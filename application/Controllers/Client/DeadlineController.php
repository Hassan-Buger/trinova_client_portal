<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class DeadlineController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('client/stub', [
            'pageTitle'   => 'Important Dates',
            'featureName' => 'Compliance Deadlines',
            'description' => 'VAT, Payroll, Accounts, and Tax deadlines tracker will be built in the Sunday Phase.'
        ], 'main');
    }
}
