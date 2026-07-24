<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class UserAdminController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('staff/stub', [
            'pageTitle'   => 'User Management',
            'featureName' => 'Account Lifecycle Management',
            'description' => 'Creating, editing, suspending client and staff accounts via admin panel will be built in the Sunday Phase.'
        ], 'main');
    }
}
