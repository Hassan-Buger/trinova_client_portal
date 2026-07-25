<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\DocumentRequest;

class RequestController extends Controller
{
    private DocumentRequest $requestModel;

    public function __construct()
    {
        $this->requestModel = new DocumentRequest();
    }

    public function index(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $requests = $clientId ? $this->requestModel->getAllByClientId($clientId) : [];

        $this->render('client/requests/index', [
            'pageTitle' => 'Document Requests',
            'requests'  => $requests,
        ], 'main');
    }
}
