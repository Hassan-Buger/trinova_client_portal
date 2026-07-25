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

    public function getAmlActionRequiredCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total 
            FROM clients 
            WHERE aml_status = 'Action Required'
        ");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO clients (user_id, phone, address, aml_status, notes)
            VALUES (:user_id, :phone, :address, :aml_status, :notes)
        ");
        $stmt->execute([
            'user_id'    => $data['user_id'],
            'phone'      => $data['phone'] ?? null,
            'address'    => $data['address'] ?? null,
            'aml_status' => $data['aml_status'] ?? 'Action Required',
            'notes'      => $data['notes'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $client = $this->findById($id);
        if ($client && !empty($client['user_id'])) {
            $stmtUser = $this->db->prepare("DELETE FROM users WHERE id = :user_id");
            $stmtUser->execute(['user_id' => $client['user_id']]);
        }
        $stmt = $this->db->prepare("DELETE FROM clients WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
