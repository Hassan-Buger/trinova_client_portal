<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\ClientEntity;
use Application\Models\Deadline;
use Application\Models\Document;
use Application\Models\DocumentRequest;
use Application\Models\Meeting;
use Application\Models\Message;
use Application\Models\User;
use Application\Services\AuditService;
use Application\Services\ClientCsvImportService;

final class TrashController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $clientModel = new Client();
        $entityModel = new ClientEntity();
        $docModel = new Document();
        $reqModel = new DocumentRequest();
        $msgModel = new Message();
        $deadlineModel = new Deadline();
        $meetingModel = new Meeting();
        $userModel = new User();
        $csvService = new ClientCsvImportService();

        $deletedItems = [
            'clients'   => $clientModel->getSoftDeleted(),
            'entities'  => $entityModel->getSoftDeleted(),
            'documents' => $docModel->getSoftDeleted(),
            'requests'  => $reqModel->getSoftDeleted(),
            'messages'  => $msgModel->getSoftDeleted(),
            'deadlines' => $deadlineModel->getSoftDeleted(),
            'meetings'  => $meetingModel->getSoftDeleted(),
            'users'     => $userModel->getSoftDeleted(),
            'batches'   => $csvService->getSoftDeletedBatches(),
        ];

        $totalDeleted = array_sum(array_map('count', $deletedItems));

        $this->render('staff/trash/index', [
            'pageTitle'    => 'Trash / Soft-Deleted Items',
            'deletedItems' => $deletedItems,
            'totalDeleted' => $totalDeleted,
        ], 'main');
    }

    public function restore(Request $request, Response $response): void
    {
        $type = (string) $request->input('target_type', '');
        $id = (int) $request->input('target_id', 0);

        if ($id <= 0 || empty($type)) {
            $this->redirectOrJson($request, $response, false, 'Invalid restore parameters.');
            return;
        }

        $restored = false;
        switch ($type) {
            case 'client':
                $restored = (new Client())->restore($id);
                break;
            case 'entity':
                $restored = (new ClientEntity())->restore($id);
                break;
            case 'document':
                $restored = (new Document())->restore($id);
                break;
            case 'request':
                $restored = (new DocumentRequest())->restore($id);
                break;
            case 'message':
                $restored = (new Message())->restore($id);
                break;
            case 'deadline':
                $restored = (new Deadline())->restore($id);
                break;
            case 'meeting':
                $restored = (new Meeting())->restore($id);
                break;
            case 'user':
                $restored = (new User())->restore($id);
                break;
            case 'csv_batch':
                $restored = (new ClientCsvImportService())->restoreImportBatch($id);
                break;
        }

        if ($restored) {
            AuditService::log('record_restored', $type, $id, null, ['type' => $type, 'id' => $id]);
            $this->redirectOrJson($request, $response, true, 'Item restored successfully.');
        } else {
            $this->redirectOrJson($request, $response, false, 'Failed to restore item.');
        }
    }

    public function bulkRestore(Request $request, Response $response): void
    {
        $type = (string) $request->input('target_type', '');
        $rawIds = $request->input('ids', []);
        $ids = is_array($rawIds) ? array_map('intval', array_filter($rawIds)) : [];

        if (empty($ids) || empty($type)) {
            $this->redirectOrJson($request, $response, false, 'No items selected for restore.');
            return;
        }

        $count = 0;
        switch ($type) {
            case 'client':
                $count = (new Client())->bulkRestore($ids);
                break;
            case 'entity':
                $count = (new ClientEntity())->bulkRestore($ids);
                break;
            case 'document':
                $count = (new Document())->bulkRestore($ids);
                break;
            case 'request':
                $count = (new DocumentRequest())->bulkRestore($ids);
                break;
            case 'message':
                $count = (new Message())->bulkRestore($ids);
                break;
            case 'deadline':
                $count = (new Deadline())->bulkRestore($ids);
                break;
            case 'meeting':
                $count = (new Meeting())->bulkRestore($ids);
                break;
            case 'user':
                $count = (new User())->bulkRestore($ids);
                break;
        }

        if ($count > 0) {
            AuditService::log('bulk_records_restored', $type, null, null, ['count' => $count, 'type' => $type, 'ids' => $ids]);
            $this->redirectOrJson($request, $response, true, "{$count} items restored successfully.");
        } else {
            $this->redirectOrJson($request, $response, false, 'No items were restored.');
        }
    }

    private function redirectOrJson(Request $request, Response $response, bool $success, string $message): void
    {
        if ($request->isAjax()) {
            $response->json(['success' => $success, 'message' => $message], $success ? 200 : 422);
            return;
        }
        Session::setFlash($success ? 'success' : 'error', $message);
        $response->redirect('/staff/trash');
    }
}
