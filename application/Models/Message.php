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

    public function getUnreadCountByUser(int $userId): int
    {
        $stmt=$this->db->prepare("SELECT COUNT(DISTINCT m.id) FROM messages m JOIN client_entities e ON e.id=m.entity_id JOIN clients c ON c.id=e.client_id LEFT JOIN entity_directors ed ON ed.entity_id=e.id AND ed.user_id=:director_user JOIN users sender ON sender.id=m.sender_id WHERE m.read_at IS NULL AND sender.role='staff' AND ((m.scope='company' AND ed.user_id IS NOT NULL) OR (m.scope='personal' AND c.user_id=:owner_user))");
        $stmt->execute(['director_user'=>$userId,'owner_user'=>$userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getAccessibleByUser(int $userId, int $afterId=0): array
    {
        $stmt=$this->db->prepare("SELECT DISTINCT m.*,u.name AS sender_name,u.role AS sender_role,e.company_name AS entity_name FROM messages m LEFT JOIN users u ON u.id=m.sender_id JOIN client_entities e ON e.id=m.entity_id JOIN clients c ON c.id=e.client_id LEFT JOIN entity_directors ed ON ed.entity_id=e.id AND ed.user_id=:director_user WHERE m.id>:after_id AND ((m.scope='company' AND ed.user_id IS NOT NULL) OR (m.scope='personal' AND c.user_id=:owner_user)) ORDER BY m.id ASC LIMIT 100");
        $stmt->execute(['director_user'=>$userId,'owner_user'=>$userId,'after_id'=>$afterId]);
        return $stmt->fetchAll();
    }

    public function getTotalUnreadCountForStaff(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM messages WHERE read_at IS NULL");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function getByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.name AS sender_name, u.role AS sender_role
            FROM messages m
            LEFT JOIN users u ON u.id = m.sender_id
            WHERE m.client_id = :client_id
            ORDER BY m.created_at ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getByClientAfterId(int $clientId, int $afterId, int $limit = 100): array
    {
        $limit = max(1, min($limit, 100));
        $stmt = $this->db->prepare("
            SELECT m.*, u.name AS sender_name, u.role AS sender_role
            FROM messages m
            LEFT JOIN users u ON u.id = m.sender_id
            WHERE m.client_id = :client_id AND m.id > :after_id
            ORDER BY m.id ASC
            LIMIT {$limit}
        ");
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO messages (client_id, entity_id, scope, sender_id, thread_id, body)
            VALUES (:client_id, :entity_id, :scope, :sender_id, :thread_id, :body)
        ");
        $stmt->execute([
            'client_id' => $data['client_id'],
            'entity_id' => $data['entity_id'],
            'scope' => $data['scope'],
            'sender_id' => $data['sender_id'],
            'thread_id' => $data['thread_id'] ?? 1,
            'body'      => $data['body'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function markAsReadForRecipient(int $clientId, string $recipientRole): void
    {
        // Mark messages as read where sender role is NOT the recipient (i.e., messages FROM the other side)
        $senderRole = ($recipientRole === 'staff') ? 'client' : 'staff';
        $stmt = $this->db->prepare("
            UPDATE messages m
            JOIN users u ON u.id = m.sender_id
            SET m.read_at = NOW()
            WHERE m.client_id = :client_id
              AND m.read_at IS NULL
              AND u.role = :sender_role
        ");
        $stmt->execute([
            'client_id'   => $clientId,
            'sender_role' => $senderRole,
        ]);
    }
}
