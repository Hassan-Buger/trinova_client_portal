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

    public function getRecentCount(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM documents");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO documents (client_id, uploaded_by_user_id, direction, filename, stored_path, description, status)
            VALUES (:client_id, :uploaded_by_user_id, :direction, :filename, :stored_path, :description, :status)
        ");
        $stmt->execute([
            'client_id'           => $data['client_id'],
            'uploaded_by_user_id' => $data['uploaded_by_user_id'],
            'direction'           => $data['direction'],
            'filename'            => $data['filename'],
            'stored_path'         => $data['stored_path'],
            'description'        => $data['description'] ?? null,
            'status'              => $data['status'] ?? 'Ready',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getAllWithDetails(): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, u.name AS uploaded_by_name, c_user.name AS client_name
            FROM documents d
            JOIN users u ON u.id = d.uploaded_by_user_id
            JOIN clients c ON c.id = d.client_id
            JOIN users c_user ON c_user.id = c.user_id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
