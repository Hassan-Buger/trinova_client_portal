<?php

namespace Application\Controllers\Staff;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Client;
use Application\Models\Message;
use Application\Models\Notification;
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
        $entities=(new \Application\Models\ClientEntity())->getAllWithClient();
        $activeClient = $selectedClientId > 0 ? $this->clientModel->findById($selectedClientId) : null;

        $this->render('staff/messages/index', [
            'pageTitle'        => 'Messages & Communications',
            'clients'          => $clients,
            'selectedClientId' => $selectedClientId,
            'activeClient'     => $activeClient,
            'messages'         => $messages,
            'entities'         => $entities,
        ], 'main');
    }

    public function feed(Request $request, Response $response): void
    {
        $clientId = (int) ($request->getQueryParams()['client_id'] ?? 0);
        if ($clientId <= 0 || !$this->clientModel->findById($clientId)) {
            $response->json(['success' => false, 'message' => 'Client thread was not found.'], 404);
        }

        $afterId = max(0, (int) ($request->getQueryParams()['after_id'] ?? 0));
        $this->messageModel->markAsReadForRecipient($clientId, 'staff');
        $response->json([
            'success' => true,
            'messages' => $this->serialiseMessages($this->messageModel->getByClientAfterId($clientId, $afterId)),
        ]);
    }

    public function send(Request $request, Response $response): void
    {
        $body        = $request->getBody();
        $clientId    = (int)($body['client_id'] ?? 0);
        $senderId    = Session::get('user_id');
        $messageText = trim($body['body'] ?? '');
        $entityId=(int)($body['entity_id']??0);
        $entity=$entityId ? (new \Application\Models\ClientEntity())->findById($entityId) : null;
        if(!$entity && $clientId){$owned=(new \Application\Models\ClientEntity())->getByClientId($clientId);$entity=$owned[0]??null;$entityId=(int)($entity['id']??0);}
        if($entity) $clientId=(int)$entity['client_id'];

        if ($clientId > 0 && $senderId && !empty($messageText) && $entity) {
            $msgId = $this->messageModel->create([
                'client_id' => (int)$entity['client_id'],
                'entity_id' => $entityId,
                'scope' => $entity['entity_scope'],
                'sender_id' => $senderId,
                'body'      => $messageText,
            ]);

            AuditService::log('staff_message_sent', 'messages', $msgId);
            
            $client = $this->clientModel->findById($clientId);
            try {
                if ($client && !empty($client['user_id'])) {
                    (new Notification())->create((int)$client['user_id'], 'message_received', 'message:' . $msgId);
                }
            } catch (\Throwable $e) {
                // The message remains successful if notification storage is unavailable.
            }
            if ($client && !empty($client['email'])) {
                NotificationService::sendPromptEmail($client['email'], 'New Message from TriNova Accounting', 'A member of the TriNova team has sent you a message on your portal.');
            }

            Session::setFlash('success', 'Message dispatched to client thread.');
        } elseif ($request->isAjax()) {
            $response->json(['success' => false, 'message' => 'Choose a valid client and enter a message.'], 422);
        }

        if ($request->isAjax()) {
            $response->json(['success' => true, 'message' => 'Message dispatched to client thread.']);
        }

        $response->redirect('/staff/messages?client_id=' . $clientId);
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
        $clientId = (int)($request->input('client_id', 0));
        if ($msgId > 0 && $this->messageModel->softDelete($msgId)) {
            AuditService::log('message_deleted', 'messages', $msgId);
            $msg = 'Message deleted.';
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg]);
                return;
            }
            Session::setFlash('success', $msg);
        } else {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Failed to delete message.'], 422);
                return;
            }
            Session::setFlash('error', 'Failed to delete message.');
        }
        $response->redirect('/staff/messages' . ($clientId ? '?client_id=' . $clientId : ''));
    }

    public function deleteThread(Request $request, Response $response): void
    {
        $clientId = (int)($request->input('client_id', 0));
        $threadId = (int)($request->input('thread_id', 1));
        if ($clientId > 0 && $this->messageModel->softDeleteThread($threadId, $clientId)) {
            AuditService::log('message_thread_deleted', 'messages', null, null, ['client_id' => $clientId, 'thread_id' => $threadId]);
            $msg = 'Entire message thread deleted.';
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg]);
                return;
            }
            Session::setFlash('success', $msg);
        } else {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'Failed to delete message thread.'], 422);
                return;
            }
            Session::setFlash('error', 'Failed to delete message thread.');
        }
        $response->redirect('/staff/messages');
    }

    public function bulkDelete(Request $request, Response $response): void
    {
        $rawIds = $request->input('ids', []);
        $ids = is_array($rawIds) ? array_map('intval', array_filter($rawIds)) : [];
        $clientId = (int)($request->input('client_id', 0));

        if (empty($ids)) {
            if ($request->isAjax()) {
                $response->json(['success' => false, 'message' => 'No messages selected for deletion.'], 422);
                return;
            }
            Session::setFlash('error', 'No messages selected for deletion.');
            $response->redirect('/staff/messages' . ($clientId ? '?client_id=' . $clientId : ''));
            return;
        }

        $count = $this->messageModel->bulkSoftDelete($ids);
        if ($count > 0) {
            AuditService::log('message_bulk_deleted', 'messages', null, null, ['count' => $count, 'ids' => $ids]);
            $msg = "{$count} message(s) deleted.";
            if ($request->isAjax()) {
                $response->json(['success' => true, 'message' => $msg]);
                return;
            }
            Session::setFlash('success', $msg);
        }
        $response->redirect('/staff/messages' . ($clientId ? '?client_id=' . $clientId : ''));
    }
}
