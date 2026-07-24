<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class RequestController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('staff/stub', [
            'pageTitle'   => 'Staff Requests',
            'featureName' => 'Document Request Management',
            'description' => 'Issuing and updating document requests will be built in the Sunday Phase.'
        ], 'main');
    }
}
