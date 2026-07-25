<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Deadline;

class DeadlineController extends Controller
{
    private Deadline $deadlineModel;

    public function __construct()
    {
        $this->deadlineModel = new Deadline();
    }

    public function index(Request $request, Response $response): void
    {
        $clientId  = Session::get('client_id');
        $deadlines = $clientId ? $this->deadlineModel->getAllByClient($clientId) : [];

        $this->render('client/deadlines/index', [
            'pageTitle' => 'Important Dates & Compliance',
            'deadlines' => $deadlines,
        ], 'main');
    }
}
