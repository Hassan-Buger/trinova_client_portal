<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;

class MessageController extends Controller
{
    public function index(Request $request, Response $response): void
    {
        $this->render('staff/stub', [
            'pageTitle'   => 'Staff Messages',
            'featureName' => 'Staff Messaging Inbox',
            'description' => 'Staff-wide client messaging threads will be built in the Sunday Phase.'
        ], 'main');
    }
}
