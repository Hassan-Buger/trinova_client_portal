<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Deadline extends Model
{
    public function getAllByClient(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM deadlines
            WHERE client_id = :client_id
            ORDER BY due_date ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getAllWithDetails(): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, c_user.name AS client_name
            FROM deadlines d
            JOIN clients c ON c.id = d.client_id
            JOIN users c_user ON c_user.id = c.user_id
            ORDER BY d.due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO deadlines (client_id, type, due_date, status)
            VALUES (:client_id, :type, :due_date, :status)
        ");
        $stmt->execute([
            'client_id' => $data['client_id'],
            'type'      => $data['type'],
            'due_date'  => $data['due_date'],
            'status'    => $data['status'] ?? 'Pending',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE deadlines SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
}
