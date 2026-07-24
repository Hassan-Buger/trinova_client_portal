<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class Deadline extends Model
{
    public function getUpcomingByClient(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM deadlines
            WHERE client_id = :client_id AND status != 'Completed'
            ORDER BY due_date ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }
}
