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
}
