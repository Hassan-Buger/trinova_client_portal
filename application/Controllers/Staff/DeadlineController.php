<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class DeadlineController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('staff/stub', [
            'pageTitle'   => 'Staff Deadlines',
            'featureName' => 'Tax & Compliance Deadlines Management',
            'description' => 'Staff control over client tax deadlines will be built in the Sunday Phase.'
        ], 'main');
    }
}
