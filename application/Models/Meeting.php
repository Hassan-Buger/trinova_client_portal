<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Meeting extends Model
{
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM meetings WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getByClient(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM meetings
            WHERE client_id = :client_id AND deleted_at IS NULL
            ORDER BY created_at DESC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getByClientId(int $clientId): array
    {
        return $this->getByClient($clientId);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO meetings (client_id, type, external_booking_reference)
            VALUES (:client_id, :type, :external_booking_reference)
        ");
        $stmt->execute([
            'client_id'                  => $data['client_id'],
            'type'                       => $data['type'],
            'external_booking_reference' => $data['external_booking_reference'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE meetings SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function bulkSoftDelete(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->softDelete((int)$id)) {
                $count++;
            }
        }
        return $count;
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE meetings SET deleted_at = NULL WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function bulkRestore(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->restore((int)$id)) {
                $count++;
            }
        }
        return $count;
    }

    public function getSoftDeleted(): array
    {
        $stmt = $this->db->query("
            SELECT m.*, u.name AS client_name
            FROM meetings m
            JOIN clients c ON c.id = m.client_id
            JOIN users u ON u.id = c.user_id
            WHERE m.deleted_at IS NOT NULL
            ORDER BY m.deleted_at DESC
        ");
        return $stmt->fetchAll();
    }
}
