<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class ProfileController extends Controller
{
    public function aml(Request $request, Response $response): void
    {
        $this->render('client/stub', [
            'pageTitle'   => 'AML Status',
            'featureName' => 'Anti-Money Laundering Verification',
            'description' => 'AML status tracking and verification links will be built in the Sunday Phase.'
        ], 'main');
    }

    public function details(Request $request, Response $response): void
    {
        $this->render('client/stub', [
            'pageTitle'   => 'My Details',
            'featureName' => 'Account Contact Details',
            'description' => 'Contact details overview and staff update request flow will be built in the Sunday Phase.'
        ], 'main');
    }
}
