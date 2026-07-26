<?php

namespace Application\Controllers;

use Application\Core\Controller;
use Application\Core\Request;
use Application\Core\Response;
use Application\Core\Session;
use Application\Models\Notification;
use Application\Models\Client;
use Application\Models\Document;
use Application\Models\DocumentRequest;

class NotificationController extends Controller
{
    private Notification $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
    }

    public function feed(Request $request, Response $response): void
    {
        header('Cache-Control: private, no-store, max-age=0');
        $userId = (int)Session::get('user_id', 0);
        $role = (string)Session::get('role', 'client');
        $items = array_map(
            fn(array $item): array => $this->serialise($item, $role),
            $this->notificationModel->getRecentByUser($userId)
        );

        $unreadCount = $this->notificationModel->countUnreadByUser($userId);
        $response->json(['success' => true, 'count' => $unreadCount, 'unread_count' => $unreadCount, 'notifications' => $items]);
    }

    public function read(Request $request, Response $response): void
    {
        $id = (int)$request->input('notification_id', 0);
        if ($id <= 0 || !$this->notificationModel->markAsRead($id, (int)Session::get('user_id', 0))) {
            $response->json(['success' => false, 'message' => 'Notification was not found.'], 404);
            return;
        }
        $response->json(['success' => true]);
    }

    public function readAll(Request $request, Response $response): void
    {
        $userId = (int)Session::get('user_id', 0);
        $notificationId = (int)$request->input('notification_id', 0);
        $notificationIds = $request->input('notification_ids', []);
        if ($notificationId > 0) {
            $this->notificationModel->markAsRead($notificationId, $userId);
        } elseif (is_array($notificationIds) && $notificationIds) {
            $this->notificationModel->markManyAsRead($notificationIds, $userId);
        } else {
            $response->json(['success' => false, 'message' => 'No notifications were selected.'], 422);
            return;
        }
        $unreadCount = $this->notificationModel->countUnreadByUser($userId);
        $response->json(['success' => true, 'count' => $unreadCount, 'unread_count' => $unreadCount]);
    }

    private function serialise(array $item, string $role): array
    {
        $related = (string)($item['related_entity'] ?? '');
        $parts = explode(':', $related, 2);
        $relatedId = isset($parts[1]) ? (int)$parts[1] : 0;
        $type = (string)$item['type'];

        $details = match ($type) {
            'document_request' => $this->requestDetails($relatedId),
            'document_received' => $this->documentReceivedDetails($relatedId),
            'document_upload', 'client_document_uploaded' => $this->uploadDetails($relatedId),
            'message_received' => $role === 'staff'
                ? ['New Client Message', 'You received a new client message', '/staff/messages' . ($relatedId > 0 ? '?client_id=' . $relatedId : '')]
                : ['New Message', 'You received a new message from TriNova', '/client/messages'],
            default => ['Portal Update', 'You have a new portal update', $role === 'staff' ? '/staff/dashboard' : '/client/dashboard'],
        };

        return [
            'id' => (int)$item['id'],
            'type' => $type,
            'title' => (string)($item['title'] ?? '') ?: $details[0],
            'message' => (string)($item['message'] ?? '') ?: $details[1],
            'url' => (string)($item['action_url'] ?? '') ?: $details[2],
            'is_read' => empty($item['is_unread']),
            'created_at' => date(DATE_ATOM, strtotime((string)$item['created_at'])),
        ];
    }

    private function requestDetails(int $id): array
    {
        $request = $id > 0 ? (new DocumentRequest())->find($id) : null;
        $title = trim((string)($request['title'] ?? ''));
        return ['New Document Request', $title !== '' ? "Staff requested: {$title}" : 'Staff requested a new document.', '/client/requests'];
    }

    private function uploadDetails(int $id): array
    {
        $document = $id > 0 ? (new Document())->find($id) : null;
        $filename = (string)($document['filename'] ?? 'a document');
        $client = $document ? (new Client())->findById((int)$document['client_id']) : null;
        $name = (string)($client['name'] ?? 'A client');
        return ['New Document Uploaded', "{$name} uploaded a new document: {$filename}", '/staff/documents' . ($document ? '?client_id=' . (int)$document['client_id'] : '')];
    }

    private function documentReceivedDetails(int $id): array
    {
        $document = $id > 0 ? (new Document())->find($id) : null;
        $filename = (string)($document['filename'] ?? 'a new document');
        return ['New Document Available', "A new document has been uploaded to your account: {$filename}", '/client/documents/trinova'];
    }
}
