<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Notification extends Model
{
    public function getUnreadByUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications
            WHERE user_id = :user_id AND read_at IS NULL
            ORDER BY created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
