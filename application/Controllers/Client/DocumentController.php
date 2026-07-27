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
use Application\Models\EntityAccess;
use Application\Models\ClientEntity;
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
        $userId=(int)Session::get('user_id');
        $requestId=(int)($request->getQueryParams()['request_id']??0);
        $selectedRequest=$requestId>0?(new \Application\Models\DocumentRequest())->find($requestId):null;
        if($selectedRequest && !(new EntityAccess())->canAccessRecord($userId,$selectedRequest)) $selectedRequest=null;
        $this->render('client/documents/upload', [
            'pageTitle' => 'Upload Documents',
            'entities' => (new EntityAccess())->accessibleEntities($userId),
            'selectedRequest' => $selectedRequest,
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
        $entityId    = (int)($request->getBody()['entity_id'] ?? 0);
        $entityAccess = new EntityAccess();
        $requestRecord = $requestId > 0 ? (new \Application\Models\DocumentRequest())->find($requestId) : null;
        if ($requestRecord) $entityId = (int)$requestRecord['entity_id'];
        if ($entityId <= 0 || !$entityAccess->canAccessEntity((int)$userId, $entityId) || ($requestId > 0 && !$requestRecord)) {
            Session::setFlash('error', 'Choose a valid company or personal record.');
            $response->redirect('/client/documents/upload');
            return;
        }
        $entity = (new ClientEntity())->findById($entityId);
        $recordClientId = (int)$entity['client_id'];
        $scope = (string)$entity['entity_scope'];

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
            'client_id'           => $recordClientId,
            'entity_id'           => $entityId,
            'scope'               => $scope,
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
            $clientName = (new \Application\Models\Client())->findById((int)$clientId)['name'] ?? 'A client';
            foreach ((new User())->getAllStaff() as $staff) {
                if (($staff['status'] ?? '') === 'active') {
                    $notificationModel->create(
                        (int)$staff['id'],
                        'document_upload',
                        'document:' . $docId,
                        'New Document Uploaded',
                        "{$clientName} uploaded a new document: {$filename}",
                        '/staff/documents?client_id=' . (int)$clientId
                    );
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

        $successMessage = "Document '{$filename}' uploaded successfully!";
        if ($request->isAjax()) {
            $response->json([
                'success' => true,
                'message' => $successMessage,
                'redirect' => '/client/documents/my-uploads',
            ]);
            return;
        }

        Session::setFlash('success', $successMessage);
        $response->redirect('/client/documents/my-uploads');
    }

    public function myUploads(Request $request, Response $response): void
    {
        $documents = $this->documentModel->getAccessibleByUserAndDirection((int)Session::get('user_id'), 'client_upload');

        $this->render('client/documents/my-uploads', [
            'pageTitle' => 'My Uploads',
            'documents' => $documents,
        ], 'main');
    }

    public function trinovaDocs(Request $request, Response $response): void
    {
        $documents = $this->documentModel->getAccessibleByUserAndDirection((int)Session::get('user_id'), 'from_trinova');

        $this->render('client/documents/trinova-docs', [
            'pageTitle' => 'Documents from TriNova',
            'documents' => $documents,
        ], 'main');
    }

    public function download(Request $request, Response $response, int $id): void
    {
        // Reuse this established route for browser previews. Some deployments
        // cache their front-controller route table, while this download route
        // is already known to be available and working.
        if ((string)$request->input('preview', '') === '1') {
            $this->view($request, $response, $id);
            return;
        }

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

    public function availability(Request $request, Response $response, int $id): void
    {
        $doc = $this->documentModel->find($id);
        if (!$doc) {
            $response->json(['success' => false, 'message' => 'This document record no longer exists.'], 404);
            return;
        }

        $role = (string)Session::get('role');
        $userId = (int)Session::get('user_id', 0);
        if ($role !== 'staff' && ($role !== 'client' || $userId <= 0 || !(new EntityAccess())->canAccessRecord($userId, $doc))) {
            $response->json(['success' => false, 'message' => 'You do not have permission to view this document.'], 403);
            return;
        }

        $filePath = App::get('storage_dir') . '/uploads/' . $doc['stored_path'];
        if (!is_file($filePath)) {
            $response->json(['success' => false, 'message' => 'This file is unavailable because it is missing from secure storage. Please upload it again or contact an administrator.'], 404);
            return;
        }

        $response->json(['success' => true]);
    }

    private function resolveAuthorizedDocument(Response $response, int $id): array
    {
        $doc = $this->documentModel->find($id);
        if (!$doc) {
            $response->setStatusCode(404);
            die('Document Not Found.');
        }

        $userRole = (string)Session::get('role');
        $userId = (int)Session::get('user_id', 0);
        if ($userRole !== 'staff' && ($userRole !== 'client' || $userId <= 0 || !(new EntityAccess())->canAccessRecord($userId, $doc))) {
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
