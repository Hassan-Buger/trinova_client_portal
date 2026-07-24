<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class RequestController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('client/stub', [
            'pageTitle'   => 'Document Requests',
            'featureName' => 'Outstanding Document Requests',
            'description' => 'Document requests lifecycle management will be built in the Sunday Phase.'
        ], 'main');
    }
}
