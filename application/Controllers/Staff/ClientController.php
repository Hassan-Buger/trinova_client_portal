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
        $deadlines = $this->deadlineModel->getUpcomingByClient($id);

        $this->render('staff/clients/show', [
            'pageTitle'   => "Client: {$client['name']}",
            'client'      => $client,
            'entities'    => $entities,
            'outstanding' => $outstanding,
            'deadlines'   => $deadlines,
        ], 'main');
    }
}
