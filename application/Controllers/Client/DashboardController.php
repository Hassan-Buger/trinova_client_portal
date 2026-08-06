<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\Deadline;
use Application\Models\DocumentRequest;
use Application\Models\Message;

class DashboardController extends Controller
{
    private Client $clientModel;
    private DocumentRequest $requestModel;
    private Deadline $deadlineModel;
    private Message $messageModel;

    public function __construct()
    {
        $this->clientModel = new Client();
        $this->requestModel = new DocumentRequest();
        $this->deadlineModel = new Deadline();
        $this->messageModel = new Message();
    }

    public function index(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $client = $clientId ? $this->clientModel->findById($clientId) : null;

        $userId=(int)Session::get('user_id');
        $requests = $this->requestModel->getAccessibleByUser($userId, true);
        $deadlines = $this->deadlineModel->getAccessibleByUser($userId);
        $unreadMessages = $this->messageModel->getUnreadCountByUser($userId);

        $this->render('client/dashboard', [
            'pageTitle'       => 'Dashboard',
            'userName'        => Session::get('user_name', 'Client'),
            'client'          => $client,
            'requests'        => $requests,
            'deadlines'       => $deadlines,
            'unreadMessages'  => $unreadMessages,
            'amlStatus'       => $client['aml_status'] ?? 'Complete',
        ], 'main');
    }
}
