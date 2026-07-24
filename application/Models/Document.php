<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Document extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getByClientAndDirection(int $clientId, string $direction): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, u.name AS uploaded_by_name, u.role AS uploaded_by_role
            FROM documents d
            JOIN users u ON u.id = d.uploaded_by_user_id
            WHERE d.client_id = :client_id AND d.direction = :direction
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([
            'client_id' => $clientId,
            'direction' => $direction
        ]);
        return $stmt->fetchAll();
    }
}
