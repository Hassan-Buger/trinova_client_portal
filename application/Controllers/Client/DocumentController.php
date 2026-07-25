<?php

namespace Application\Controllers\Client;

use Application\Config\App;
use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Document;
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
        $doc = $this->documentModel->find($id);
        $clientId = Session::get('client_id');
        $userRole = Session::get('role');

        if (!$doc) {
            $response->setStatusCode(404);
            die('Document Not Found.');
        }

        // Security check: Clients can only download their own documents; Staff has unrestricted access
        if ($userRole === 'client' && $clientId && (int)$doc['client_id'] !== (int)$clientId) {
            $response->setStatusCode(403);
            die('Access Denied: You do not have permission to download this document.');
        }

        $filePath = App::get('storage_dir') . '/uploads/' . $doc['stored_path'];
        if (!file_exists($filePath)) {
            $response->setStatusCode(404);
            die('File artifact missing from storage directory.');
        }

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
}
