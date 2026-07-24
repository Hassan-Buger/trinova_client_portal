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
        $this->render('client/stub', [
            'pageTitle'   => 'Upload Documents',
            'featureName' => 'Document Uploads',
            'description' => 'Drag-and-drop file upload with plain-text description field will be built in the Sunday Phase.'
        ], 'main');
    }

    public function myUploads(Request $request, Response $response): void
    {
        $this->render('client/stub', [
            'pageTitle'   => 'My Uploads',
            'featureName' => 'Upload History',
            'description' => 'Client upload history and document status tracking will be built in the Sunday Phase.'
        ], 'main');
    }

    public function trinovaDocs(Request $request, Response $response): void
    {
        $this->render('client/stub', [
            'pageTitle'   => 'Documents from TriNova',
            'featureName' => 'TriNova Documents',
            'description' => 'Shared documents from TriNova team with secure download links will be built in the Sunday Phase.'
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
        if ($userRole === 'client' && (int)$doc['client_id'] !== (int)$clientId) {
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
        readfile($filePath);
        exit;
    }
}
