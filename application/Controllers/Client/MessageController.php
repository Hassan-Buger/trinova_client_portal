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
        $userId=(int)Session::get('user_id');
        $messages = $this->messageModel->getAccessibleByUser($userId);
        $entities=(new \Application\Models\EntityAccess())->accessibleEntities($userId);

        $this->render('client/messages/index', [
            'pageTitle' => 'Messages',
            'messages'  => $messages,
            'entities'  => $entities,
        ], 'main');
    }

    public function feed(Request $request, Response $response): void
    {
        $userId = (int) Session::get('user_id', 0);
        if ($userId <= 0) {
            $response->json(['success' => false, 'message' => 'Client session is unavailable.'], 403);
        }

        $afterId = max(0, (int) ($request->getQueryParams()['after_id'] ?? 0));
        $response->json([
            'success' => true,
            'messages' => $this->serialiseMessages($this->messageModel->getAccessibleByUser($userId, $afterId)),
        ]);
    }

    public function send(Request $request, Response $response): void
    {
        $clientId = Session::get('client_id');
        $senderId = Session::get('user_id');
        $body     = trim($request->getBody()['body'] ?? '');
        $entityId = (int)($request->getBody()['entity_id'] ?? 0);
        $access=new \Application\Models\EntityAccess();
        $entities=$access->accessibleEntities((int)$senderId);
        if($entityId<=0) $entityId=(int)($entities[0]['id']??0);
        $entity=(new \Application\Models\ClientEntity())->findById($entityId);

        if ($clientId && $senderId && !empty($body) && $entity && $access->canAccessEntity((int)$senderId,$entityId)) {
            $msgId = $this->messageModel->create([
                'client_id' => (int)$entity['client_id'],
                'entity_id' => $entityId,
                'scope' => $entity['entity_scope'],
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

    public function delete(Request $request, Response $response): void
    {
        $msgId = (int)($request->input('message_id', 0) ?: $request->input('id', 0));
        $userId = (int)Session::get('user_id');

        $msg = $msgId > 0 ? $this->messageModel->find($msgId) : null;

        if (!$msg || (int)$msg['sender_id'] !== $userId) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'You can only delete messages you sent.'], 403);
                return;
            }
            Session::setFlash('error', 'You can only delete messages you sent.');
            $response->redirect('/client/messages');
            return;
        }

        if ($this->messageModel->softDelete($msgId)) {
            AuditService::log('message_deleted', 'messages', $msgId);
            $flash = 'Message deleted.';
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $flash]);
                return;
            }
            Session::setFlash('success', $flash);
        } else {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Failed to delete message.'], 422);
                return;
            }
            Session::setFlash('error', 'Failed to delete message.');
        }
        $response->redirect('/client/messages');
    }
}
