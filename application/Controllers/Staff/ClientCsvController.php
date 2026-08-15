<?php

namespace Application\Controllers\Staff;

use Application\Config\ClientCsv;
use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Exceptions\UserFacingException;
use Application\Exceptions\SystemSetupException;
use Application\Services\ClientCsvImportService;
use Application\Services\ErrorHandler;

final class ClientCsvController extends Controller
{
    public function index(Request $request,Response $response):void
    {
        if ($request->input('template') || $request->input('action') === 'template') {
            $this->template($request, $response);
            return;
        }
        $this->render('staff/clients/import',['pageTitle'=>'Import Business Clients','fields'=>ClientCsv::fields()],'main');
    }

    public function upload(Request $request,Response $response):void
    {
        try{$upload=(new ClientCsvImportService())->upload($_FILES['csv_file']??[]);if(!empty($upload['duplicate'])){$this->render('staff/clients/import-duplicate',['pageTitle'=>'Duplicate Import Prevented','existing'=>$upload['existing']],'main');return;}$this->render('staff/clients/import',['pageTitle'=>'Map CSV Columns','fields'=>ClientCsv::fields(),'upload'=>$upload],'main');}
        catch(UserFacingException $e){$this->userFailure($request,$response,$e->getMessage());}
    }

    public function template(Request $request, Response $response): void
    {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="trinova-client-import-template.csv"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'COMPANY NAME',
            'Company Number',
            'UTR',
            'VAT NUMBER',
            'PAYE REF NUMBER',
            'PAYE OFFICE NUMBER',
            'ADDRESS',
            'EMAIL',
            'PHONE',
            'END OF YEAR DATE',
            'ACCOUNTS DEADLINE',
            'CONFIRMATION STATEMENT DATE',
            'VAT RETURN FREQUENCY',
            'VAT QUARTER PATTERN',
            'Director 1',
            'Director 2',
            'Director 3',
            'Director 4',
            'Director 5'
        ], ',', '"', '');
        fputcsv($out, [
            'Trinova Accounting',
            '16469351',
            '1490724673',
            '516859262',
            '',
            '',
            '42 London rd, Stroud, GL5 2AJ',
            'office@trinovaaccounting.co.uk',
            '01453 702030',
            '2026-05-31',
            '2027-02-22',
            '2027-05-21',
            'Quarterly',
            'Jan/Apr/Jul/Oct',
            'Jane Dean',
            'Kirsty Allen',
            'Emma Dean',
            '',
            ''
        ], ',', '"', '');
        fputcsv($out, [
            'Cotswold Garden Landscapes Limited',
            '12303100',
            '9138427415',
            '381307996',
            '',
            '',
            '113 Arrowsmith Drive, Stonehouse, GL10 2QS',
            'cotswoldgardenlandscapes@yahoo.co.uk',
            '07833089296',
            '2026-11-30',
            '2026-08-31',
            '2026-11-06',
            'Quarterly',
            'Feb/May/Aug/Nov',
            'Paul Tabb',
            '',
            '',
            '',
            ''
        ], ',', '"', '');
        fclose($out);
        exit;
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
        try{
            $query=$request->getQueryParams();$service=new ClientCsvImportService();$report=!empty($query['import_id'])?$service->reportById((int)$query['import_id']):$service->report((string)($query['token']??''));
            while(ob_get_level()>0)ob_end_clean();
            header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="trinova-client-import-report-'.date('Y-m-d').'.csv"');header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
            $out=fopen('php://output','wb');fwrite($out,"\xEF\xBB\xBF");
            fputcsv($out,['Row','Company','Result','Action','Duplicate Match','Director Names','Placeholders Created','Placeholders Reused','Links Created','Need Details','Failure Stage','Database State','Database Driver Code','Diagnostic Reference','Warnings','Errors'],',','"','');
            foreach($report['rows'] as $row)fputcsv($out,[$row['line'],$this->csvCell($row['data']['client_name']??''),$this->csvCell($this->importResultLabel($row)),$this->csvCell($row['action']??''),$this->csvCell(isset($row['match'])&&$row['match']?($row['match']['field'].'='.$row['match']['value']):''),$this->csvCell(implode('; ',$row['directors']??[])),(int)($row['placeholder_directors_created']??0),(int)($row['placeholder_directors_reused']??0),(int)($row['director_links_created']??0),(int)($row['directors_needing_details']??0),$this->csvCell($row['failure_stage']??''),$this->csvCell($row['database_state']??''),$this->csvCell($row['database_driver_code']??''),$this->csvCell($row['diagnostic_reference']??''),$this->csvCell(implode('; ',$row['warnings']??[])),$this->csvCell(implode('; ',$row['errors']??[]))],',','"','');
            fclose($out);exit;
        }
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
        if($batchId<1){$this->userFailure($request,$response,'The import batch identifier is missing or invalid.');return;}
        try {
            if ((new ClientCsvImportService())->deleteImportBatch($batchId)) {
                $msg = 'Import batch moved to Trash successfully.';
                if ($request->isAjax()) { $response->json(['success' => true, 'message' => $msg, 'redirect' => '/staff/trash?tab=batches']); return; }
                Session::setFlash('success', $msg);
                $response->redirect('/staff/trash?tab=batches');
                return;
            }
        } catch (UserFacingException $e) {
            $this->userFailure($request, $response, $e->getMessage());
            return;
        } catch (SystemSetupException $e) {
            ErrorHandler::report(new \RuntimeException('Delete import batch '.$batchId.' failed schema validation for user '.(int)Session::get('user_id'),0,$e),$request);
            $message=ErrorHandler::SETUP_MESSAGE;
            if($request->isAjax()){$response->json(['success'=>false,'message'=>$message,'error_code'=>'CSV_DELETE_SCHEMA'],503);return;}
            Session::setFlash('error',$message);$response->redirect('/staff/clients/import');return;
        } catch (\Throwable $e) {
            $reference='CSV-DEL-'.date('YmdHis').'-'.$batchId;
            ErrorHandler::report(new \RuntimeException($reference.' delete import batch failed for user '.(int)Session::get('user_id'),0,$e),$request);
            $message='The import batch could not be deleted. Please try again or contact support with reference '.$reference.'.';
            if($request->isAjax()){$response->json(['success'=>false,'message'=>$message,'error_code'=>$reference],500);return;}
            Session::setFlash('error',$message);$response->redirect('/staff/clients/import');return;
        }
        $response->redirect('/staff/clients/import');
    }
}
