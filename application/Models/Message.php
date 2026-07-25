<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Message extends Model
{
    public function getUnreadCountByClient(int $clientId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total
            FROM messages
            WHERE client_id = :client_id AND read_at IS NULL
        ");
        $stmt->execute(['client_id' => $clientId]);
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function getTotalUnreadCountForStaff(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM messages WHERE read_at IS NULL");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }
}
