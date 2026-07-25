<?php

namespace Application\Controllers\Staff;

use Application\Config\App;
use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\Document;
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
        $documents = $this->documentModel->getAllWithDetails();
        $clients   = $this->clientModel->getAllWithUsers();

        $this->render('staff/documents/index', [
            'pageTitle' => 'Document Management',
            'documents' => $documents,
            'clients'   => $clients,
        ], 'main');
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
        Session::setFlash('success', "Document '{$filename}' dispatched to client account successfully.");
        $response->redirect('/staff/documents');
    }
}
