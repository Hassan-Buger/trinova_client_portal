<?php
namespace Application\Services;

use Application\Config\App;
use Application\Config\DirectorCsv;
use Application\Core\Database;
use Application\Core\Session;
use Application\Exceptions\UserFacingException;
use PDO;
use PDOException;

final class DirectorCsvImportService
{
    private PDO $db;
    public function __construct(){ $this->db=Database::getInstance(); }

    public function upload(array $file):array
    {
        SchemaGuard::assertDirectorImporterReady();
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new UserFacingException('Select a directors CSV file to upload.');
        if((int)($file['size']??0)<1||(int)$file['size']>DirectorCsv::MAX_FILE_BYTES)throw new UserFacingException('The directors CSV must be between 1 byte and 5 MB.');
        if(strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION))!=='csv')throw new UserFacingException('Only .csv files are accepted.');
        $rawHash=hash_file('sha256',(string)$file['tmp_name']);if($rawHash===false)throw new UserFacingException('The directors CSV could not be fingerprinted safely.');
        $handle=fopen((string)$file['tmp_name'],'rb');if(!$handle)throw new UserFacingException('The directors CSV could not be read.');
        [$headers,$headerLine]=$this->findHeaders($handle);$mapping=DirectorCsv::mapping($headers);
        if(!isset($mapping['name'],$mapping['companies'])){fclose($handle);throw new UserFacingException('The selected file does not contain the required Director Name and Linked Company/ies columns.');}
        $rows=[];$line=$headerLine;while(($values=fgetcsv($handle))!==false){$line++;if(count($rows)>=DirectorCsv::MAX_ROWS){fclose($handle);throw new UserFacingException('The directors CSV exceeds the 5,000 row limit.');}if(!array_filter($values,fn($v)=>trim((string)$v)!==''))continue;$rows[]=['line'=>$line,'malformed'=>count($values)!==count($headers),'values'=>array_map(fn($v)=>trim((string)$v),$values)];}fclose($handle);
        if(!$rows)throw new UserFacingException('The directors CSV contains no data rows.');
        $contentHash=$this->contentHash($headers,$rows);$token=bin2hex(random_bytes(24));$reservation=$this->reserve($rawHash,$contentHash,basename((string)$file['name']),count($rows),$token);
        if($reservation['duplicate'])return ['duplicate'=>true,'existing'=>$this->duplicateDetails($reservation['record'])];
        $draft=['token'=>$token,'user_id'=>(int)Session::get('user_id'),'import_id'=>(int)$reservation['record']['id'],'filename'=>basename((string)$file['name']),'created_at'=>time(),'headers'=>$headers,'mapping'=>$mapping,'rows'=>$rows];$this->writeDraft($token,$draft);
        return $this->preview($token);
    }

    public function preview(string $token):array
    {
        SchemaGuard::assertDirectorImporterReady();$draft=$this->readDraft($token);$companies=$this->companyIndex();$planned=[];
        foreach($draft['rows'] as $raw){$data=[];foreach(DirectorCsv::fields() as $key=>$definition)$data[$key]=isset($draft['mapping'][$key])?trim((string)($raw['values'][$draft['mapping'][$key]]??'')):'';$planned[]=$this->planRow((int)$raw['line'],$data,!empty($raw['malformed']),$companies);}
        $draft['preview']=$planned;$this->writeDraft($token,$draft);return ['token'=>$token,'filename'=>$draft['filename'],'rows'=>$planned,'summary'=>$this->summary($planned)];
    }

    public function commit(string $token):array
    {
        SchemaGuard::assertDirectorImporterReady();$draft=$this->readDraft($token);if(!isset($draft['preview']))throw new UserFacingException('Preview the directors CSV before importing it.');$importId=(int)$draft['import_id'];$report=[];
        $claim=$this->db->prepare("UPDATE client_csv_imports SET status='processing',started_at=NOW() WHERE id=:id AND practice_key=:practice AND import_type='director_csv' AND status='pending'");$claim->execute(['id'=>$importId,'practice'=>$this->practiceKey()]);
        if($claim->rowCount()!==1){$saved=$this->reportById($importId);return $saved+['duplicate'=>true];}
        foreach($draft['preview'] as $row){if($row['errors']){$row['result']='Failed';$report[]=$this->reportRow($row);continue;}$this->db->beginTransaction();try{$processed=$this->commitRow($row,$importId);$this->db->commit();$report[]=$this->reportRow($processed);}catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();ErrorHandler::report(new \RuntimeException('Director import row '.$row['line'].' failed; import '.$importId,0,$e));$row['result']='Failed';$row['errors'][]='This row could not be imported safely.';$report[]=$this->reportRow($row);}}
        $summary=$this->summary($report);$result=['import_id'=>$importId,'filename'=>$draft['filename'],'completed_at'=>date('c'),'summary'=>$summary,'rows'=>$report];$status=$summary['failed']>0?'partially_completed':'completed';
        $stmt=$this->db->prepare("UPDATE client_csv_imports SET status=:status,completed_at=NOW(),total_rows=:total,created_count=:created,updated_count=:updated,skipped_count=:skipped,flagged_count=:flagged,failed_count=:failed,report_json=:report,safe_error=NULL WHERE id=:id AND practice_key=:practice AND import_type='director_csv'");$stmt->execute(['status'=>$status,'total'=>$summary['total'],'created'=>$summary['directors_created'],'updated'=>$summary['directors_updated']+$summary['placeholders_upgraded'],'skipped'=>$summary['rows_skipped'],'flagged'=>$summary['rows_flagged'],'failed'=>$summary['failed'],'report'=>json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),'id'=>$importId,'practice'=>$this->practiceKey()]);
        try{AuditService::log('director_csv_import_completed','client_csv_imports',$importId,null,['import_id'=>$importId,'total'=>$summary['total'],'created'=>$summary['directors_created'],'updated'=>$summary['directors_updated'],'flagged'=>$summary['rows_flagged'],'failed'=>$summary['failed']]);}catch(\Throwable $e){ErrorHandler::report($e);}
        $draft['report']=$result;$this->writeDraft($token,$draft);return $result+['token'=>$token];
    }

    public function reportById(int $id):array{$stmt=$this->db->prepare("SELECT report_json FROM client_csv_imports WHERE id=:id AND practice_key=:practice AND import_type='director_csv' AND status IN('completed','partially_completed')");$stmt->execute(['id'=>$id,'practice'=>$this->practiceKey()]);$json=$stmt->fetchColumn();$report=$json?json_decode((string)$json,true):null;if(!is_array($report))throw new UserFacingException('The requested Directors Import Report is unavailable.');return $report;}

    private function planRow(int $line,array $data,bool $malformed,array $index):array
    {
        $errors=[];$warnings=[];$matches=[];$notFound=[];$ambiguous=[];
        if($malformed)$errors[]='Column count does not match the header.';if($data['name']==='')$errors[]='Director Name is required.';if($data['companies']==='')$errors[]='Linked Company/ies is required.';
        if($data['email']!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){$warnings[]='Email address is invalid and will not be imported.';$data['email']='';}
        foreach($this->companyNames($data['companies']) as $company){$key=$this->normalizeCompany($company);$ids=$index[$key]??[];if(count($ids)===1)$matches[]=['id'=>(int)$ids[0]['id'],'name'=>(string)$ids[0]['company_name'],'input'=>$company];elseif(count($ids)>1)$ambiguous[]=$company;else $notFound[]=$company;}
        if(!$matches)$warnings[]='No company could be linked; no director will be created.';foreach($notFound as $name)$warnings[]='Linked company not found: '.$name;foreach($ambiguous as $name)$warnings[]='Multiple matching companies found for '.$name.'. Manual review is required.';
        return ['line'=>$line,'data'=>$data,'companies'=>$matches,'not_found'=>$notFound,'ambiguous'=>$ambiguous,'result'=>'Planned','director_result'=>'Pending','link_result'=>'Pending','profile_status'=>'Pending','warnings'=>$warnings,'errors'=>$errors];
    }

    private function commitRow(array $row,int $importId):array
    {
        if(!$row['companies']){$row['result']='Manual review required';$row['director_result']='Not created';$row['link_result']='No safe company match';$row['profile_status']='Not imported';return $row;}
        $created=0;$updated=0;$upgraded=0;$reused=0;$linksCreated=0;$linksReused=0;$needs=0;
        foreach($row['companies'] as $company){$entityId=(int)$company['id'];$candidates=$this->contacts($entityId);$match=$this->matchContact($candidates,$row['data']);if($match==='ambiguous'){$row['warnings'][]='Multiple possible directors were found at '.$company['name'].'. Manual review is required.';continue;}
            if(is_array($match)){$before=(int)$match['needs_contact_details'];$changes=$this->profileChanges($match,$row['data']);$profileComplete=$this->isComplete(array_merge($match,$changes));if($changes){$this->updateContact((int)$match['id'],$changes,$importId);if($before===1&&$profileComplete){$upgraded++;$row['director_result']='Placeholder upgraded';}else{$updated++;$row['director_result']='Existing director updated';}}else{$reused++;$row['director_result']='Existing director reused';}$linksReused++;}
            else{$profileComplete=$this->isComplete($row['data']);$stmt=$this->db->prepare('INSERT INTO entity_contacts(entity_id,user_id,name,original_full_name,email,phone,director_utr,address,id_number,ch_verification_number,is_primary,needs_contact_details,last_director_import_id) VALUES(:entity,NULL,:name,:original,:email,:phone,:utr,:address,:idn,:verify,0,:needs,:import)');$stmt->execute(['entity'=>$entityId,'name'=>$row['data']['name'],'original'=>$row['data']['name'],'email'=>$row['data']['email']?:null,'phone'=>$row['data']['phone']?:null,'utr'=>$row['data']['utr']?:null,'address'=>$row['data']['address']?:null,'idn'=>$row['data']['id_number']?:null,'verify'=>$row['data']['verification_number']?:null,'needs'=>$profileComplete?0:1,'import'=>$importId]);$created++;$linksCreated++;$row['director_result']='New director created';}
            if(!$profileComplete)$needs++;
        }
        $row['result']=($created+$updated+$upgraded+$reused)>0?'Imported':'Manual review required';$row['link_result']=$linksCreated?'Company link created':($linksReused?'Company link already existed':'Manual review required');$row['profile_status']=$needs?'Needs more details':'Complete';$row['metrics']=['created'=>$created,'updated'=>$updated,'upgraded'=>$upgraded,'reused'=>$reused,'links_created'=>$linksCreated,'links_reused'=>$linksReused,'needs'=>$needs];return $row;
    }

    private function contacts(int $entity):array{$stmt=$this->db->prepare('SELECT * FROM entity_contacts WHERE entity_id=:entity FOR UPDATE');$stmt->execute(['entity'=>$entity]);return $stmt->fetchAll();}
    private function matchContact(array $contacts,array $data):array|string|null
    {
        $tests=[fn($c)=>(int)$c['needs_contact_details']===1&&$this->normalizePerson((string)$c['name'])===$this->normalizePerson($data['name']),fn($c)=>$data['email']!==''&&strtolower(trim((string)$c['email']))===strtolower($data['email']),fn($c)=>$data['utr']!==''&&$this->identifier((string)($c['director_utr']??''))===$this->identifier($data['utr']),fn($c)=>$data['verification_number']!==''&&$this->identifier((string)($c['ch_verification_number']??''))===$this->identifier($data['verification_number']),fn($c)=>$this->normalizePerson((string)$c['name'])===$this->normalizePerson($data['name'])];
        foreach($tests as $test){$found=array_values(array_filter($contacts,$test));if(count($found)===1)return $found[0];if(count($found)>1)return 'ambiguous';}return null;
    }
    private function profileChanges(array $current,array $data):array{$map=['original_full_name'=>'name','email'=>'email','phone'=>'phone','director_utr'=>'utr','address'=>'address','id_number'=>'id_number','ch_verification_number'=>'verification_number'];$changes=[];foreach($map as $column=>$key)if(trim((string)($current[$column]??''))===''&&$data[$key]!=='')$changes[$column]=$data[$key];$merged=array_merge($current,$changes);$needed=$this->isComplete(['name'=>$merged['name']??$data['name'],'email'=>$merged['email']??'','phone'=>$merged['phone']??'','address'=>$merged['address']??''])?0:1;if((int)($current['needs_contact_details']??1)!==$needed)$changes['needs_contact_details']=$needed;return $changes;}
    private function updateContact(int $id,array $changes,int $import):void{$allowed=['original_full_name','email','phone','director_utr','address','id_number','ch_verification_number','needs_contact_details'];$set=[];$params=['id'=>$id,'import'=>$import];foreach($changes as $column=>$value)if(in_array($column,$allowed,true)){$set[]="$column=:$column";$params[$column]=$value;}$set[]='last_director_import_id=:import';$this->db->prepare('UPDATE entity_contacts SET '.implode(',',$set).' WHERE id=:id')->execute($params);}
    private function isComplete(array $data):bool{return trim((string)($data['name']??''))!==''&&filter_var((string)($data['email']??''),FILTER_VALIDATE_EMAIL)!==false&&trim((string)($data['phone']??''))!==''&&trim((string)($data['address']??''))!=='';}
    private function companyIndex():array{$index=[];foreach($this->db->query("SELECT id,company_name FROM client_entities WHERE entity_scope='company' AND company_name IS NOT NULL")->fetchAll() as $row)$index[$this->normalizeCompany((string)$row['company_name'])][]=$row;return $index;}
    private function companyNames(string $value):array{return array_values(array_unique(array_filter(array_map('trim',preg_split('/[;|\r\n]+/u',$value)?:[]))));}
    private function normalizeCompany(string $value):string{$value=strtolower(trim($value));$value=preg_replace('/\blimited\b/u','ltd',$value);return trim(preg_replace('/[^a-z0-9]+/u',' ',$value)??'');}
    private function normalizePerson(string $value):string{return trim(preg_replace('/[^a-z0-9]+/u',' ',strtolower($value))??'');}
    private function identifier(string $value):string{return strtoupper(preg_replace('/[^A-Za-z0-9]/','',$value)??'');}

    private function findHeaders($handle):array{for($line=1;$line<=10&&($row=fgetcsv($handle))!==false;$line++){$headers=array_map(fn($v)=>trim((string)$v," \t\n\r\0\x0B\xEF\xBB\xBF"),$row);$map=DirectorCsv::mapping($headers);if(isset($map['name'],$map['companies']))return [$headers,$line];}throw new UserFacingException('The selected file does not contain the required director columns.');}
    private function contentHash(array $headers,array $rows):string{$map=DirectorCsv::mapping($headers);$normalized=[];foreach($rows as $row){$record=[];foreach(DirectorCsv::fields() as $key=>$f)if(isset($map[$key]))$record[$key]=strtolower(preg_replace('/\s+/u',' ',trim((string)($row['values'][$map[$key]]??'')))??'');ksort($record);$normalized[]=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}sort($normalized);return hash('sha256',json_encode($normalized,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));}
    private function reserve(string $fileHash,string $contentHash,string $filename,int $rows,string $token):array{$existing=$this->findImport($fileHash,$contentHash);if($existing&&$existing['status']!=='failed')return ['duplicate'=>true,'record'=>$existing];if($existing){$stmt=$this->db->prepare("UPDATE client_csv_imports SET file_hash=:file,content_hash=:content,original_filename=:filename,created_by_user_id=:user,draft_token=:token,status='pending',total_rows=:rows,report_json=NULL,safe_error=NULL,started_at=NULL,completed_at=NULL WHERE id=:id AND status='failed'");$stmt->execute(['file'=>$fileHash,'content'=>$contentHash,'filename'=>$filename,'user'=>(int)Session::get('user_id'),'token'=>$token,'rows'=>$rows,'id'=>$existing['id']]);return ['duplicate'=>false,'record'=>['id'=>(int)$existing['id']]];}try{$stmt=$this->db->prepare("INSERT INTO client_csv_imports(practice_key,import_type,file_hash,content_hash,original_filename,created_by_user_id,draft_token,status,total_rows) VALUES(:practice,'director_csv',:file,:content,:filename,:user,:token,'pending',:rows)");$stmt->execute(['practice'=>$this->practiceKey(),'file'=>$fileHash,'content'=>$contentHash,'filename'=>$filename,'user'=>(int)Session::get('user_id'),'token'=>$token,'rows'=>$rows]);return ['duplicate'=>false,'record'=>['id'=>(int)$this->db->lastInsertId()]];}catch(PDOException $e){if($e->getCode()!=='23000')throw $e;$existing=$this->findImport($fileHash,$contentHash);if(!$existing)throw $e;return ['duplicate'=>true,'record'=>$existing];}}
    private function findImport(string $file,string $content):?array{$stmt=$this->db->prepare("SELECT i.*,u.name imported_by FROM client_csv_imports i LEFT JOIN users u ON u.id=i.created_by_user_id WHERE i.practice_key=:practice AND i.import_type='director_csv' AND(i.file_hash=:file OR i.content_hash=:content) ORDER BY i.id DESC LIMIT 1");$stmt->execute(['practice'=>$this->practiceKey(),'file'=>$file,'content'=>$content]);return $stmt->fetch()?:null;}
    private function duplicateDetails(array $r):array{return ['id'=>(int)$r['id'],'status'=>$r['status'],'filename'=>$r['original_filename'],'imported_by'=>$r['imported_by']??'Staff user','completed_at'=>$r['completed_at']??null,'report_url'=>in_array($r['status'],['completed','partially_completed'],true)?'/staff/directors/import/report/'.(int)$r['id']:null];}
    private function summary(array $rows):array{$s=['total'=>count($rows),'directors_created'=>0,'directors_updated'=>0,'placeholders_upgraded'=>0,'directors_reused'=>0,'links_created'=>0,'links_reused'=>0,'rows_flagged'=>0,'rows_skipped'=>0,'failed'=>0,'companies_not_found'=>0,'ambiguous_companies'=>0,'needing_details'=>0];foreach($rows as $r){$m=$r['metrics']??[];$s['directors_created']+=(int)($m['created']??0);$s['directors_updated']+=(int)($m['updated']??0);$s['placeholders_upgraded']+=(int)($m['upgraded']??0);$s['directors_reused']+=(int)($m['reused']??0);$s['links_created']+=(int)($m['links_created']??0);$s['links_reused']+=(int)($m['links_reused']??0);$s['needing_details']+=(int)($m['needs']??0);$s['companies_not_found']+=count($r['not_found']??[]);$s['ambiguous_companies']+=count($r['ambiguous']??[]);if($r['warnings']??[])$s['rows_flagged']++;if(($r['result']??'')==='Failed')$s['failed']++;if(($r['result']??'')==='Manual review required')$s['rows_skipped']++;}return $s;}
    private function reportRow(array $row):array{$row['data']=['name'=>(string)($row['data']['name']??''),'companies'=>(string)($row['data']['companies']??'')];return $row;}
    private function practiceKey():string{return substr((string)App::get('practice_key','trinova-default'),0,64);}
    private function path(string $token):string{if(!preg_match('/^[a-f0-9]{48}$/',$token))throw new UserFacingException('The import token is invalid.');$dir=App::get('storage_dir').'/director-imports';if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new UserFacingException('The secure Directors Importer workspace is unavailable.');return $dir.'/'.$token.'.json';}
    private function writeDraft(string $token,array $data):void{if(file_put_contents($this->path($token),json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX)===false)throw new UserFacingException('The secure import preview could not be saved.');}
    private function readDraft(string $token):array{$path=$this->path($token);if(!is_file($path))throw new UserFacingException('This Directors Importer preview has expired.');$data=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if((int)($data['user_id']??0)!==(int)Session::get('user_id')||time()-(int)($data['created_at']??0)>3600)throw new UserFacingException('This import preview has expired or belongs to another user.');return $data;}
}
