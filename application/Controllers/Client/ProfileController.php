<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\ClientEntity;
use Application\Models\Message;

class ProfileController extends Controller
{
    private Client $clientModel;
    private ClientEntity $entityModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->entityModel = new ClientEntity();
    }

    public function aml(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $client   = $clientId ? $this->clientModel->findById($clientId) : null;

        $this->render('client/profile/aml', [
            'pageTitle' => 'AML Compliance Status',
            'client'    => $client,
        ], 'main');
    }

    public function details(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $client   = $clientId ? $this->clientModel->findById($clientId) : null;
        $entities = (new \Application\Models\EntityAccess())->accessibleEntities((int)Session::get('user_id'));

        $this->render('client/profile/details', [
            'pageTitle' => 'My Account Details',
            'client'    => $client,
            'entities'  => $entities,
        ], 'main');
    }

    public function requestUpdate(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $userId   = Session::get('user_id');
        $notes    = trim($request->getBody()['update_notes'] ?? '');

        if ($clientId && $userId && !empty($notes)) {
            $entities=(new \Application\Models\EntityAccess())->accessibleEntities((int)$userId);
            $entity=$entities[0]??null;
            if(!$entity){ Session::setFlash('error','No accessible record is available.'); $response->redirect('/client/profile/details'); return; }
            $msgModel = new Message();
            $msgModel->create([
                'client_id' => (int)$entity['client_id'],
                'entity_id' => (int)$entity['id'],
                'scope' => $entity['entity_scope'],
                'sender_id' => $userId,
                'body'      => '[Detail Update Request]: ' . $notes,
            ]);
            Session::setFlash('success', 'Your detail update request has been submitted to the team.');
        }

        $response->redirect('/client/profile/details');
    }
}
