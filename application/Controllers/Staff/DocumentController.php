<?php

namespace Application\Controllers\Staff;

use Application\Config\App;
use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\Document;
use Application\Models\Notification;
use Application\Services\AuditService;

class DocumentController extends Controller
{
    private Document $documentModel;
    private Client $clientModel;

    public function __construct()
    {
        $this->documentModel = new Document();
        $this->clientModel   = new Client();
    }

    public function index(Request $request, Response $response): void
    {
        $query = $request->getQueryParams();
        $statuses = $this->documentModel->getStatuses();
        $requestedStatus = trim((string) ($query['status'] ?? ''));
        $filters = [
            'search' => trim((string) ($query['q'] ?? '')),
            'client_id' => max(0, (int) ($query['client_id'] ?? 0)),
            'direction' => in_array(($query['direction'] ?? ''), ['client_upload', 'from_trinova'], true) ? $query['direction'] : '',
            'status' => in_array($requestedStatus, $statuses, true) ? $requestedStatus : '',
            'file_type' => in_array(($query['file_type'] ?? ''), ['pdf', 'word', 'spreadsheet', 'image', 'archive', 'text', 'other'], true) ? $query['file_type'] : '',
            'date_from' => $this->validDate($query['date_from'] ?? '') ? $query['date_from'] : '',
            'date_to' => $this->validDate($query['date_to'] ?? '') ? $query['date_to'] : '',
            'sort' => in_array(($query['sort'] ?? ''), ['newest', 'oldest', 'client_asc', 'filename_asc'], true) ? $query['sort'] : 'newest',
        ];
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = (int) ($query['per_page'] ?? 20);
        if (!in_array($perPage, [10, 20, 50], true)) {
            $perPage = 20;
        }
        $pagination = $this->documentModel->paginateWithDetails($filters, $page, $perPage);
        $clients   = $this->clientModel->getAllWithUsers();

        $this->render('staff/documents/index', [
            'pageTitle' => 'Document Management',
            'documents' => $pagination['items'],
            'clients'   => $clients,
            'statuses' => $statuses,
            'filters' => $filters,
            'pagination' => $pagination,
        ], 'main');
    }

    private function validDate(mixed $value): bool
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    public function upload(Request $request, Response $response): void
    {
        $staffUserId = Session::get('user_id');
        $clientId    = (int)($request->getBody()['client_id'] ?? 0);
        $description = trim($request->getBody()['description'] ?? '');

        if ($clientId <= 0 || empty($_FILES['file']['name'])) {
            Session::setFlash('error', 'Please select a client and choose a file to dispatch.');
            $response->redirect('/staff/documents');
            return;
        }

        $file     = $_FILES['file'];
        $filename = basename($file['name']);
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $targetDir = App::get('storage_dir') . '/uploads';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetFile = $targetDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            Session::setFlash('error', 'Failed to store file in repository.');
            $response->redirect('/staff/documents');
            return;
        }

        $docId = $this->documentModel->create([
            'client_id'           => $clientId,
            'uploaded_by_user_id' => $staffUserId,
            'direction'           => 'from_trinova',
            'filename'            => $filename,
            'stored_path'         => $storedName,
            'description'        => $description,
            'status'              => 'Ready',
        ]);

        AuditService::log('staff_upload', 'documents', $docId);
        try {
            $client = $this->clientModel->findById($clientId);
            if ($client && !empty($client['user_id'])) {
                (new Notification())->create((int)$client['user_id'], 'document_received', 'document:' . $docId);
            }
        } catch (\Throwable $e) {
            // Notifications must never prevent a successful document upload.
        }
        Session::setFlash('success', "Document '{$filename}' dispatched to client account successfully.");
        $response->redirect('/staff/documents');
    }

    public function download(Request $request, Response $response, int $id): void
    {
        $doc = $this->documentModel->find($id);
        if (!$doc) {
            $response->setStatusCode(404);
            die('Document Not Found.');
        }

        $filePath = App::get('storage_dir') . '/uploads/' . $doc['stored_path'];
        if (!file_exists($filePath)) {
            $response->setStatusCode(404);
            die('File artifact missing from storage directory.');
        }

        AuditService::log('download', 'documents', $doc['id']);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($doc['filename']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
