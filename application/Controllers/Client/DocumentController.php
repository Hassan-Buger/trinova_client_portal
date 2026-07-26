<?php

namespace Application\Controllers\Client;

use Application\Config\App;
use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Document;
use Application\Models\Notification;
use Application\Models\User;
use Application\Services\AuditService;

class DocumentController extends Controller
{
    private Document $documentModel;

    public function __construct()
    {
        $this->documentModel = new Document();
    }

    public function showUpload(Request $request, Response $response): void
    {
        $this->render('client/documents/upload', [
            'pageTitle' => 'Upload Documents',
        ], 'main');
    }

    public function processUpload(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $userId   = Session::get('user_id');

        if (!$clientId || !$userId) {
            Session::setFlash('error', 'Session expired. Please log in again.');
            $response->redirect('/login');
            return;
        }

        if (empty($_FILES['file']['name'])) {
            Session::setFlash('error', 'Please select a file to upload.');
            $response->redirect('/client/documents/upload');
            return;
        }

        $file        = $_FILES['file'];
        $filename    = basename($file['name']);
        $description = trim($request->getBody()['description'] ?? '');
        $requestId   = (int)($request->getBody()['request_id'] ?? 0);

        // Validation: File size limit 25MB
        if ($file['size'] > 25 * 1024 * 1024) {
            Session::setFlash('error', 'File size exceeds maximum allowed limit (25MB).');
            $response->redirect('/client/documents/upload');
            return;
        }

        // Extension check
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'png', 'jpg', 'jpeg', 'zip', 'txt'];
        if (!in_array($ext, $allowed, true)) {
            Session::setFlash('error', 'Disallowed file type. Allowed formats: PDF, DOC, XLS, CSV, Images, ZIP.');
            $response->redirect('/client/documents/upload');
            return;
        }

        $targetDir = App::get('storage_dir') . '/uploads';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetFile = $targetDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            Session::setFlash('error', 'Failed to store uploaded file. Please try again.');
            $response->redirect('/client/documents/upload');
            return;
        }

        $docId = $this->documentModel->create([
            'client_id'           => $clientId,
            'uploaded_by_user_id' => $userId,
            'direction'           => 'client_upload',
            'filename'            => $filename,
            'stored_path'         => $storedName,
            'description'        => $description,
            'status'              => 'Uploaded',
        ]);

        AuditService::log('upload', 'documents', $docId);
        try {
            $notificationModel = new Notification();
            foreach ((new User())->getAllStaff() as $staff) {
                if (($staff['status'] ?? '') === 'active') {
                    $notificationModel->create((int)$staff['id'], 'client_document_uploaded', 'document:' . $docId);
                }
            }
        } catch (\Throwable $e) {
            // Notifications must never prevent a successful document upload.
        }

        // If tied to a document request, update status to Uploaded
        if ($requestId > 0) {
            $reqModel = new \Application\Models\DocumentRequest();
            $reqModel->updateStatus($requestId, 'Uploaded');
        }

        Session::setFlash('success', "Document '{$filename}' uploaded successfully!");
        $response->redirect('/client/documents/my-uploads');
    }

    public function myUploads(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $documents = $clientId ? $this->documentModel->getByClientAndDirection($clientId, 'client_upload') : [];

        $this->render('client/documents/my-uploads', [
            'pageTitle' => 'My Uploads',
            'documents' => $documents,
        ], 'main');
    }

    public function trinovaDocs(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $documents = $clientId ? $this->documentModel->getByClientAndDirection($clientId, 'from_trinova') : [];

        $this->render('client/documents/trinova-docs', [
            'pageTitle' => 'Documents from TriNova',
            'documents' => $documents,
        ], 'main');
    }

    public function download(Request $request, Response $response, int $id): void
    {
        [$doc, $filePath] = $this->resolveAuthorizedDocument($response, $id);

        // Log audit event
        AuditService::log('download', 'documents', $doc['id']);

        // Secure Stream Headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($doc['filename']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        readfile($filePath);
        exit;
    }

    public function view(Request $request, Response $response, int $id): void
    {
        [$doc, $filePath] = $this->resolveAuthorizedDocument($response, $id);
        $extension = strtolower(pathinfo((string)$doc['filename'], PATHINFO_EXTENSION));
        $contentTypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'txt' => 'text/plain; charset=utf-8',
            'csv' => 'text/csv; charset=utf-8',
        ];

        if (!isset($contentTypes[$extension])) {
            $response->redirect('/documents/download/' . $id);
            return;
        }

        AuditService::log('view', 'documents', $doc['id']);
        $safeFilename = str_replace(['"', "\r", "\n"], '', basename((string)$doc['filename']));
        header('Content-Type: ' . $contentTypes[$extension]);
        header('Content-Disposition: inline; filename="' . $safeFilename . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Content-Type-Options: nosniff');
        readfile($filePath);
        exit;
    }

    private function resolveAuthorizedDocument(Response $response, int $id): array
    {
        $doc = $this->documentModel->find($id);
        if (!$doc) {
            $response->setStatusCode(404);
            die('Document Not Found.');
        }

        $userRole = (string)Session::get('role');
        $clientId = (int)Session::get('client_id', 0);
        if ($userRole !== 'staff' && ($userRole !== 'client' || $clientId <= 0 || (int)$doc['client_id'] !== $clientId)) {
            $response->setStatusCode(403);
            die('Access Denied: You do not have permission to access this document.');
        }

        $filePath = App::get('storage_dir') . '/uploads/' . $doc['stored_path'];
        if (!is_file($filePath)) {
            $response->setStatusCode(404);
            die('File artifact missing from storage directory.');
        }

        return [$doc, $filePath];
    }
}
