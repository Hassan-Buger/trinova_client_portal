<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Meeting extends Model
{
    public function getByClient(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM meetings
            WHERE client_id = :client_id
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
}
