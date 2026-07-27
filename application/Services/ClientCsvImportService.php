<?php

namespace Application\Services;

use Application\Config\App;
use Application\Config\ClientCsv;
use Application\Core\Database;
use PDO;

final class ClientCsvImportService
{
    private PDO $db;
    public function __construct(){ $this->db=Database::getInstance(); }

    public function upload(array $file): array
    {
        if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) throw new \RuntimeException('Select a CSV file to upload.');
        if((int)($file['size']??0)<=0 || (int)$file['size']>ClientCsv::MAX_FILE_BYTES) throw new \RuntimeException('The CSV must be between 1 byte and 5 MB.');
        if(strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION))!=='csv') throw new \RuntimeException('Only .csv files are accepted.');
        $handle=fopen((string)$file['tmp_name'],'rb');
        if(!$handle) throw new \RuntimeException('The uploaded CSV could not be read.');
        $headers=fgetcsv($handle);
        if(!$headers){fclose($handle);throw new \RuntimeException('The CSV is empty.');}
        $headers=array_map(fn($v)=>trim((string)$v," \t\n\r\0\x0B\xEF\xBB\xBF"),$headers);
        // TriNova's canonical export has a human-readable title row before the
        // actual header row. Accept it while still supporting ordinary CSVs.
        if(($headers[0]??'')===ClientCsv::TITLE && count(array_filter(array_slice($headers,1),fn($v)=>$v!==''))===0){
            $headers=fgetcsv($handle);
            if(!$headers){fclose($handle);throw new \RuntimeException('The CSV title is present but its header row is missing.');}
            $headers=array_map(fn($v)=>trim((string)$v," \t\n\r\0\x0B\xEF\xBB\xBF"),$headers);
        }
        if(count($headers)<2 || count(array_filter($headers))!==count(array_unique(array_filter($headers)))){fclose($handle);throw new \RuntimeException('The CSV headers are missing or duplicated.');}
        $rows=[];$line=1;
        while(($row=fgetcsv($handle))!==false){
            $line++; if(count($rows)>=ClientCsv::MAX_ROWS){fclose($handle);throw new \RuntimeException('The CSV exceeds the 5,000 row import limit.');}
            if(count(array_filter($row,fn($v)=>trim((string)$v)!==''))===0) continue;
            if(count($row)!==count($headers)){$rows[]=['_line'=>$line,'_malformed'=>true,'values'=>$row];continue;}
            $rows[]=['_line'=>$line,'values'=>array_map(fn($v)=>trim((string)$v),$row)];
        }
        fclose($handle);
        if(!$rows) throw new \RuntimeException('The CSV contains no data rows.');
        $token=bin2hex(random_bytes(24));
        $draft=['token'=>$token,'user_id'=>(int)\Application\Core\Session::get('user_id'),'filename'=>basename((string)$file['name']),'created_at'=>time(),'headers'=>$headers,'rows'=>$rows];
        $this->writeDraft($token,$draft);
        return ['token'=>$token,'headers'=>$headers,'mapping'=>ClientCsv::defaultMapping($headers),'row_count'=>count($rows),'filename'=>$draft['filename']];
    }

    public function preview(string $token,array $mapping): array
    {
        $draft=$this->readDraft($token); $fields=ClientCsv::fields(); $clean=[];$used=[];
        foreach($fields as $key=>$definition){
            $index=$mapping[$key]??'';
            if($index===''){if($definition['required']) throw new \RuntimeException("Map the required '{$definition['header']}' field.");continue;}
            if(!ctype_digit((string)$index) || !array_key_exists((int)$index,$draft['headers'])) throw new \RuntimeException('The selected column mapping is invalid.');
            if(isset($used[(int)$index])) throw new \RuntimeException('A CSV column cannot be mapped to more than one destination field.');
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
        $draft=$this->readDraft($token); if(empty($draft['preview'])) throw new \RuntimeException('Preview this import before confirming it.');
        $report=[];$this->db->beginTransaction();
        try{
            foreach($draft['preview'] as $row){
                if($row['errors']){$row['result']='failed';$report[]=$row;continue;}
                $report[]=$this->commitRow($row);
            }
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
        $summary=$this->summarize($report);
        AuditService::log('client_csv_import','clients',null,null,[
            'filename'=>$draft['filename'],'total_rows'=>$summary['total'],'created'=>$summary['created'],
            'updated'=>$summary['updated'],'skipped'=>$summary['skipped'],'flagged'=>$summary['flagged'],
            'failed'=>$summary['failed'],'status'=>$summary['failed']>0?'completed_with_errors':'completed',
        ]);
        $result=['filename'=>$draft['filename'],'rows'=>$report,'summary'=>$summary,'completed_at'=>date('c'),'vat_rule_confirmed'=>ClientCsv::VAT_RULE_CONFIRMED,'vat_offset_days'=>ClientCsv::VAT_DEADLINE_OFFSET_DAYS];
        $draft['report']=$result;$this->writeDraft($token,$draft);
        return $result+['token'=>$token];
    }

    public function report(string $token): array { $draft=$this->readDraft($token);if(empty($draft['report']))throw new \RuntimeException('No completed report is available.');return $draft['report']; }

    private function validateRow(int $line,array $data,bool $malformed,array $existing): array
    {
        $errors=[];$warnings=[];$directors=array_values(array_filter(array_map('trim',explode(';',(string)($data['directors']??'')))));
        if($malformed)$errors[]='Malformed row: column count does not match the header.';
        if(($data['client_name']??'')==='')$errors[]='Company name is required.';
        if(($data['company_number']??'')!==''&&!preg_match('/^[A-Za-z0-9]{6,10}$/',$data['company_number']))$warnings[]='Company Number has an unusual format.';
        if(($data['utr']??'')!==''&&!preg_match('/^\d{10}$/',preg_replace('/\s+/','',$data['utr'])))$warnings[]='UTR should normally contain 10 digits.';
        if(($data['vat_number']??'')!==''&&!preg_match('/^(GB)?[0-9A-Za-z ]{8,14}$/i',$data['vat_number']))$warnings[]='VAT number has an unusual format.';
        if(($data['email']??'')!==''&&!filter_var($data['email'],FILTER_VALIDATE_EMAIL))$errors[]='Email address is invalid.';
        if(!$directors)$warnings[]='No director/contact names were supplied.';
        if(count($directors)>1 && (($data['email']??'')!==''||($data['phone']??'')!==''))$warnings[]='Only the first contact receives the supplied email and phone; remaining directors need details.';
        foreach(['year_end'=>'Accounting year end','filing_deadline'=>'Filing deadline'] as $key=>$label){if(($data[$key]??'')!==''&&!$this->parseDate($data[$key]))$errors[]="{$label} is invalid or unsupported.";}
        if(($data['vat_quarter']??'')!==''&&!$this->vatMonths($data['vat_quarter']))$errors[]='VAT quarter must contain four recurring months, such as Jan/Apr/Jul/Oct.';
        $match=null;
        foreach(['company_number','utr','vat_number'] as $key){$v=$this->identifier($data[$key]??'');if($v!==''&&isset($existing[$key][$v])){$match=['entity_id'=>$existing[$key][$v],'field'=>$key,'value'=>$data[$key]];break;}}
        if(!$match && ($data['company_number']??'')===''&&($data['utr']??'')===''&&($data['vat_number']??'')==='')$warnings[]='No strong company identifier was supplied; safe re-import matching is limited.';
        if(!$match && ($data['email']??'')==='')$errors[]='A primary contact email is required when creating a new company.';
        return ['line'=>$line,'data'=>$data,'directors'=>$directors,'match'=>$match,'action'=>$match?'update':'create','errors'=>$errors,'warnings'=>$warnings,'result'=>'planned'];
    }

    private function commitRow(array $row): array
    {
        $d=$row['data'];$entityId=(int)($row['match']['entity_id']??0);$created=false;$primaryCreated=0;$placeholderCount=0;$deadlines=0;
        if($entityId){
            $stmt=$this->db->prepare('SELECT e.*,c.user_id,c.id AS client_id FROM client_entities e JOIN clients c ON c.id=e.client_id WHERE e.id=:id FOR UPDATE');$stmt->execute(['id'=>$entityId]);$entity=$stmt->fetch();
            if(!$entity){throw new \RuntimeException('A matched company no longer exists.');}
            $clientId=(int)$entity['client_id'];$userId=(int)$entity['user_id'];
            $attrs=json_decode((string)($entity['attributes']??'{}'),true)?:[];
            foreach(['vat_number'=>$d['vat_number']??'','ct_utr'=>$d['utr']??'','accounting_year_end'=>$this->parseDate($d['year_end']??''),'vat_quarter'=>$d['vat_quarter']??''] as $k=>$v)if($v!==''&&empty($attrs[$k]))$attrs[$k]=['label'=>$k,'value'=>$v];
            $this->db->prepare('UPDATE client_entities SET company_name=COALESCE(NULLIF(company_name,\'\'),:name),company_number=COALESCE(NULLIF(company_number,\'\'),:number),tax_reference=COALESCE(NULLIF(tax_reference,\'\'),:utr),attributes=:attrs WHERE id=:id')->execute(['name'=>$d['client_name'],'number'=>$d['company_number']?:null,'utr'=>$d['utr']?:null,'attrs'=>json_encode($attrs,JSON_UNESCAPED_UNICODE),'id'=>$entityId]);
            $this->db->prepare('UPDATE clients SET phone=COALESCE(NULLIF(phone,\'\'),:phone),address=COALESCE(NULLIF(address,\'\'),:address),notes=COALESCE(NULLIF(notes,\'\'),:notes) WHERE id=:id')->execute(['phone'=>$d['phone']?:null,'address'=>$d['address']?:null,'notes'=>$d['status_notes']?:null,'id'=>$clientId]);
        }else{
            $user=$this->db->prepare('SELECT id FROM users WHERE email=:email LIMIT 1');$user->execute(['email'=>$d['email']]);$userId=(int)($user->fetchColumn()?:0);
            if($userId){$c=$this->db->prepare('SELECT id FROM clients WHERE user_id=:id');$c->execute(['id'=>$userId]);$clientId=(int)($c->fetchColumn()?:0);if(!$clientId)throw new \RuntimeException('The primary email belongs to a non-client account.');}
            else{$this->db->prepare("INSERT INTO users(name,email,password_hash,role,status) VALUES(:name,:email,:hash,'client','pending_activation')")->execute(['name'=>$row['directors'][0]??$d['client_name'],'email'=>$d['email'],'hash'=>password_hash(bin2hex(random_bytes(24)),PASSWORD_BCRYPT)]);$userId=(int)$this->db->lastInsertId();$this->db->prepare("INSERT INTO clients(user_id,phone,address,aml_status,notes) VALUES(:user,:phone,:address,'Action Required',:notes)")->execute(['user'=>$userId,'phone'=>$d['phone']?:null,'address'=>$d['address']?:null,'notes'=>$d['status_notes']?:null]);$clientId=(int)$this->db->lastInsertId();$primaryCreated=1;}
            $attrs=['vat_number'=>['label'=>'VAT registration number','value'=>$d['vat_number']??''],'ct_utr'=>['label'=>'Corporation Tax UTR','value'=>$d['utr']??''],'accounting_year_end'=>['label'=>'Accounting year end','value'=>$this->parseDate($d['year_end']??'')],'vat_quarter'=>['label'=>'VAT quarter pattern','value'=>$d['vat_quarter']??'']];$attrs=array_filter($attrs,fn($v)=>$v['value']!=='');
            $this->db->prepare("INSERT INTO client_entities(client_id,company_name,entity_type,entity_scope,company_number,tax_reference,attributes) VALUES(:client,:name,'Limited Company','company',:number,:utr,:attrs)")->execute(['client'=>$clientId,'name'=>$d['client_name'],'number'=>$d['company_number']?:null,'utr'=>$d['utr']?:null,'attrs'=>json_encode($attrs,JSON_UNESCAPED_UNICODE)]);$entityId=(int)$this->db->lastInsertId();$created=true;
            $this->db->prepare('INSERT IGNORE INTO entity_directors(entity_id,user_id,created_by_user_id) VALUES(:entity,:user,:creator)')->execute(['entity'=>$entityId,'user'=>$userId,'creator'=>(int)\Application\Core\Session::get('user_id')]);
        }
        foreach($row['directors'] as $i=>$name){$isPrimary=$i===0;$stmt=$this->db->prepare('INSERT INTO entity_contacts(entity_id,user_id,name,email,phone,is_primary,needs_contact_details) VALUES(:entity,:user,:name,:email,:phone,:primary,:needs) ON DUPLICATE KEY UPDATE user_id=COALESCE(user_id,VALUES(user_id)),email=COALESCE(email,VALUES(email)),phone=COALESCE(phone,VALUES(phone)),is_primary=GREATEST(is_primary,VALUES(is_primary))');$stmt->execute(['entity'=>$entityId,'user'=>$isPrimary?$userId:null,'name'=>$name,'email'=>$isPrimary&&($d['email']??'')!==''?$d['email']:null,'phone'=>$isPrimary&&($d['phone']??'')!==''?$d['phone']:null,'primary'=>$isPrimary?1:0,'needs'=>$isPrimary?0:1]);if(!$isPrimary)$placeholderCount++;}
        if(($date=$this->parseDate($d['filing_deadline']??''))!=='' && $this->ensureDeadline($clientId,$entityId,ClientCsv::FILING_DEADLINE_TYPE,$date))$deadlines++;
        if(($d['vat_quarter']??'')!=='' && ($date=$this->nextVatDeadline($d['vat_quarter'])) && $this->ensureDeadline($clientId,$entityId,ClientCsv::VAT_DEADLINE_TYPE,$date))$deadlines++;
        $row['result']=$created?'created':'updated';$row['entity_id']=$entityId;$row['primary_contacts_created']=$primaryCreated;$row['placeholder_directors_created']=$placeholderCount;$row['deadlines_created']=$deadlines;return $row;
    }

    private function existingIdentifiers(): array
    {
        $result=['company_number'=>[],'utr'=>[],'vat_number'=>[]];
        foreach($this->db->query('SELECT id,company_number,tax_reference,attributes FROM client_entities WHERE entity_scope=\'company\'')->fetchAll() as $row){$a=json_decode((string)($row['attributes']??'{}'),true)?:[];foreach(['company_number'=>$row['company_number'],'utr'=>$this->attr($a,'ct_utr')?:$row['tax_reference'],'vat_number'=>$this->attr($a,'vat_number')] as $k=>$v){$v=$this->identifier((string)$v);if($v!=='')$result[$k][$v]=(int)$row['id'];}}return $result;
    }
    private function attr(array $a,string $k): string{$v=$a[$k]??'';return trim((string)(is_array($v)?($v['value']??''):$v));}
    private function identifier(string $v):string{return strtoupper(preg_replace('/[^A-Za-z0-9]/','',$v)??'');}
    private function parseDate(string $v):string{$v=trim($v);if($v==='')return '';$v=preg_replace('/^[A-Za-z]{3}\s+/','',$v);foreach(['!d M Y','!j M Y','!Y-m-d','!d/m/Y','!d-m-Y'] as $f){$d=\DateTimeImmutable::createFromFormat($f,$v,new \DateTimeZone('UTC'));if($d&&\DateTimeImmutable::getLastErrors()!==false&&\DateTimeImmutable::getLastErrors()['warning_count']===0&&\DateTimeImmutable::getLastErrors()['error_count']===0)return $d->format('Y-m-d');if($d&&\DateTimeImmutable::getLastErrors()===false)return $d->format('Y-m-d');}return '';}
    private function vatMonths(string $v):array{$map=['jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12];$parts=array_map(fn($p)=>strtolower(substr(trim($p),0,3)),explode('/',$v));if(count($parts)!==4)return [];$months=[];foreach($parts as $p){if(!isset($map[$p]))return [];$months[]=$map[$p];}sort($months);for($i=1;$i<4;$i++)if(($months[$i]-$months[$i-1])!==3)return [];return $months;}
    private function nextVatDeadline(string $pattern):?string{$months=$this->vatMonths($pattern);if(!$months)return null;$today=new \DateTimeImmutable('today',new \DateTimeZone('UTC'));foreach([$today->format('Y'),(string)((int)$today->format('Y')+1)] as $year)foreach($months as $month){$end=(new \DateTimeImmutable(sprintf('%s-%02d-01',$year,$month),new \DateTimeZone('UTC')))->modify('last day of this month');$due=$end->modify('+'.ClientCsv::VAT_DEADLINE_OFFSET_DAYS.' days');if($due>=$today)return $due->format('Y-m-d');}return null;}
    private function ensureDeadline(int $client,int $entity,string $type,string $date):bool{$s=$this->db->prepare('INSERT INTO deadlines(client_id,entity_id,scope,type,due_date,status) SELECT :client,:entity,\'company\',:type,:date,\'Pending\' WHERE NOT EXISTS(SELECT 1 FROM deadlines WHERE entity_id=:entity2 AND type=:type2 AND due_date=:date2)');$s->execute(['client'=>$client,'entity'=>$entity,'type'=>$type,'date'=>$date,'entity2'=>$entity,'type2'=>$type,'date2'=>$date]);return $s->rowCount()>0;}
    private function summarize(array $rows):array{$s=['total'=>count($rows),'created'=>0,'updated'=>0,'skipped'=>0,'flagged'=>0,'failed'=>0,'primary_contacts_created'=>0,'placeholder_directors_created'=>0,'deadlines_created'=>0];foreach($rows as $r){$result=$r['result']??'planned';if($result==='planned')$result=($r['errors']??[])?'failed':($r['action']??'skipped');if(isset($s[$result]))$s[$result]++;if($r['warnings']??[])$s['flagged']++;foreach(['primary_contacts_created','placeholder_directors_created','deadlines_created'] as $k)$s[$k]+=(int)($r[$k]??0);}return $s;}
    private function path(string $token):string{if(!preg_match('/^[a-f0-9]{48}$/',$token))throw new \RuntimeException('The import token is invalid.');$dir=App::get('storage_dir').'/csv-imports';if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException('The secure import workspace is unavailable.');return $dir.'/'.$token.'.json';}
    private function writeDraft(string $token,array $data):void{file_put_contents($this->path($token),json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX);}
    private function readDraft(string $token):array{$path=$this->path($token);if(!is_file($path))throw new \RuntimeException('This import preview has expired.');$data=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if((int)($data['user_id']??0)!==(int)\Application\Core\Session::get('user_id')||time()-(int)($data['created_at']??0)>3600)throw new \RuntimeException('This import preview has expired or belongs to another user.');return $data;}
}
