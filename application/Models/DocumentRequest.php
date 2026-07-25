<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class DocumentRequest extends Model
{
    public function getOutstandingByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM document_requests
            WHERE client_id = :client_id AND status != 'Completed'
            ORDER BY due_date ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getAllByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM document_requests
            WHERE client_id = :client_id
            ORDER BY created_at DESC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getAllWithDetails(): array
    {
        $stmt = $this->db->prepare("
            SELECT dr.*, u.name AS created_by_name, c_user.name AS client_name
            FROM document_requests dr
            JOIN users u ON u.id = dr.created_by_user_id
            JOIN clients c ON c.id = dr.client_id
            JOIN users c_user ON c_user.id = c.user_id
            ORDER BY dr.due_date ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO document_requests (client_id, created_by_user_id, title, description, due_date, status)
            VALUES (:client_id, :created_by_user_id, :title, :description, :due_date, :status)
        ");
        $stmt->execute([
            'client_id'          => $data['client_id'],
            'created_by_user_id' => $data['created_by_user_id'],
            'title'              => $data['title'],
            'description'        => $data['description'] ?? null,
            'due_date'           => $data['due_date'],
            'status'             => $data['status'] ?? 'Awaiting Client',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE document_requests SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function getOverdueCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total 
            FROM document_requests 
            WHERE status != 'Completed' AND due_date < CURRENT_DATE()
        ");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }
}
