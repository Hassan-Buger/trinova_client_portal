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

    public function getByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT m.*, u.name AS sender_name, u.role AS sender_role
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.client_id = :client_id
            ORDER BY m.created_at ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO messages (client_id, sender_id, thread_id, body)
            VALUES (:client_id, :sender_id, :thread_id, :body)
        ");
        $stmt->execute([
            'client_id' => $data['client_id'],
            'sender_id' => $data['sender_id'],
            'thread_id' => $data['thread_id'] ?? 1,
            'body'      => $data['body'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function markAsReadForRecipient(int $clientId, string $recipientRole): void
    {
        // If recipient is client, mark staff messages as read. If recipient is staff, mark client messages as read.
        $stmt = $this->db->prepare("
            UPDATE messages
            SET read_at = NOW()
            WHERE client_id = :client_id 
              AND read_at IS NULL 
              AND sender_id IN (SELECT id FROM users WHERE role != :recipient_role)
        ");
        $stmt->execute([
            'client_id'      => $clientId,
            'recipient_role' => $recipientRole
        ]);
    }
}
