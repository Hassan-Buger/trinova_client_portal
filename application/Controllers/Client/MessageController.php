<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Message;
use Application\Services\AuditService;
use Application\Services\NotificationService;

class MessageController extends Controller
{
    private Message $messageModel;

    public function __construct()
    {
        $this->messageModel = new Message();
    }

    public function index(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        if ($clientId) {
            $this->messageModel->markAsReadForRecipient($clientId, 'client');
        }
        $messages = $clientId ? $this->messageModel->getByClientId($clientId) : [];

        $this->render('client/messages/index', [
            'pageTitle' => 'Messages',
            'messages'  => $messages,
        ], 'main');
    }

    public function send(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $senderId = Session::get('user_id');
        $body     = trim($request->getBody()['body'] ?? '');

        if ($clientId && $senderId && !empty($body)) {
            $msgId = $this->messageModel->create([
                'client_id' => $clientId,
                'sender_id' => $senderId,
                'body'      => $body,
            ]);

            AuditService::log('message_sent', 'messages', $msgId);
            NotificationService::sendPromptEmail('staff@trinova.co.uk', 'New Client Message Received', 'A new message was posted by client on TriNova Portal.');
            Session::setFlash('success', 'Message sent.');
        }

        $response->redirect('/client/messages');
    }
}
