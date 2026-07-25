<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\DocumentRequest;
use Application\Services\AuditService;

class RequestController extends Controller
{
    private DocumentRequest $requestModel;
    private Client $clientModel;

    public function __construct()
    {
        $this->requestModel = new DocumentRequest();
        $this->clientModel  = new Client();
    }

    public function index(Request $request, Response $response): void
    {
        $requests = $this->requestModel->getAllWithDetails();
        $clients  = $this->clientModel->getAllWithUsers();

        $this->render('staff/requests/index', [
            'pageTitle' => 'Document Request Management',
            'requests'  => $requests,
            'clients'   => $clients,
        ], 'main');
    }

    public function create(Request $request, Response $response): void
    {
        $staffUserId = Session::get('user_id');
        $body        = $request->getBody();
        $clientId    = (int)($body['client_id'] ?? 0);
        $title       = trim($body['title'] ?? '');
        $description = trim($body['description'] ?? '');
        $dueDate     = trim($body['due_date'] ?? '');

        if ($clientId <= 0 || empty($title) || empty($dueDate)) {
            Session::setFlash('error', 'Client, request title, and due date are required.');
            $response->redirect('/staff/requests');
            return;
        }

        $reqId = $this->requestModel->create([
            'client_id'          => $clientId,
            'created_by_user_id' => $staffUserId,
            'title'              => $title,
            'description'        => $description,
            'due_date'           => $dueDate,
            'status'             => 'Awaiting Client',
        ]);

        AuditService::log('request_created', 'document_requests', $reqId);
        Session::setFlash('success', "Document request '{$title}' issued successfully.");
        $response->redirect('/staff/requests');
    }

    public function updateStatus(Request $request, Response $response): void
    {
        $body      = $request->getBody();
        $requestId = (int)($body['request_id'] ?? 0);
        $status    = trim($body['status'] ?? '');

        $validStatuses = ['Awaiting Client', 'Uploaded', 'Under Review', 'Completed'];
        if ($requestId > 0 && in_array($status, $validStatuses, true)) {
            $this->requestModel->updateStatus($requestId, $status);
            AuditService::log('request_status_updated', 'document_requests', $requestId);
            Session::setFlash('success', 'Request status updated.');
        }

        $response->redirect('/staff/requests');
    }
}
