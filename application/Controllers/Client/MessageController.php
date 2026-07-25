<?php

namespace Application\Controllers\Client;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Message;
use Application\Models\Notification;
use Application\Models\User;
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

    public function feed(Request $request, Response $response): void
    {
        $clientId = (int) Session::get('client_id', 0);
        if ($clientId <= 0) {
            $response->json(['success' => false, 'message' => 'Client session is unavailable.'], 403);
        }

        $afterId = max(0, (int) ($request->getQueryParams()['after_id'] ?? 0));
        $this->messageModel->markAsReadForRecipient($clientId, 'client');
        $response->json([
            'success' => true,
            'messages' => $this->serialiseMessages($this->messageModel->getByClientAfterId($clientId, $afterId)),
        ]);
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
            try {
                $notificationModel = new Notification();
                foreach ((new User())->getAllStaff() as $staff) {
                    if (($staff['status'] ?? '') === 'active') {
                        $notificationModel->create((int)$staff['id'], 'message_received', 'client:' . (int)$clientId);
                    }
                }
            } catch (\Throwable $e) {
                // The message remains successful if notification storage is unavailable.
            }
            NotificationService::sendPromptEmail('staff@trinova.co.uk', 'New Client Message Received', 'A new message was posted by client on TriNova Portal.');
            Session::setFlash('success', 'Message sent.');
        } elseif ($request->isAjax()) {
            $response->json(['success' => false, 'message' => 'Please enter a message.'], 422);
        }

        if ($request->isAjax()) {
            $response->json(['success' => true, 'message' => 'Message sent.']);
        }

        $response->redirect('/client/messages');
    }

    private function serialiseMessages(array $messages): array
    {
        return array_map(static fn(array $message): array => [
            'id' => (int) $message['id'],
            'sender_name' => $message['sender_name'] ?? 'User',
            'sender_role' => $message['sender_role'] ?? '',
            'body' => $message['body'] ?? '',
            'created_at' => date(DATE_ATOM, strtotime($message['created_at'])),
            'read_at' => $message['read_at'] ?? null,
        ], $messages);
    }
}
