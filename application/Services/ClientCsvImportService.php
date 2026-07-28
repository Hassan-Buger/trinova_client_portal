<?php

namespace Application\Services;

use Application\Config\App;
use Application\Config\ClientCsv;
use Application\Core\Database;
use Application\Exceptions\UserFacingException;
use PDO;

final class ClientCsvImportService
{
    private PDO $db;
    public function __construct(){ $this->db=Database::getInstance(); }

    public function upload(array $file): array
    {
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new UserFacingException('Select a CSV file to upload.');
        if((int)($file['size']??0)<=0 || (int)$file['size']>ClientCsv::MAX_FILE_BYTES) throw new UserFacingException('The CSV must be between 1 byte and 5 MB.');
        if(strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION))!=='csv') throw new UserFacingException('Only .csv files are accepted.');
        if(function_exists('finfo_open')){$info=finfo_open(FILEINFO_MIME_TYPE);$mime=$info?finfo_file($info,(string)$file['tmp_name']):false;if($info)finfo_close($info);if($mime!==false&&!in_array(strtolower((string)$mime),['text/csv','text/plain','application/csv','application/vnd.ms-excel','application/octet-stream'],true))throw new UserFacingException('The uploaded file is not recognized as a CSV.');}
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
        $draft=['token'=>$token,'user_id'=>(int)\Application\Core\Session::get('user_id'),'filename'=>basename((string)$file['name']),'created_at'=>time(),'headers'=>$headers,'rows'=>$rows];
        $this->writeDraft($token,$draft);
        return ['token'=>$token,'headers'=>$headers,'mapping'=>ClientCsv::defaultMapping($headers),'row_count'=>count($rows),'filename'=>$draft['filename']];
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
        $report=[];$currentLine=0;$this->db->beginTransaction();
        try{
            foreach($draft['preview'] as $row){
                $currentLine=(int)($row['line']??0);
                if($row['errors']){$row['result']='failed';$report[]=$row;continue;}
                $report[]=$this->commitRow($row);
            }
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw new \RuntimeException('Client CSV commit failed at row '.$currentLine.'; all changes were rolled back. Cause: '.$e->getMessage(),0,$e);}
        $summary=$this->summarize($report);
        AuditService::log('client_csv_import','clients',null,null,[
            'filename'=>$draft['filename'],'total_rows'=>$summary['total'],'created'=>$summary['created'],
            'updated'=>$summary['updated'],'skipped'=>$summary['skipped'],'flagged'=>$summary['flagged'],
            'failed'=>$summary['failed'],'status'=>$summary['failed']>0?'completed_with_errors':'completed',
            'director_names_detected'=>$summary['director_names_detected'],
            'placeholder_directors_created'=>$summary['placeholder_directors_created'],
            'placeholder_directors_reused'=>$summary['placeholder_directors_reused'],
            'director_links_created'=>$summary['director_links_created'],
        ]);
        $result=['filename'=>$draft['filename'],'rows'=>$report,'summary'=>$summary,'completed_at'=>date('c'),'vat_rule_confirmed'=>ClientCsv::VAT_RULE_CONFIRMED,'vat_offset_days'=>ClientCsv::VAT_DEADLINE_OFFSET_DAYS];
        $draft['report']=$result;$this->writeDraft($token,$draft);
        return $result+['token'=>$token];
    }

    public function report(string $token): array { $draft=$this->readDraft($token);if(empty($draft['report']))throw new UserFacingException('No completed report is available.');return $draft['report']; }

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
}
