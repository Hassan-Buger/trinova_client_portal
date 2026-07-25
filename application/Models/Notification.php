<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Notification extends Model
{
    public function getUnreadByUser(int $userId, int $limit = 20): array
    {
        $limit = max(1, min($limit, 50));
        $stmt = $this->db->prepare("
            SELECT * FROM notifications
            WHERE user_id = :user_id AND read_at IS NULL
            ORDER BY created_at DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $type, ?string $relatedEntity = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, type, related_entity)
            VALUES (:user_id, :type, :related_entity)
        ");
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'related_entity' => $relatedEntity,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE notifications SET read_at = NOW()
            WHERE user_id = :user_id AND read_at IS NULL
        ");
        $stmt->execute(['user_id' => $userId]);
    }
}
