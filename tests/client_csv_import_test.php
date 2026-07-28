<?php

require dirname(__DIR__).'/vendor/autoload.php';

use Application\Config\ClientCsv;
use Application\Exceptions\UserFacingException;
use Application\Services\ClientCsvImportService;

function expect(bool $condition,string $message): void { if(!$condition) throw new RuntimeException($message); }

$reflection=new ReflectionClass(ClientCsvImportService::class);
$service=$reflection->newInstanceWithoutConstructor();

$headers=$reflection->getMethod('findHeaders');
$stream=fopen('php://temp','w+b');
fputcsv($stream,[ClientCsv::TITLE]);
fputcsv($stream,["\xEF\xBB\xBFCLIENT NAME",' Company No. ','Director(s) / Contact(s)','Email']);
fputcsv($stream,['Example Ltd','00123456','Alex Example; Sam Example','company@example.com']);
rewind($stream);
[$detected,$line]=$headers->invoke($service,$stream);
expect($line===2,'The title row was not skipped.');
expect((ClientCsv::defaultMapping($detected)['client_name']??null)===0,'BOM/case-insensitive Client Name mapping failed.');
fclose($stream);

$invalid=fopen('php://temp','w+b');fputcsv($invalid,['Not','A','Header']);rewind($invalid);
try{$headers->invoke($service,$invalid);throw new RuntimeException('Invalid headers were accepted.');}catch(ReflectionException $e){throw $e;}catch(Throwable $e){$cause=$e instanceof ReflectionException?$e:$e->getPrevious();expect($e instanceof UserFacingException||$cause instanceof UserFacingException,'Invalid header did not produce a safe validation error.');}fclose($invalid);

$validate=$reflection->getMethod('validateRow');
$base=['client_name'=>'Example Ltd','status_notes'=>'','company_number'=>'00123456','utr'=>'','vat_number'=>'','address'=>'','directors'=>" Alex Example; ;Sam Example, alex   example\nTaylor Example ",'email'=>'company@example.com','phone'=>'0123456789','year_end'=>'Thu 31 Jul 2025','filing_deadline'=>'Thu 30 Apr 2026','vat_quarter'=>'Jan/Apr/Jul/Oct'];
$existing=['company_number'=>['00123456'=>42],'utr'=>[],'vat_number'=>[],'email_company'=>[],'contacts'=>[42=>['alex example'=>true]]];
$row=$validate->invoke($service,3,$base,false,$existing);
expect($row['directors']===['Alex Example','Sam Example','Taylor Example'],'Director delimiters, blanks or normalized duplicates were not handled.');
expect($row['director_plan']['reuse']===['Alex Example'],'Existing company contact was not reused.');
expect($row['director_plan']['create']===['Sam Example','Taylor Example'],'Missing company contacts were not planned correctly.');
expect($row['errors']===[],'Valid dates or values were rejected.');

$emailExisting=$existing;$emailExisting['company_number']=[];$emailExisting['contacts']=[];$emailExisting['email_company']['company@example.com|example ltd']=77;$noNumber=$base;$noNumber['company_number']='';
$row=$validate->invoke($service,4,$noNumber,false,$emailExisting);
expect(($row['match']['entity_id']??0)===77,'Email plus company-name fallback did not prevent a duplicate entity.');

$bad=$base;$bad['client_name']='';$bad['filing_deadline']='not-a-date';
$row=$validate->invoke($service,5,$bad,false,$existing);
expect(count($row['errors'])>=2,'Missing company name and invalid date were not rejected.');

$source=file_get_contents(dirname(__DIR__).'/application/Services/ClientCsvImportService.php');
expect(str_contains($source,'VALUES(:entity,NULL,:name,NULL,NULL,:primary,1)'),'Director placeholders are not name-only records.');
expect(!str_contains($source,'NotificationService'),'CSV import must not send invitations or notification emails.');

echo "Client CSV focused tests passed\n";
