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

    public function getOverdueCount(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total 
            FROM document_requests 
            WHERE due_date < CURDATE() AND status != 'Completed'
        ");
        $row = $stmt->fetch();
        return (int) ($row['total'] ?? 0);
    }
}
