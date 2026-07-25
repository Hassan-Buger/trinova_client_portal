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
        $clients = $this->clientModel->getAllWithUsers();

        $this->render('staff/clients/index', [
            'pageTitle' => 'Clients Overview',
            'clients'   => $clients,
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

        $this->render('staff/clients/show', [
            'pageTitle'   => "Client: {$client['name']}",
            'client'      => $client,
            'entities'    => $entities,
            'outstanding' => $outstanding,
            'deadlines'   => $deadlines,
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

        $hash = password_hash('password123', PASSWORD_BCRYPT);
        $userId = $userModel->create([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $hash,
            'role'          => 'client',
            'status'        => 'active',
        ]);

        $clientId = $this->clientModel->create([
            'user_id'    => $userId,
            'phone'      => $phone,
            'address'    => $address,
            'aml_status' => $aml,
        ]);

        \Application\Services\AuditService::log('client_created', 'clients', $clientId);
        \Application\Services\NotificationService::sendPromptEmail($email, 'Welcome to TriNova Client Portal', "Your client account has been created. Default password: password123");
        \Application\Core\Session::setFlash('success', "Client account '{$name}' created successfully.");
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
