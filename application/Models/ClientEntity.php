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

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO client_entities (client_id, company_name, company_number, tax_reference)
            VALUES (:client_id, :company_name, :company_number, :tax_reference)
        ");
        $stmt->execute([
            'client_id'      => $data['client_id'],
            'company_name'   => $data['company_name'],
            'company_number' => $data['company_number'] ?? null,
            'tax_reference'  => $data['tax_reference'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }
}
