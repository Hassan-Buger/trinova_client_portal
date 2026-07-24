<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class ClientEntity extends Model
{
    public function getByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM client_entities
            WHERE client_id = :client_id
            ORDER BY company_name ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }
}
