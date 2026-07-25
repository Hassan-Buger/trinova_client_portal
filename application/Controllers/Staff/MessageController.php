<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\Message;
use Application\Services\AuditService;
use Application\Services\NotificationService;

class MessageController extends Controller
{
    private Message $messageModel;
    private Client $clientModel;

    public function __construct()
    {
        $this->messageModel = new Message();
        $this->clientModel  = new Client();
    }

    public function index(Request $request, Response $response): void
    {
        $clients = $this->clientModel->getAllWithUsers();
        $selectedClientId = (int)($request->getQueryParams()['client_id'] ?? ($clients[0]['id'] ?? 0));

        if ($selectedClientId > 0) {
            $this->messageModel->markAsReadForRecipient($selectedClientId, 'staff');
        }

        $messages = $selectedClientId > 0 ? $this->messageModel->getByClientId($selectedClientId) : [];
        $activeClient = $selectedClientId > 0 ? $this->clientModel->findById($selectedClientId) : null;

        $this->render('staff/messages/index', [
            'pageTitle'        => 'Messages & Communications',
            'clients'          => $clients,
            'selectedClientId' => $selectedClientId,
            'activeClient'     => $activeClient,
            'messages'         => $messages,
        ], 'main');
    }

    public function send(Request $request, Response $response): void
    {
        $body        = $request->getBody();
        $clientId    = (int)($body['client_id'] ?? 0);
        $senderId    = Session::get('user_id');
        $messageText = trim($body['body'] ?? '');

        if ($clientId > 0 && $senderId && !empty($messageText)) {
            $msgId = $this->messageModel->create([
                'client_id' => $clientId,
                'sender_id' => $senderId,
                'body'      => $messageText,
            ]);

            AuditService::log('staff_message_sent', 'messages', $msgId);
            
            $client = $this->clientModel->findById($clientId);
            if ($client && !empty($client['email'])) {
                NotificationService::sendPromptEmail($client['email'], 'New Message from TriNova Accounting', 'A member of the TriNova team has sent you a message on your portal.');
            }

            Session::setFlash('success', 'Message dispatched to client thread.');
        }

        $response->redirect('/staff/messages?client_id=' . $clientId);
    }
}
