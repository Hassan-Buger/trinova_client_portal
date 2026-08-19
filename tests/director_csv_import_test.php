<?php
declare(strict_types=1);
$appDir = dirname(__DIR__);
if (file_exists($appDir . '/vendor/autoload.php')) {
    require_once $appDir . '/vendor/autoload.php';
} else {
    spl_autoload_register(function ($class) use ($appDir) {
        $prefix = 'Application\\';
        $baseDir = $appDir . '/application/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;
        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) require_once $file;
    });
}

use Application\Config\DirectorCsv;
use Application\Services\DirectorCsvImportService;

function ok(bool $value,string $message):void{if(!$value)throw new RuntimeException($message);}

$exact=DirectorCsv::mapping(DirectorCsv::headers());
ok(isset($exact['name'],$exact['companies']),'Exact required headers are not recognized.');
$aliases=DirectorCsv::mapping(['Full Name','Companies','Director UTR','Mobile','Email Address','Residential Address','Identification Number','CH Verification Number']);
ok(count($aliases)===8,'Header aliases are not recognized.');
$bom=DirectorCsv::mapping(["\xEF\xBB\xBFDirector Name",'Linked Company/ies']);
ok(isset($bom['name'],$bom['companies']),'UTF-8 BOM is not handled.');
ok(!isset(DirectorCsv::mapping(['Name','Customer'])['companies']),'Unrelated CSV was accepted.');

$root=dirname(__DIR__);$routes=file_get_contents($root.'/public/index.php');$controller=file_get_contents($root.'/application/Controllers/Staff/DirectorCsvController.php');$service=file_get_contents($root.'/application/Services/DirectorCsvImportService.php');$clientService=file_get_contents($root.'/application/Services/ClientCsvImportService.php');
ok(str_contains($routes,"'/directors/import'"),'Separate Directors Importer route is missing.');
ok(str_contains($controller,'final class DirectorCsvController'),'Dedicated controller is missing.');
ok(str_contains($service,'final class DirectorCsvImportService'),'Dedicated service is missing.');
ok(str_contains($service,"import_type='director_csv'")&&str_contains($service,"'director_csv'"),'Separate import type is missing.');
ok(str_contains($service,"preg_split('/[;|\\r\\n]+/u'"),'Safe multi-company delimiter handling is missing.');
ok(!str_contains($service,'INSERT INTO client_entities')&&!str_contains($service,'INSERT INTO clients')&&!str_contains($service,'INSERT INTO users'),'Directors Importer can create company/client/login accounts.');
ok(str_contains($service,'practice_key=:practice'),'Director reports are not tenant scoped.');
ok(str_contains($controller,'preg_match')&&str_contains($controller,"[=+\\-@]"),'Report CSV formula-injection protection is missing.');
ok(str_contains($clientService,"'business_clients'"),'Existing company importer import type changed unexpectedly.');

$reflection=new ReflectionClass(DirectorCsvImportService::class);$service=$reflection->newInstanceWithoutConstructor();
$call=function(string $method,array $arguments=[])use($reflection,$service){$m=$reflection->getMethod($method);return $m->invokeArgs($service,$arguments);};
ok($call('normalizeCompany',['AB Design Ltd'])===$call('normalizeCompany',[' AB DESIGN Limited. ']),'Ltd/Limited normalization is unsafe.');
ok($call('companyNames',["Company One Ltd; Company Two Ltd|Company Three Ltd\nCompany Four Ltd"])===['Company One Ltd','Company Two Ltd','Company Three Ltd','Company Four Ltd'],'Multiple-company separators are not handled.');
ok($call('companyNames',['Smith, Jones & Co Ltd'])===['Smith, Jones & Co Ltd'],'Company names are incorrectly split on commas.');
$placeholder=['id'=>7,'name'=>'Alan Berry','email'=>null,'phone'=>null,'director_utr'=>null,'address'=>null,'id_number'=>null,'ch_verification_number'=>null,'needs_contact_details'=>1];
$data=['name'=>'Alan Berry','email'=>'alan@example.invalid','phone'=>'07700900123','utr'=>'0012345678','address'=>'Example address','id_number'=>'ID-001','verification_number'=>'PV-001'];
ok(($call('matchContact',[[$placeholder],$data]))['id']===7,'Company-scoped placeholder was not matched by name.');
$changes=$call('profileChanges',[$placeholder,$data]);
ok($changes['director_utr']==='0012345678'&&$changes['phone']==='07700900123','String identifiers or leading-zero phone values were changed.');
$existing=array_merge($placeholder,['email'=>'kept@example.invalid','phone'=>'07000000000']);$blank=array_fill_keys(array_keys($data),'');$blank['name']='Alan Berry';$changes=$call('profileChanges',[$existing,$blank]);
ok(!isset($changes['email'],$changes['phone']),'Blank CSV values overwrite reliable profile data.');
$safe=$call('reportRow',[['data'=>$data,'line'=>3]]);
ok(array_keys($safe['data'])===['name','companies']&&!str_contains(json_encode($safe),'0012345678'),'Sensitive values remain in persistent reports.');

// Test multi-column duplicate headers mapping (5 company name columns)
$multiHeaders=DirectorCsv::mapping(['Director Name','UTR','company name','company name','company name','company name','company name']);
ok(is_array($multiHeaders['companies'])&&count($multiHeaders['companies'])===5&&$multiHeaders['companies']===[2,3,4,5,6],'Multiple duplicate company name columns were not all captured.');

// Test tab and whitespace sanitization
ok($call('sanitizeString',["Kevin Gardiner\t\t"])==='Kevin Gardiner','Embedded trailing tabs were not sanitized.');
ok($call('normalizeCompany',[" Trinova Accounting\t\t "])==='trinova accounting','Tab sanitization failed during company normalization.');

echo "Directors Importer structural, multi-header, tab sanitization, and company linking tests passed.\n";

