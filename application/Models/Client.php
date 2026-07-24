<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Client extends Model
{
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name, u.email
            FROM clients c
            JOIN users u ON u.id = c.user_id
            WHERE c.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name, u.email
            FROM clients c
            JOIN users u ON u.id = c.user_id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getAllWithUsers(): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.name, u.email, u.status AS user_status
            FROM clients c
            JOIN users u ON u.id = c.user_id
            ORDER BY u.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
