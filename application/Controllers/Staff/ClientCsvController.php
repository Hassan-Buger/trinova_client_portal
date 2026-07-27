<?php

namespace Application\Controllers\Staff;

use Application\Config\ClientCsv;
use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Services\ClientCsvImportService;

final class ClientCsvController extends Controller
{
    public function index(Request $request,Response $response):void
    {
        $this->render('staff/clients/import',['pageTitle'=>'Import Business Clients','fields'=>ClientCsv::fields()],'main');
    }

    public function upload(Request $request,Response $response):void
    {
        try{$upload=(new ClientCsvImportService())->upload($_FILES['csv_file']??[]);$this->render('staff/clients/import',['pageTitle'=>'Map CSV Columns','fields'=>ClientCsv::fields(),'upload'=>$upload],'main');}
        catch(\Throwable $e){Session::setFlash('error',$e instanceof \RuntimeException?$e->getMessage():'The CSV could not be processed.');$response->redirect('/staff/clients/import');}
    }

    public function preview(Request $request,Response $response):void
    {
        try{$body=$request->getBody();$mapping=is_array($_POST['mapping']??null)?$_POST['mapping']:[];$preview=(new ClientCsvImportService())->preview((string)($body['token']??''),$mapping);$this->render('staff/clients/import-preview',['pageTitle'=>'Confirm Client Import','preview'=>$preview,'fields'=>ClientCsv::fields()],'main');}
        catch(\Throwable $e){Session::setFlash('error',$e instanceof \RuntimeException?$e->getMessage():'The import preview could not be generated.');$response->redirect('/staff/clients/import');}
    }

    public function commit(Request $request,Response $response):void
    {
        try{$result=(new ClientCsvImportService())->commit((string)$request->input('token',''));$this->render('staff/clients/import-report',['pageTitle'=>'Client Import Report','result'=>$result],'main');}
        catch(\Throwable $e){Session::setFlash('error',$e instanceof \RuntimeException?$e->getMessage():'The import failed and no partial changes were kept.');$response->redirect('/staff/clients/import');}
    }

    public function reportCsv(Request $request,Response $response):void
    {
        try{$report=(new ClientCsvImportService())->report((string)($request->getQueryParams()['token']??''));while(ob_get_level()>0)ob_end_clean();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="trinova-client-import-report-'.date('Y-m-d').'.csv"');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Row','Company','Result','Action','Duplicate Match','Warnings','Errors']);foreach($report['rows'] as $row)fputcsv($out,[$row['line'],$row['data']['client_name']??'',$row['result']??'',$row['action']??'',isset($row['match'])&&$row['match']?($row['match']['field'].'='.$row['match']['value']):'',implode('; ',$row['warnings']??[]),implode('; ',$row['errors']??[])]);fclose($out);exit;}
        catch(\Throwable $e){Session::setFlash('error','The import report is unavailable or expired.');$response->redirect('/staff/clients/import');}
    }
}
