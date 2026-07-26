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
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM client_entities WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function getAllWithClient(): array
    {
        $stmt = $this->db->query("SELECT e.*, u.name AS client_name FROM client_entities e JOIN clients c ON c.id=e.client_id JOIN users u ON u.id=c.user_id ORDER BY u.name,e.company_name");
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO client_entities (client_id, company_name, entity_type, company_number, tax_reference, attributes)
            VALUES (:client_id, :company_name, :entity_type, :company_number, :tax_reference, :attributes)
        ");
        $stmt->execute([
            'client_id'      => $data['client_id'],
            'company_name'   => $data['company_name'],
            'entity_type'    => $data['entity_type'] ?? 'Other',
            'company_number' => $data['company_number'] ?? null,
            'tax_reference'  => $data['tax_reference'] ?? null,
            'attributes'     => json_encode($data['attributes'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
        return (int) $this->db->lastInsertId();
    }

    private function hydrate(array $row): array
    {
        $attributes = json_decode((string)($row['attributes'] ?? '{}'), true);
        $row['attributes'] = is_array($attributes) ? $attributes : [];
        return $row;
    }
}
