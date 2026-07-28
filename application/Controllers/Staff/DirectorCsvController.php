<?php
namespace Application\Controllers\Staff;

use Application\Config\DirectorCsv;
use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Exceptions\UserFacingException;
use Application\Services\DirectorCsvImportService;

final class DirectorCsvController extends Controller
{
    public function index(Request $request,Response $response):void{$this->render('staff/directors/import',['pageTitle'=>'Directors Importer'],'main');}
    public function upload(Request $request,Response $response):void{try{$result=(new DirectorCsvImportService())->upload($_FILES['csv_file']??[]);if(!empty($result['duplicate'])){$this->render('staff/directors/import-duplicate',['pageTitle'=>'Duplicate Director Import Prevented','existing'=>$result['existing']],'main');return;}$this->render('staff/directors/import-preview',['pageTitle'=>'Review Director Import','preview'=>$result],'main');}catch(UserFacingException $e){$this->fail($request,$response,$e->getMessage());}}
    public function commit(Request $request,Response $response):void{try{$result=(new DirectorCsvImportService())->commit((string)$request->input('token',''));$this->render('staff/directors/import-report',['pageTitle'=>'Directors Import Report','result'=>$result],'main');}catch(UserFacingException $e){$this->fail($request,$response,$e->getMessage());}}
    public function report(Request $request,Response $response,int $id):void{try{$result=(new DirectorCsvImportService())->reportById($id);$this->render('staff/directors/import-report',['pageTitle'=>'Directors Import Report','result'=>$result],'main');}catch(UserFacingException $e){$this->fail($request,$response,$e->getMessage());}}
    public function template(Request $request,Response $response):void{while(ob_get_level())ob_end_clean();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="trinova-directors-import-template.csv"');header('Cache-Control: private, no-store');$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,[DirectorCsv::TITLE]);fputcsv($out,DirectorCsv::headers());fclose($out);exit;}
    public function reportCsv(Request $request,Response $response,int $id):void{try{$report=(new DirectorCsvImportService())->reportById($id);while(ob_get_level())ob_end_clean();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="trinova-directors-import-report-'.date('Y-m-d').'.csv"');header('Cache-Control: private, no-store');$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['CSV Row','Director Name','Linked Company/ies','Director Result','Company Link Result','Profile Status','Warnings / Errors']);foreach($report['rows'] as $row)fputcsv($out,[(int)$row['line'],$this->safe((string)($row['data']['name']??'')),$this->safe((string)($row['data']['companies']??'')),$this->safe((string)($row['director_result']??$row['result']??'')),$this->safe((string)($row['link_result']??'')),$this->safe((string)($row['profile_status']??'')),$this->safe(implode('; ',array_merge($row['warnings']??[],$row['errors']??[])))]);fclose($out);exit;}catch(UserFacingException $e){$this->fail($request,$response,$e->getMessage());}}
    private function fail(Request $request,Response $response,string $message):void{if($request->isAjax()){$response->json(['success'=>false,'message'=>$message],422);return;}Session::setFlash('error',$message);$response->redirect('/staff/directors/import');}
    private function safe(string $value):string{$value=str_replace("\0",'',trim($value));return preg_match('/^[\x00-\x20]*[=+\-@]/u',$value)?"'".$value:$value;}
}
