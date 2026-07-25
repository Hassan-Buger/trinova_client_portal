<?php

namespace Application\Controllers;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Notification;

class NotificationController extends Controller
{
    private Notification $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
    }

    public function feed(Request $request, Response $response): void
    {
        $userId = (int)Session::get('user_id', 0);
        $role = (string)Session::get('role', 'client');
        $items = array_map(
            fn(array $item): array => $this->serialise($item, $role),
            $this->notificationModel->getUnreadByUser($userId)
        );

        $response->json(['success' => true, 'count' => count($items), 'notifications' => $items]);
    }

    public function readAll(Request $request, Response $response): void
    {
        $this->notificationModel->markAllAsRead((int)Session::get('user_id', 0));
        $response->json(['success' => true, 'count' => 0]);
    }

    private function serialise(array $item, string $role): array
    {
        $related = (string)($item['related_entity'] ?? '');
        $parts = explode(':', $related, 2);
        $relatedId = isset($parts[1]) ? (int)$parts[1] : 0;
        $type = (string)$item['type'];

        $details = match ($type) {
            'document_received' => ['A new document is ready for you', '/client/documents/trinova'],
            'client_document_uploaded' => ['A client uploaded a new document', '/staff/documents'],
            'message_received' => $role === 'staff'
                ? ['You received a new client message', '/staff/messages' . ($relatedId > 0 ? '?client_id=' . $relatedId : '')]
                : ['You received a new message from TriNova', '/client/messages'],
            default => ['You have a new portal update', $role === 'staff' ? '/staff/dashboard' : '/client/dashboard'],
        };

        return [
            'id' => (int)$item['id'],
            'type' => $type,
            'message' => $details[0],
            'url' => $details[1],
            'created_at' => date(DATE_ATOM, strtotime((string)$item['created_at'])),
        ];
    }
}
