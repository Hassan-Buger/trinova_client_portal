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

$uniqueHeaders=$reflection->getMethod('uniqueHeaders');
$directorColumns=$reflection->getMethod('directorColumns');
$mergeDirectors=$reflection->getMethod('mergeDirectorColumns');
$repeated=['COMPANY NAME','Company Number','Director','Director','Director'];
$unique=$uniqueHeaders->invoke($service,$repeated);
expect($unique===['COMPANY NAME','Company Number','Director','Director (2)','Director (3)'],'Repeated spreadsheet headers were not made safe for column mapping.');
$directorIndexes=$directorColumns->invoke($service,$repeated);
expect($directorIndexes===[2,3,4],'Repeated Director columns were not detected.');
$merged=$mergeDirectors->invoke($service,['Example Ltd','00123456','Alex Example','Sam Example','alex   example'],$directorIndexes);
expect($merged[2]==='Alex Example; Sam Example' && $merged[3]==='' && $merged[4]==='','Repeated Director values were not consolidated without duplicates.');
expect((ClientCsv::defaultMapping($unique)['directors']??null)===2,'The consolidated Director column was not auto-mapped.');
expect((ClientCsv::defaultMapping(['END OF YEAR DATE','accounts dealine','QTR1'])['year_end']??null)===0,'Common year-end header was not mapped.');
expect((ClientCsv::defaultMapping(['END OF YEAR DATE','accounts dealine','QTR1'])['filing_deadline']??null)===1,'Common accounts deadline header was not mapped.');
expect((ClientCsv::defaultMapping(['END OF YEAR DATE','accounts dealine','QTR1'])['vat_quarter']??null)===2,'Common VAT-quarter header was not mapped.');

$canReclaim=$reflection->getMethod('canReclaimPending');
expect($canReclaim->invoke($service,['status'=>'pending','created_by_user_id'=>9],9)===true,'The same staff member cannot recover an abandoned pending import.');
expect($canReclaim->invoke($service,['status'=>'processing','created_by_user_id'=>9],9)===false,'A processing import could be reclaimed unsafely.');
expect($canReclaim->invoke($service,['status'=>'pending','created_by_user_id'=>8],9)===false,'One staff member could reclaim another staff member\'s pending import.');

$fingerprint=$reflection->getMethod('contentHash');
$fingerprintHeaders=['Client Name','Company No.','Email'];
$rowsA=[['_line'=>2,'values'=>['Example Ltd','00123456','company@example.com']],['_line'=>3,'values'=>['Second Ltd','00876543','second@example.com']]];
$rowsB=[['_line'=>20,'values'=>[' second   ltd ','00876543','SECOND@example.com']],['_line'=>21,'values'=>['EXAMPLE LTD','00123456','company@example.com']]];
expect($fingerprint->invoke($service,$fingerprintHeaders,$rowsA)===$fingerprint->invoke($service,$fingerprintHeaders,$rowsB),'Reordered or whitespace/case-only CSV changes bypassed normalized duplicate detection.');
$tempA=tempnam(sys_get_temp_dir(),'csv-a');$tempB=tempnam(sys_get_temp_dir(),'csv-b');file_put_contents($tempA,"a,b\r\n1,2\r\n");file_put_contents($tempB,file_get_contents($tempA));expect(hash_file('sha256',$tempA)===hash_file('sha256',$tempB),'Renaming identical file content changed the raw fingerprint.');unlink($tempA);unlink($tempB);

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
expect($row['errors']===[],'Business validation still blocks a structurally valid company row.');
expect(count($row['warnings'])>=2,'Business validation warnings were lost.');
$malformed=$validate->invoke($service,6,$base,true,$existing);
expect(count($malformed['errors'])===1,'A malformed CSV row is no longer a blocking technical error.');
$attributes=$reflection->getMethod('businessAttributes')->invoke($service,$base+['_source_fields'=>['PAYE REF NUMBER'=>'123/AB456']]);
expect(($attributes['accounting_year_end']['value']??'')==='Thu 31 Jul 2025','Accounting year-end input was normalized instead of retained.');
expect(str_contains((string)($attributes['csv_source_data']['value']??''),'PAYE REF NUMBER'),'Unmapped source fields are not retained.');

$source=file_get_contents(dirname(__DIR__).'/application/Services/ClientCsvImportService.php');
expect(str_contains($source,'VALUES(:entity,NULL,:name,NULL,NULL,:primary,1)'),'Director placeholders are not name-only records.');
expect(!str_contains($source,'NotificationService'),'CSV import must not send invitations or notification emails.');
$migration=file_get_contents(dirname(__DIR__).'/config/client_csv_duplicate_tracking_migration.sql');
expect(str_contains($migration,'UNIQUE KEY uq_csv_import_file (practice_key, import_type, file_hash)'),'Exact-file concurrency constraint is missing.');
expect(str_contains($migration,'UNIQUE KEY uq_csv_import_content (practice_key, import_type, content_hash)'),'Normalized-content concurrency constraint is missing.');
expect(str_contains($source,"status='completed'"),'Completed import state is not persisted.');
expect(str_contains($source,'practice_key=:practice'),'Import/report queries are not tenant scoped.');
expect(str_contains($source,'FOR UPDATE'),'Concurrent commit locking is missing.');
expect(str_contains($source,'Email address format appears unusual; the supplied value will still be stored.'),'Invalid email values are not retained as warnings.');
expect(str_contains($source,'VAT quarter format appears unusual; the supplied value will still be stored.'),'VAT format validation is not preserved as a warning.');
expect(str_contains($source,'Duplicates CSV row {$seen[$key][$id]} by {$key}; database matching will determine whether this company is created or updated.'),'Duplicate business identifiers still block otherwise importable rows.');
expect(str_contains($source,"'accounting_year_end'=>['label'=>'Accounting year end','value'=>(string)(\$data['year_end']??'')]"),'The raw accounting year value is not retained.');
expect(str_contains($source,'This row could not be saved because of a technical or database error.'),'Technical row failures are not reported safely.');
expect(str_contains($source,'failed_count>=total_rows'),'A previously all-failed CSV cannot be retried under the warning-only validation policy.');
expect(str_contains($source,"(\$row['result'] ?? '') !== 'created'"),'Batch cleanup is not restricted to companies created by that import.');
expect(str_contains($source,"practice_key=:practice AND import_type='business_clients' FOR UPDATE"),'Batch deletion is not tenant-scoped and transactionally locked.');
expect(str_contains($source,"if (!empty(\$batch['deleted_at']))"),'Repeated batch deletion is not idempotent.');
$controllerSource=file_get_contents(dirname(__DIR__).'/application/Controllers/Staff/ClientCsvController.php');
$reportViewSource=file_get_contents(dirname(__DIR__).'/application/Views/staff/clients/import-report.php');
expect(str_contains($controllerSource,"input('import_id', 0)"),'The batch endpoint does not accept legacy import_id submissions.');
expect(str_contains($reportViewSource,'name="batch_id"'),'The import report does not submit the batch identifier expected by the endpoint.');
expect(str_contains($controllerSource,"'/staff/trash?tab=batches'"),'Successful deletion does not redirect to the CSV batch history in Trash.');
expect(!str_contains($controllerSource,"'Failed to delete batch.'"),'Batch deletion still hides every failure behind the old generic toast.');
$databaseSql=file_get_contents(dirname(__DIR__).'/config/database.sql');
expect(str_contains($databaseSql,'`deleted_at` DATETIME NULL'),'The canonical CSV import schema does not support soft deletion.');

echo "Client CSV focused tests passed\n";
