<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Notification extends Model
{
    private ?bool $hasDetailColumns = null;

    public function getRecentByUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = $this->db->prepare("
            SELECT *, (read_at IS NULL) AS is_unread FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function countUnreadByUser(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL");
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(int $userId, string $type, ?string $relatedEntity = null, ?string $title = null, ?string $message = null, ?string $actionUrl = null): int
    {
        $data = [
            'user_id' => $userId,
            'type' => $type,
            'related_entity' => $relatedEntity,
        ];
        if ($this->supportsDetailColumns()) {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, related_entity, title, message, action_url)
                VALUES (:user_id, :type, :related_entity, :title, :message, :action_url)
            ");
            $data += ['title' => $title, 'message' => $message, 'action_url' => $actionUrl];
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, related_entity)
                VALUES (:user_id, :type, :related_entity)
            ");
        }
        $stmt->execute($data);
        return (int)$this->db->lastInsertId();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE notifications SET read_at = NOW()
            WHERE user_id = :user_id AND read_at IS NULL
        ");
        $stmt->execute(['user_id' => $userId]);
    }

    private function supportsDetailColumns(): bool
    {
        if ($this->hasDetailColumns !== null) return $this->hasDetailColumns;
        try {
            $columns = $this->db->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
            return $this->hasDetailColumns = count(array_intersect(['title', 'message', 'action_url'], $columns)) === 3;
        } catch (\Throwable $e) {
            return $this->hasDetailColumns = false;
        }
    }
}
