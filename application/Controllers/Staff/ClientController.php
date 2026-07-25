<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Models\Client;
use Application\Models\ClientEntity;
use Application\Models\Deadline;
use Application\Models\DocumentRequest;

class ClientController extends Controller
{
    private Client $clientModel;
    private ClientEntity $entityModel;
    private DocumentRequest $requestModel;
    private Deadline $deadlineModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->entityModel = new ClientEntity();
        $this->requestModel = new DocumentRequest();
        $this->deadlineModel = new Deadline();
    }

    public function index(Request $request, Response $response): void
    {
        $query = $request->getQueryParams();
        $search = trim((string) ($query['q'] ?? ''));
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = (int) ($query['per_page'] ?? 10);
        if (!in_array($perPage, [10, 20, 50], true)) {
            $perPage = 10;
        }
        $pagination = $this->clientModel->paginate($search, $page, $perPage);

        $this->render('staff/clients/index', [
            'pageTitle' => 'Clients Overview',
            'clients'   => $pagination['items'],
            'search' => $search,
            'pagination' => $pagination,
        ], 'main');
    }

    public function show(Request $request, Response $response, int $id): void
    {
        $client = $this->clientModel->findById($id);
        if (!$client) {
            $response->setStatusCode(404);
            die('Client record not found.');
        }

        $entities = $this->entityModel->getByClientId($id);
        $outstanding = $this->requestModel->getOutstandingByClientId($id);
        $deadlines = $this->deadlineModel->getAllByClient($id);

        $auditModel = new \Application\Models\AuditLog();
        $auditLogs = $auditModel->getByUserId((int)$client['user_id'], 20);

        $documentModel = new \Application\Models\Document();
        $documents = $documentModel->getByClientId($id);

        $meetingModel = new \Application\Models\Meeting();
        $meetings = $meetingModel->getByClientId($id);

        $this->render('staff/clients/show', [
            'pageTitle'   => "Client: {$client['name']}",
            'client'      => $client,
            'entities'    => $entities,
            'outstanding' => $outstanding,
            'deadlines'   => $deadlines,
            'auditLogs'   => $auditLogs,
            'documents'   => $documents,
            'meetings'    => $meetings,
        ], 'main');
    }

    public function create(Request $request, Response $response): void
    {
        $body    = $request->getBody();
        $name    = trim($body['name'] ?? '');
        $email   = trim($body['email'] ?? '');
        $phone   = trim($body['phone'] ?? '');
        $address = trim($body['address'] ?? '');
        $aml     = trim($body['aml_status'] ?? 'Action Required');

        if (empty($name) || empty($email)) {
            \Application\Core\Session::setFlash('error', 'Client name and email are required.');
            $response->redirect('/staff/clients');
            return;
        }

        $userModel = new \Application\Models\User();
        if ($userModel->findByEmail($email)) {
            \Application\Core\Session::setFlash('error', "User email '{$email}' already registered.");
            $response->redirect('/staff/clients');
            return;
        }

        $dummyHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $userId = $userModel->create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $dummyHash,
            'role'          => 'client',
            'status'        => 'pending_activation',
        ]);

        $activationToken = bin2hex(random_bytes(32));
        $userModel->storeActivationToken($userId, $activationToken);

        $clientId = $this->clientModel->create([
            'user_id'    => $userId,
            'phone'      => $phone,
            'address'    => $address,
            'aml_status' => $aml,
        ]);

        $appUrl = \Application\Config\App::get('url', 'https://white-bison-201906.hostingersite.com');
        $activationLink = rtrim($appUrl, '/') . '/activate?token=' . urlencode($activationToken);

        \Application\Services\AuditService::log('client_created', 'clients', $clientId);
        \Application\Services\NotificationService::sendWelcomeActivationEmail($email, $name, $activationLink);

        \Application\Core\Session::setFlash('success', "Client account for '{$name}' created! A welcome activation email has been dispatched.");
        $response->redirect('/staff/clients');
    }

    public function resetPassword(Request $request, Response $response): void
    {
        $body        = $request->getBody();
        $clientId    = (int)($body['client_id'] ?? 0);
        $newPassword = trim($body['new_password'] ?? '');

        if ($clientId <= 0 || empty($newPassword)) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Client ID and new password are required.'], 400);
                return;
            }
            \Application\Core\Session::setFlash('error', 'Client ID and new password are required.');
            $response->redirect('/staff/clients');
            return;
        }

        $client = $this->clientModel->findById($clientId);
        if (!$client) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Client account not found.'], 404);
                return;
            }
            \Application\Core\Session::setFlash('error', 'Client not found.');
            $response->redirect('/staff/clients');
            return;
        }

        $userModel = new \Application\Models\User();
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $userModel->updatePassword((int)$client['user_id'], $hash);

        \Application\Services\AuditService::log('staff_reset_client_password', 'users', $client['user_id']);
        
        $msg = "Password for client '{$client['name']}' updated successfully.";
        if ($request->isAjax()) {
            $response->json(['success' => true, 'message' => $msg]);
            return;
        }

        \Application\Core\Session::setFlash('success', $msg);
        $response->redirect('/staff/clients/' . $clientId);
    }

    public function delete(Request $request, Response $response): void
    {
        $body     = $request->getBody();
        $clientId = (int)($body['client_id'] ?? 0);

        if ($clientId <= 0) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Invalid client ID.'], 400);
                return;
            }
            \Application\Core\Session::setFlash('error', 'Invalid client ID.');
            $response->redirect('/staff/clients');
            return;
        }

        $client = $this->clientModel->findById($clientId);
        if ($client) {
            $name = $client['name'];
            $this->clientModel->delete($clientId);
            \Application\Services\AuditService::log('client_deleted', 'clients', $clientId);

            $msg = "Client account '{$name}' removed successfully.";
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg]);
                return;
            }
            \Application\Core\Session::setFlash('success', $msg);
        }

        $response->redirect('/staff/clients');
    }

    public function addEntity(Request $request, Response $response): void
    {
        $body        = $request->getBody();
        $clientId    = (int)($body['client_id'] ?? 0);
        $companyName = trim($body['company_name'] ?? '');
        $companyNum  = trim($body['company_number'] ?? '');
        $taxRef      = trim($body['tax_reference'] ?? '');

        if ($clientId > 0 && !empty($companyName)) {
            $this->entityModel->create([
                'client_id'      => $clientId,
                'company_name'   => $companyName,
                'company_number' => $companyNum,
                'tax_reference'  => $taxRef,
            ]);

            \Application\Services\AuditService::log('entity_added', 'client_entities', $clientId);
            \Application\Core\Session::setFlash('success', "Business entity '{$companyName}' linked to client.");
        }

        $response->redirect('/staff/clients/' . $clientId);
    }
}
