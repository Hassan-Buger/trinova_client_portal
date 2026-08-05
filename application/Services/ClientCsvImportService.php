<?php

namespace Application\Services;

use Application\Config\App;
use Application\Config\ClientCsv;
use Application\Core\Database;
use Application\Core\Session;
use Application\Exceptions\UserFacingException;
use PDO;
use PDOException;

final class ClientCsvImportService
{
    private PDO $db;
    public function __construct(){ $this->db=Database::getInstance(); }

    public function upload(array $file): array
    {
        SchemaGuard::assertClientCsvReady();
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new UserFacingException('Select a CSV file to upload.');
        if((int)($file['size']??0)<=0 || (int)$file['size']>ClientCsv::MAX_FILE_BYTES) throw new UserFacingException('The CSV must be between 1 byte and 5 MB.');
        if(strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION))!=='csv') throw new UserFacingException('Only .csv files are accepted.');
        if(function_exists('finfo_open')){$info=finfo_open(FILEINFO_MIME_TYPE);$mime=$info?finfo_file($info,(string)$file['tmp_name']):false;if($info)finfo_close($info);if($mime!==false&&!in_array(strtolower((string)$mime),['text/csv','text/plain','application/csv','application/vnd.ms-excel','application/octet-stream'],true))throw new UserFacingException('The uploaded file is not recognized as a CSV.');}
        $fileHash=hash_file('sha256',(string)$file['tmp_name']);
        if($fileHash===false)throw new UserFacingException('The uploaded CSV could not be fingerprinted safely.');
        $handle=fopen((string)$file['tmp_name'],'rb');
        if(!$handle) throw new UserFacingException('The uploaded CSV could not be read.');
        [$headers,$headerLine]=$this->findHeaders($handle);
        if(count($headers)<2 || count(array_filter($headers))!==count(array_unique(array_filter($headers)))){fclose($handle);throw new UserFacingException('The CSV headers are missing or duplicated.');}
        $rows=[];$line=$headerLine;
        while(($row=fgetcsv($handle))!==false){
            $line++; if(count($rows)>=ClientCsv::MAX_ROWS){fclose($handle);throw new UserFacingException('The CSV exceeds the 5,000 row import limit.');}
            if(count(array_filter($row,fn($v)=>trim((string)$v)!==''))===0) continue;
            if(count($row)!==count($headers)){$rows[]=['_line'=>$line,'_malformed'=>true,'values'=>$row];continue;}
            $rows[]=['_line'=>$line,'values'=>array_map(fn($v)=>trim((string)$v),$row)];
        }
        fclose($handle);
        if(!$rows) throw new UserFacingException('The CSV contains no data rows.');
        $token=bin2hex(random_bytes(24));
        $contentHash=$this->contentHash($headers,$rows);
        $reservation=$this->reserveImport($fileHash,$contentHash,basename((string)$file['name']),count($rows),$token,(int)Session::get('user_id'));
        if(!empty($reservation['duplicate'])){try{AuditService::log('client_csv_duplicate_prevented','client_csv_imports',(int)$reservation['record']['id'],null,['practice_key'=>$this->practiceKey(),'filename'=>basename((string)$file['name']),'existing_import_id'=>(int)$reservation['record']['id'],'status'=>$reservation['record']['status']]);}catch(\Throwable $auditError){error_log('CSV duplicate audit logging failed: '.$auditError->getMessage());}return ['duplicate'=>true,'existing'=>$this->duplicateDetails($reservation['record'])];}
        $draft=['token'=>$token,'user_id'=>(int)Session::get('user_id'),'import_id'=>(int)$reservation['record']['id'],'filename'=>basename((string)$file['name']),'created_at'=>time(),'headers'=>$headers,'rows'=>$rows];
        try{$this->writeDraft($token,$draft);}catch(\Throwable $e){$this->failImport((int)$reservation['record']['id'],'The secure import draft could not be stored.');throw $e;}
        return ['token'=>$token,'import_id'=>(int)$reservation['record']['id'],'headers'=>$headers,'mapping'=>ClientCsv::defaultMapping($headers),'row_count'=>count($rows),'filename'=>$draft['filename']];
    }

    public function preview(string $token,array $mapping): array
    {
        SchemaGuard::assertClientCsvReady();
        $draft=$this->readDraft($token); $fields=ClientCsv::fields(); $clean=[];$used=[];
        foreach($fields as $key=>$definition){
            $index=$mapping[$key]??'';
            if($index===''){if($definition['required']) throw new UserFacingException("Map the required '{$definition['header']}' field.");continue;}
            if(!ctype_digit((string)$index) || !array_key_exists((int)$index,$draft['headers'])) throw new UserFacingException('The selected column mapping is invalid.');
            if(isset($used[(int)$index])) throw new UserFacingException('A CSV column cannot be mapped to more than one destination field.');
            $used[(int)$index]=true;$clean[$key]=(int)$index;
        }
        $identifiers=$this->existingIdentifiers();$preview=[];$seen=[];
        foreach($draft['rows'] as $raw){
            $data=[];foreach($clean as $key=>$index)$data[$key]=trim((string)($raw['values'][$index]??''));
            $planned=$this->validateRow((int)$raw['_line'],$data,!empty($raw['_malformed']),$identifiers);
            foreach(['company_number','utr','vat_number'] as $key){$id=$this->identifier($data[$key]??'');if($id==='')continue;if(isset($seen[$key][$id])){$planned['errors'][]="Duplicates CSV row {$seen[$key][$id]} by {$key}.";break;}$seen[$key][$id]=(int)$raw['_line'];}
            $preview[]=$planned;
        }
        $draft['mapping']=$clean;$draft['preview']=$preview;$this->writeDraft($token,$draft);
        return ['token'=>$token,'filename'=>$draft['filename'],'headers'=>$draft['headers'],'mapping'=>$clean,'rows'=>$preview,'summary'=>$this->summarize($preview),'expires_at'=>$draft['created_at']+3600];
    }

    public function commit(string $token): array
    {
        SchemaGuard::assertClientCsvReady();
        $draft=$this->readDraft($token); if(empty($draft['preview'])) throw new UserFacingException('Preview this import before confirming it.');
        $report=[];$currentLine=0;$importId=(int)($draft['import_id']??0);if($importId<1)throw new UserFacingException('This import draft predates duplicate protection. Upload the CSV again.');$this->db->beginTransaction();
        try{
            $tracked=$this->lockImport($importId);
            if(!$tracked)throw new UserFacingException('The tracked import could not be found. Upload the CSV again.');
            if($tracked['status']==='completed'){$this->db->rollBack();$saved=$this->decodeReport($tracked);return $saved+['import_id'=>$importId,'duplicate'=>true];}
            if($tracked['status']!=='pending')throw new UserFacingException('This CSV import is already being processed.');
            $this->db->prepare("UPDATE client_csv_imports SET status='processing',started_at=NOW(),safe_error=NULL WHERE id=:id")->execute(['id'=>$importId]);
            foreach($draft['preview'] as $row){
                $currentLine=(int)($row['line']??0);
                if($row['errors']){$row['result']='failed';$report[]=$row;continue;}
                $report[]=$this->commitRow($row);
            }
            $summary=$this->summarize($report);
            $result=['filename'=>$draft['filename'],'rows'=>$report,'summary'=>$summary,'completed_at'=>date('c'),'vat_rule_confirmed'=>ClientCsv::VAT_RULE_CONFIRMED,'vat_offset_days'=>ClientCsv::VAT_DEADLINE_OFFSET_DAYS,'import_id'=>$importId];
            $this->completeImport($importId,$summary,$result);
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();$this->failImport($importId,'The import could not be completed. Please review the file and try again.');throw new \RuntimeException('Client CSV commit failed at row '.$currentLine.'; all changes were rolled back. Cause: '.$e->getMessage(),0,$e);}
        AuditService::log('client_csv_import','clients',null,null,[
            'filename'=>$draft['filename'],'total_rows'=>$summary['total'],'created'=>$summary['created'],
            'updated'=>$summary['updated'],'skipped'=>$summary['skipped'],'flagged'=>$summary['flagged'],
            'failed'=>$summary['failed'],'status'=>$summary['failed']>0?'completed_with_errors':'completed',
            'director_names_detected'=>$summary['director_names_detected'],
            'placeholder_directors_created'=>$summary['placeholder_directors_created'],
            'placeholder_directors_reused'=>$summary['placeholder_directors_reused'],
            'director_links_created'=>$summary['director_links_created'],
        ]);
        $draft['report']=$result;$this->writeDraft($token,$draft);
        return $result+['token'=>$token];
    }

    public function report(string $token): array { $draft=$this->readDraft($token);if(empty($draft['report']))throw new UserFacingException('No completed report is available.');return $draft['report']; }

    public function reportById(int $id): array
    {
        $stmt=$this->db->prepare("SELECT * FROM client_csv_imports WHERE id=:id AND practice_key=:practice AND import_type='business_clients' AND status='completed' LIMIT 1");
        $stmt->execute(['id'=>$id,'practice'=>$this->practiceKey()]);$record=$stmt->fetch();
        if(!$record)throw new UserFacingException('The requested import report is unavailable.');
        return $this->decodeReport($record)+['import_id'=>$id];
    }

    /** @return array{0:array,1:int} */
    private function findHeaders($handle): array
    {
        for($line=1;$line<=10 && ($candidate=fgetcsv($handle))!==false;$line++){
            $candidate=array_map(fn($value)=>trim((string)$value," \t\n\r\0\x0B\xEF\xBB\xBF"),$candidate);
            $mapping=ClientCsv::defaultMapping($candidate);
            if(isset($mapping['client_name'])&&count($mapping)>=2)return [$candidate,$line];
        }
        throw new UserFacingException('The CSV header could not be recognized. Ensure the Client Name and expected business columns are present.');
    }

    private function contentHash(array $headers,array $rows): string
    {
        $mapping=ClientCsv::defaultMapping($headers);$normalized=[];
        foreach($rows as $row){$values=$row['values']??[];$record=[];foreach(ClientCsv::fields() as $key=>$definition){if(!isset($mapping[$key]))continue;$value=trim((string)($values[$mapping[$key]]??''));$record[$key]=strtolower(preg_replace('/\s+/u',' ',$value)??$value);}if(!empty($row['_malformed']))$record['_malformed']=array_map(fn($value)=>strtolower(trim((string)$value)),$values);$normalized[]=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
        sort($normalized,SORT_STRING);
        return hash('sha256',json_encode($normalized,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
    }

    private function reserveImport(string $fileHash,string $contentHash,string $filename,int $rows,string $token,int $userId): array
    {
        $practice=$this->practiceKey();$existing=$this->findTrackedImport($fileHash,$contentHash);
        if($existing){
            if($existing['status']==='failed'){$retry=$this->db->prepare("UPDATE client_csv_imports SET file_hash=:file_hash,content_hash=:content_hash,original_filename=:filename,created_by_user_id=:user,draft_token=:token,status='pending',total_rows=:rows,safe_error=NULL,started_at=NULL,completed_at=NULL,report_json=NULL WHERE id=:id AND status='failed'");try{$retry->execute(['file_hash'=>$fileHash,'content_hash'=>$contentHash,'filename'=>$filename,'user'=>$userId?:null,'token'=>$token,'rows'=>$rows,'id'=>$existing['id']]);}catch(PDOException $e){if($e->getCode()!=='23000')throw $e;}if($retry->rowCount()>0)return ['duplicate'=>false,'record'=>['id'=>(int)$existing['id']]];$existing=$this->findTrackedImport($fileHash,$contentHash);}
            if($existing&&in_array($existing['status'],['pending','processing'],true)&&strtotime((string)$existing['updated_at'])<time()-900){$retry=$this->db->prepare("UPDATE client_csv_imports SET file_hash=:file_hash,content_hash=:content_hash,original_filename=:filename,created_by_user_id=:user,draft_token=:token,status='pending',total_rows=:rows,safe_error=NULL,started_at=NULL,completed_at=NULL,report_json=NULL WHERE id=:id AND status IN('pending','processing') AND updated_at<DATE_SUB(NOW(),INTERVAL 15 MINUTE)");$retry->execute(['file_hash'=>$fileHash,'content_hash'=>$contentHash,'filename'=>$filename,'user'=>$userId?:null,'token'=>$token,'rows'=>$rows,'id'=>$existing['id']]);if($retry->rowCount()>0)return ['duplicate'=>false,'record'=>['id'=>(int)$existing['id']]];$existing=$this->findTrackedImport($fileHash,$contentHash);}
            if($existing)return ['duplicate'=>true,'record'=>$existing];
        }
        try{$stmt=$this->db->prepare("INSERT INTO client_csv_imports(practice_key,import_type,file_hash,content_hash,original_filename,created_by_user_id,draft_token,status,total_rows) VALUES(:practice,'business_clients',:file_hash,:content_hash,:filename,:user,:token,'pending',:rows)");$stmt->execute(['practice'=>$practice,'file_hash'=>$fileHash,'content_hash'=>$contentHash,'filename'=>$filename,'user'=>$userId?:null,'token'=>$token,'rows'=>$rows]);return ['duplicate'=>false,'record'=>['id'=>(int)$this->db->lastInsertId()]];}catch(PDOException $e){if($e->getCode()!=='23000')throw $e;$existing=$this->findTrackedImport($fileHash,$contentHash);if(!$existing)throw $e;return ['duplicate'=>true,'record'=>$existing];}
    }

    private function findTrackedImport(string $fileHash,string $contentHash): ?array
    {
        $stmt=$this->db->prepare("SELECT i.*,u.name AS imported_by FROM client_csv_imports i LEFT JOIN users u ON u.id=i.created_by_user_id WHERE i.practice_key=:practice AND i.import_type='business_clients' AND (i.file_hash=:file_hash OR i.content_hash=:content_hash) ORDER BY (i.status='completed') DESC,i.id DESC LIMIT 1");$stmt->execute(['practice'=>$this->practiceKey(),'file_hash'=>$fileHash,'content_hash'=>$contentHash]);return $stmt->fetch()?:null;
    }

    private function duplicateDetails(array $record): array
    {
        return ['id'=>(int)$record['id'],'status'=>(string)$record['status'],'filename'=>(string)$record['original_filename'],'imported_by'=>(string)($record['imported_by']??'Staff user'),'completed_at'=>$record['completed_at']??null,'total_rows'=>(int)$record['total_rows'],'created'=>(int)$record['created_count'],'updated'=>(int)$record['updated_count'],'flagged'=>(int)$record['flagged_count'],'report_url'=>$record['status']==='completed'?'/staff/clients/import/report/'.(int)$record['id']:null];
    }

    private function lockImport(int $id): ?array{$stmt=$this->db->prepare('SELECT * FROM client_csv_imports WHERE id=:id AND practice_key=:practice FOR UPDATE');$stmt->execute(['id'=>$id,'practice'=>$this->practiceKey()]);return $stmt->fetch()?:null;}
    private function completeImport(int $id,array $summary,array $report): void{$this->db->prepare("UPDATE client_csv_imports SET status='completed',completed_at=NOW(),total_rows=:total,created_count=:created,updated_count=:updated,skipped_count=:skipped,flagged_count=:flagged,failed_count=:failed,report_json=:report,safe_error=NULL WHERE id=:id")->execute(['total'=>$summary['total'],'created'=>$summary['created'],'updated'=>$summary['updated'],'skipped'=>$summary['skipped'],'flagged'=>$summary['flagged'],'failed'=>$summary['failed'],'report'=>json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'id'=>$id]);}
    private function failImport(int $id,string $message): void{if($id<1)return;try{$this->db->prepare("UPDATE client_csv_imports SET status='failed',safe_error=:error,completed_at=NOW() WHERE id=:id AND status<>'completed'")->execute(['error'=>$message,'id'=>$id]);}catch(\Throwable $ignored){}}
    private function decodeReport(array $record): array{$report=json_decode((string)($record['report_json']??''),true);if(!is_array($report))throw new UserFacingException('The original import report is unavailable.');return $report;}
    private function practiceKey(): string{return substr((string)App::get('practice_key','trinova-default'),0,64);}

    private function validateRow(int $line,array $data,bool $malformed,array $existing): array
    {
        $errors=[];$warnings=[];$directors=[];$seenNames=[];
        foreach(preg_split('/[;,\r\n]+/u',(string)($data['directors']??''))?:[] as $name){$name=trim($name);if($name==='')continue;$normalized=$this->normalizeName($name);if(isset($seenNames[$normalized]))continue;$seenNames[$normalized]=true;$directors[]=$name;}
        if($malformed)$errors[]='Malformed row: column count does not match the header.';
        if(($data['client_name']??'')==='')$errors[]='Company name is required.';
        if(($data['company_number']??'')!==''&&!preg_match('/^[A-Za-z0-9]{6,10}$/',$data['company_number']))$warnings[]='Company Number has an unusual format.';
        if(($data['utr']??'')!==''&&!preg_match('/^\d{10}$/',preg_replace('/\s+/','',$data['utr'])))$warnings[]='UTR should normally contain 10 digits.';
        if(($data['vat_number']??'')!==''&&!preg_match('/^(GB)?[0-9A-Za-z ]{8,14}$/i',$data['vat_number']))$warnings[]='VAT number has an unusual format.';
        if(($data['email']??'')!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL))$errors[]='Email address is invalid.';
        if(!$directors)$warnings[]='No director/contact names were supplied.';
        if($directors)$warnings[]='Director names will be linked as incomplete placeholders; full profiles will be imported later.';
        foreach(['year_end'=>'Accounting year end','filing_deadline'=>'Filing deadline'] as $key=>$label){if(($data[$key]??'')!==''&&!$this->parseDate($data[$key]))$errors[]="{$label} is invalid or unsupported.";}
        if(($data['vat_quarter']??'')!==''&&!$this->vatMonths($data['vat_quarter']))$errors[]='VAT quarter must contain four recurring months, such as Jan/Apr/Jul/Oct.';
        $match=null;
        foreach(['company_number','utr','vat_number'] as $key){$v=$this->identifier($data[$key]??'');if($v!==''&&isset($existing[$key][$v])){$match=['entity_id'=>$existing[$key][$v],'field'=>$key,'value'=>$data[$key]];break;}}
        if(!$match&&($data['email']??'')!==''&&($data['client_name']??'')!==''){$emailCompany=strtolower(trim($data['email'])).'|'.$this->normalizeName($data['client_name']);if(isset($existing['email_company'][$emailCompany]))$match=['entity_id'=>$existing['email_company'][$emailCompany],'field'=>'email + company name','value'=>$data['email']];}
        if(!$match && ($data['company_number']??'')===''&&($data['utr']??'')===''&&($data['vat_number']??'')==='')$warnings[]='No strong company identifier was supplied; safe re-import matching is limited.';
        if(!$match && ($data['email']??'')==='')$errors[]='A primary contact email is required when creating a new company.';
        $existingContacts=$match?($existing['contacts'][(int)$match['entity_id']]??[]):[];$create=[];$reuse=[];
        foreach($directors as $name){if(isset($existingContacts[$this->normalizeName($name)]))$reuse[]=$name;else $create[]=$name;}
        return ['line'=>$line,'data'=>$data,'directors'=>$directors,'director_plan'=>['create'=>$create,'reuse'=>$reuse],'match'=>$match,'action'=>$match?'update':'create','errors'=>$errors,'warnings'=>$warnings,'result'=>'planned','director_names_detected'=>count($directors),'placeholder_directors_created'=>count($create),'placeholder_directors_reused'=>count($reuse),'director_links_created'=>count($create),'duplicate_director_links_skipped'=>count($reuse),'directors_needing_details'=>count($directors)];
    }

    private function commitRow(array $row): array
    {
        $d=$row['data'];$entityId=(int)($row['match']['entity_id']??0);$created=false;$companyAccountCreated=0;$placeholderCount=0;$placeholderReused=0;$deadlines=0;
        if($entityId){
            $stmt=$this->db->prepare('SELECT e.*,c.user_id,c.id AS client_id FROM client_entities e JOIN clients c ON c.id=e.client_id WHERE e.id=:id FOR UPDATE');$stmt->execute(['id'=>$entityId]);$entity=$stmt->fetch();
            if(!$entity){throw new UserFacingException('A matched company no longer exists.');}
            $clientId=(int)$entity['client_id'];$userId=(int)$entity['user_id'];
            $attrs=json_decode((string)($entity['attributes']??'{}'),true)?:[];
            foreach(['vat_number'=>$d['vat_number']??'','ct_utr'=>$d['utr']??'','accounting_year_end'=>$this->parseDate($d['year_end']??''),'vat_quarter'=>$d['vat_quarter']??''] as $k=>$v)if($v!==''&&empty($attrs[$k]))$attrs[$k]=['label'=>$k,'value'=>$v];
            $this->db->prepare('UPDATE client_entities SET company_name=COALESCE(NULLIF(company_name,\'\'),:name),company_number=COALESCE(NULLIF(company_number,\'\'),:number),tax_reference=COALESCE(NULLIF(tax_reference,\'\'),:utr),attributes=:attrs WHERE id=:id')->execute(['name'=>$d['client_name'],'number'=>$d['company_number']?:null,'utr'=>$d['utr']?:null,'attrs'=>json_encode($attrs,JSON_UNESCAPED_UNICODE),'id'=>$entityId]);
            $this->db->prepare('UPDATE clients SET phone=COALESCE(NULLIF(phone,\'\'),:phone),address=COALESCE(NULLIF(address,\'\'),:address),notes=COALESCE(NULLIF(notes,\'\'),:notes) WHERE id=:id')->execute(['phone'=>$d['phone']?:null,'address'=>$d['address']?:null,'notes'=>$d['status_notes']?:null,'id'=>$clientId]);
        }else{
            $user=$this->db->prepare('SELECT id FROM users WHERE email=:email LIMIT 1');$user->execute(['email'=>$d['email']]);$userId=(int)($user->fetchColumn()?:0);
            if($userId){$c=$this->db->prepare('SELECT id FROM clients WHERE user_id=:id');$c->execute(['id'=>$userId]);$clientId=(int)($c->fetchColumn()?:0);if(!$clientId)throw new UserFacingException('The primary email belongs to a non-client account.');}
            else{$this->db->prepare("INSERT INTO users(name,email,password_hash,role,status) VALUES(:name,:email,:hash,'client','pending_activation')")->execute(['name'=>$d['client_name'],'email'=>$d['email'],'hash'=>password_hash(bin2hex(random_bytes(24)),PASSWORD_BCRYPT)]);$userId=(int)$this->db->lastInsertId();$this->db->prepare("INSERT INTO clients(user_id,phone,address,aml_status,notes) VALUES(:user,:phone,:address,'Action Required',:notes)")->execute(['user'=>$userId,'phone'=>$d['phone']?:null,'address'=>$d['address']?:null,'notes'=>$d['status_notes']?:null]);$clientId=(int)$this->db->lastInsertId();$companyAccountCreated=1;}
            $attrs=['vat_number'=>['label'=>'VAT registration number','value'=>$d['vat_number']??''],'ct_utr'=>['label'=>'Corporation Tax UTR','value'=>$d['utr']??''],'accounting_year_end'=>['label'=>'Accounting year end','value'=>$this->parseDate($d['year_end']??'')],'vat_quarter'=>['label'=>'VAT quarter pattern','value'=>$d['vat_quarter']??'']];$attrs=array_filter($attrs,fn($v)=>$v['value']!=='');
            $this->db->prepare("INSERT INTO client_entities(client_id,company_name,entity_type,entity_scope,company_number,tax_reference,attributes) VALUES(:client,:name,'Limited Company','company',:number,:utr,:attrs)")->execute(['client'=>$clientId,'name'=>$d['client_name'],'number'=>$d['company_number']?:null,'utr'=>$d['utr']?:null,'attrs'=>json_encode($attrs,JSON_UNESCAPED_UNICODE)]);$entityId=(int)$this->db->lastInsertId();$created=true;
            $this->db->prepare('INSERT IGNORE INTO entity_directors(entity_id,user_id,created_by_user_id) VALUES(:entity,:user,:creator)')->execute(['entity'=>$entityId,'user'=>$userId,'creator'=>(int)\Application\Core\Session::get('user_id')]);
        }
        $knownContacts=[];$known=$this->db->prepare('SELECT name FROM entity_contacts WHERE entity_id=:entity');$known->execute(['entity'=>$entityId]);foreach($known->fetchAll() as $contact)$knownContacts[$this->normalizeName((string)$contact['name'])]=true;
        foreach($row['directors'] as $i=>$name){$normalized=$this->normalizeName($name);if(isset($knownContacts[$normalized])){$placeholderReused++;continue;}$stmt=$this->db->prepare('INSERT IGNORE INTO entity_contacts(entity_id,user_id,name,email,phone,is_primary,needs_contact_details) VALUES(:entity,NULL,:name,NULL,NULL,:primary,1)');$stmt->execute(['entity'=>$entityId,'name'=>$name,'primary'=>$i===0?1:0]);if($stmt->rowCount()>0){$placeholderCount++;$knownContacts[$normalized]=true;}else $placeholderReused++;}
        if(($date=$this->parseDate($d['filing_deadline']??''))!=='' && $this->ensureDeadline($clientId,$entityId,ClientCsv::FILING_DEADLINE_TYPE,$date))$deadlines++;
        if(($d['vat_quarter']??'')!=='' && ($date=$this->nextVatDeadline($d['vat_quarter'])) && $this->ensureDeadline($clientId,$entityId,ClientCsv::VAT_DEADLINE_TYPE,$date))$deadlines++;
        $row['result']=$created?'created':'updated';$row['entity_id']=$entityId;$row['company_accounts_created']=$companyAccountCreated;$row['placeholder_directors_created']=$placeholderCount;$row['placeholder_directors_reused']=$placeholderReused;$row['director_links_created']=$placeholderCount;$row['duplicate_director_links_skipped']=$placeholderReused;$row['director_names_detected']=count($row['directors']);$row['directors_needing_details']=count($row['directors']);$row['deadlines_created']=$deadlines;return $row;
    }

    private function existingIdentifiers(): array
    {
        $result=['company_number'=>[],'utr'=>[],'vat_number'=>[],'email_company'=>[],'contacts'=>[]];
        foreach($this->db->query("SELECT e.id,e.company_name,e.company_number,e.tax_reference,e.attributes,u.email FROM client_entities e JOIN clients c ON c.id=e.client_id JOIN users u ON u.id=c.user_id WHERE e.entity_scope='company'")->fetchAll() as $row){$a=json_decode((string)($row['attributes']??'{}'),true)?:[];foreach(['company_number'=>$row['company_number'],'utr'=>$this->attr($a,'ct_utr')?:$row['tax_reference'],'vat_number'=>$this->attr($a,'vat_number')] as $k=>$v){$v=$this->identifier((string)$v);if($v!=='')$result[$k][$v]=(int)$row['id'];}$email=strtolower(trim((string)$row['email']));if($email!=='')$result['email_company'][$email.'|'.$this->normalizeName((string)$row['company_name'])]=(int)$row['id'];}
        foreach($this->db->query('SELECT entity_id,name FROM entity_contacts')->fetchAll() as $contact)$result['contacts'][(int)$contact['entity_id']][$this->normalizeName((string)$contact['name'])]=true;
        return $result;
    }
    private function attr(array $a,string $k): string{$v=$a[$k]??'';return trim((string)(is_array($v)?($v['value']??''):$v));}
    private function identifier(string $v):string{return strtoupper(preg_replace('/[^A-Za-z0-9]/','',$v)??'');}
    private function normalizeName(string $v):string{return strtolower(preg_replace('/\s+/',' ',trim($v))??'');}
    private function parseDate(string $v):string{$v=trim($v);if($v==='')return '';$v=preg_replace('/^[A-Za-z]{3}\s+/','',$v);foreach(['!d M Y','!j M Y','!Y-m-d','!d/m/Y','!d-m-Y'] as $f){$d=\DateTimeImmutable::createFromFormat($f,$v,new \DateTimeZone('UTC'));if($d&&\DateTimeImmutable::getLastErrors()!==false&&\DateTimeImmutable::getLastErrors()['warning_count']===0&&\DateTimeImmutable::getLastErrors()['error_count']===0)return $d->format('Y-m-d');if($d&&\DateTimeImmutable::getLastErrors()===false)return $d->format('Y-m-d');}return '';}
    private function vatMonths(string $v):array{$map=['jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12];$parts=array_map(fn($p)=>strtolower(substr(trim($p),0,3)),explode('/',$v));if(count($parts)!==4)return [];$months=[];foreach($parts as $p){if(!isset($map[$p]))return [];$months[]=$map[$p];}sort($months);for($i=1;$i<4;$i++)if(($months[$i]-$months[$i-1])!==3)return [];return $months;}
    private function nextVatDeadline(string $pattern):?string{$months=$this->vatMonths($pattern);if(!$months)return null;$today=new \DateTimeImmutable('today',new \DateTimeZone('UTC'));foreach([$today->format('Y'),(string)((int)$today->format('Y')+1)] as $year)foreach($months as $month){$end=(new \DateTimeImmutable(sprintf('%s-%02d-01',$year,$month),new \DateTimeZone('UTC')))->modify('last day of this month');$due=$end->modify('+'.ClientCsv::VAT_DEADLINE_OFFSET_DAYS.' days');if($due>=$today)return $due->format('Y-m-d');}return null;}
    private function ensureDeadline(int $client,int $entity,string $type,string $date):bool{$s=$this->db->prepare('INSERT INTO deadlines(client_id,entity_id,scope,type,due_date,status) SELECT :client,:entity,\'company\',:type,:date,\'Pending\' WHERE NOT EXISTS(SELECT 1 FROM deadlines WHERE entity_id=:entity2 AND type=:type2 AND due_date=:date2)');$s->execute(['client'=>$client,'entity'=>$entity,'type'=>$type,'date'=>$date,'entity2'=>$entity,'type2'=>$type,'date2'=>$date]);return $s->rowCount()>0;}
    private function summarize(array $rows):array{$s=['total'=>count($rows),'created'=>0,'updated'=>0,'skipped'=>0,'flagged'=>0,'failed'=>0,'company_accounts_created'=>0,'director_names_detected'=>0,'placeholder_directors_created'=>0,'placeholder_directors_reused'=>0,'director_links_created'=>0,'duplicate_director_links_skipped'=>0,'directors_needing_details'=>0,'deadlines_created'=>0];foreach($rows as $r){$result=$r['result']??'planned';if($result==='planned')$result=($r['errors']??[])?'failed':($r['action']??'skipped');if(isset($s[$result]))$s[$result]++;if($r['warnings']??[])$s['flagged']++;foreach(['company_accounts_created','director_names_detected','placeholder_directors_created','placeholder_directors_reused','director_links_created','duplicate_director_links_skipped','directors_needing_details','deadlines_created'] as $k)$s[$k]+=(int)($r[$k]??0);}return $s;}
    private function path(string $token):string{if(!preg_match('/^[a-f0-9]{48}$/',$token))throw new UserFacingException('The import token is invalid.');$dir=App::get('storage_dir').'/csv-imports';if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new UserFacingException('The secure import workspace is unavailable.');return $dir.'/'.$token.'.json';}
    private function writeDraft(string $token,array $data):void{file_put_contents($this->path($token),json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX);}
    private function readDraft(string $token):array{$path=$this->path($token);if(!is_file($path))throw new UserFacingException('This import preview has expired.');$data=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if((int)($data['user_id']??0)!==(int)\Application\Core\Session::get('user_id')||time()-(int)($data['created_at']??0)>3600)throw new UserFacingException('This import preview has expired or belongs to another user.');return $data;}

    public function deleteImportBatch(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE client_csv_imports SET deleted_at = NOW() WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            \Application\Services\AuditService::log('csv_import_batch_deleted', 'client_csv_imports', $id);
        }
        return $success;
    }

    public function restoreImportBatch(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE client_csv_imports SET deleted_at = NULL WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            \Application\Services\AuditService::log('csv_import_batch_restored', 'client_csv_imports', $id);
        }
        return $success;
    }

    public function getAllBatches(): array
    {
        $stmt = $this->db->prepare("SELECT i.*, u.name AS imported_by FROM client_csv_imports i LEFT JOIN users u ON u.id = i.created_by_user_id WHERE i.deleted_at IS NULL ORDER BY i.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSoftDeletedBatches(): array
    {
        $stmt = $this->db->prepare("SELECT i.*, u.name AS imported_by FROM client_csv_imports i LEFT JOIN users u ON u.id = i.created_by_user_id WHERE i.deleted_at IS NOT NULL ORDER BY i.deleted_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
