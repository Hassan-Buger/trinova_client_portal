<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class MessageController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('client/stub', [
            'pageTitle'   => 'Messages',
            'featureName' => 'Secure Messaging',
            'description' => 'Threaded encrypted messaging between client and staff will be built in the Sunday Phase.'
        ], 'main');
    }
}
