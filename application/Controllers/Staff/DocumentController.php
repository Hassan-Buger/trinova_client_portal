<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class DocumentController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('staff/stub', [
            'pageTitle'   => 'Staff Documents',
            'featureName' => 'Document Management',
            'description' => 'Staff document upload to client accounts and tracking will be built in the Sunday Phase.'
        ], 'main');
    }
}
