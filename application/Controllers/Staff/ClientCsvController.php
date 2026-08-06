<?php

namespace Application\Controllers\Staff;

use Application\Config\ClientCsv;
use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Exceptions\UserFacingException;
use Application\Services\ClientCsvImportService;

final class ClientCsvController extends Controller
{
    public function index(Request $request,Response $response):void
    {
        $this->render('staff/clients/import',['pageTitle'=>'Import Business Clients','fields'=>ClientCsv::fields()],'main');
    }

    public function upload(Request $request,Response $response):void
    {
        try{$upload=(new ClientCsvImportService())->upload($_FILES['csv_file']??[]);if(!empty($upload['duplicate'])){$this->render('staff/clients/import-duplicate',['pageTitle'=>'Duplicate Import Prevented','existing'=>$upload['existing']],'main');return;}$this->render('staff/clients/import',['pageTitle'=>'Map CSV Columns','fields'=>ClientCsv::fields(),'upload'=>$upload],'main');}
        catch(UserFacingException $e){$this->userFailure($request,$response,$e->getMessage());}
    }

    public function preview(Request $request,Response $response):void
    {
        try{$body=$request->getBody();$mapping=is_array($_POST['mapping']??null)?$_POST['mapping']:[];$preview=(new ClientCsvImportService())->preview((string)($body['token']??''),$mapping);$this->render('staff/clients/import-preview',['pageTitle'=>'Confirm Client Import','preview'=>$preview,'fields'=>ClientCsv::fields()],'main');}
        catch(UserFacingException $e){$this->userFailure($request,$response,$e->getMessage());}
    }

    public function commit(Request $request,Response $response):void
    {
        try{$result=(new ClientCsvImportService())->commit((string)$request->input('token',''));$this->render('staff/clients/import-report',['pageTitle'=>'Client Import Report','result'=>$result],'main');}
        catch(UserFacingException $e){$this->userFailure($request,$response,$e->getMessage());}
    }

    public function reportCsv(Request $request,Response $response):void
    {
        try{$query=$request->getQueryParams();$service=new ClientCsvImportService();$report=!empty($query['import_id'])?$service->reportById((int)$query['import_id']):$service->report((string)($query['token']??''));while(ob_get_level()>0)ob_end_clean();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="trinova-client-import-report-'.date('Y-m-d').'.csv"');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');$out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");fputcsv($out,['Row','Company','Result','Action','Duplicate Match','Director Names','Placeholders Created','Placeholders Reused','Links Created','Need Details','Warnings','Errors']);foreach($report['rows'] as $row)fputcsv($out,[$row['line'],$this->csvCell($row['data']['client_name']??''),$this->csvCell($this->importResultLabel($row)),$this->csvCell($row['action']??''),$this->csvCell(isset($row['match'])&&$row['match']?($row['match']['field'].'='.$row['match']['value']):''),$this->csvCell(implode('; ',$row['directors']??[])),(int)($row['placeholder_directors_created']??0),(int)($row['placeholder_directors_reused']??0),(int)($row['director_links_created']??0),(int)($row['directors_needing_details']??0),$this->csvCell(implode('; ',$row['warnings']??[])),$this->csvCell(implode('; ',$row['errors']??[]))]);fclose($out);exit;}
        catch(UserFacingException $e){$this->userFailure($request,$response,$e->getMessage());}
    }

    public function showReport(Request $request,Response $response,int $id):void
    {
        try{$result=(new ClientCsvImportService())->reportById($id);$this->render('staff/clients/import-report',['pageTitle'=>'Client Import Report','result'=>$result],'main');}
        catch(UserFacingException $e){$this->userFailure($request,$response,$e->getMessage());}
    }

    private function userFailure(Request $request,Response $response,string $message):void
    {
        if($request->isAjax()){$response->json(['success'=>false,'message'=>$message],422);return;}
        Session::setFlash('error',$message);$response->redirect('/staff/clients/import');
    }

    private function csvCell(string $value): string
    {
        $value=str_replace("\0",'',trim($value));
        return preg_match('/^[\x00-\x20]*[=+\-@]/u',$value)?"'".$value:$value;
    }

    private function importResultLabel(array $row): string
    {
        if (($row['result'] ?? '') === 'failed') return 'Failed';
        return !empty($row['warnings']) ? 'Imported with warnings' : 'Imported';
    }

    public function deleteBatch(Request $request, Response $response): void
    {
        $batchId = (int)($request->input('batch_id', 0) ?: $request->input('import_id', 0) ?: $request->input('id', 0));
        try {
            if ($batchId > 0 && (new ClientCsvImportService())->deleteImportBatch($batchId)) {
                $msg = 'Import batch deleted.';
                if ($request->isAjax()) { $response->json(['success' => true, 'message' => $msg, 'redirect' => '/staff/clients']); return; }
                Session::setFlash('success', $msg);
                $response->redirect('/staff/clients');
                return;
            } else {
                if ($request->isAjax()) { $response->json(['success' => false, 'message' => 'Failed to delete batch.'], 422); return; }
                Session::setFlash('error', 'Failed to delete batch.');
            }
        } catch (UserFacingException $e) {
            $this->userFailure($request, $response, $e->getMessage());
            return;
        }
        $response->redirect('/staff/clients/import');
    }
}
