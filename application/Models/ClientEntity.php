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
            WHERE client_id = :client_id AND deleted_at IS NULL
            ORDER BY company_name ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM client_entities WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function getAllWithClient(): array
    {
        $stmt = $this->db->query("SELECT e.*, u.name AS client_name FROM client_entities e JOIN clients c ON c.id=e.client_id JOIN users u ON u.id=c.user_id WHERE e.deleted_at IS NULL AND c.deleted_at IS NULL ORDER BY u.name,e.company_name");
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO client_entities (client_id, company_name, entity_type, entity_scope, company_number, tax_reference, attributes)
            VALUES (:client_id, :company_name, :entity_type, :entity_scope, :company_number, :tax_reference, :attributes)
        ");
        $stmt->execute([
            'client_id'      => $data['client_id'],
            'company_name'   => $data['company_name'],
            'entity_type'    => $data['entity_type'] ?? 'Other',
            'entity_scope'   => $data['entity_scope'] ?? 'company',
            'company_number' => $data['company_number'] ?? null,
            'tax_reference'  => $data['tax_reference'] ?? null,
            'attributes'     => json_encode($data['attributes'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE client_entities SET deleted_at = NOW() WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            $this->db->prepare("UPDATE entity_directors SET deleted_at = NOW() WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
            $this->db->prepare("UPDATE documents SET deleted_at = NOW() WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
            $this->db->prepare("UPDATE document_requests SET deleted_at = NOW() WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
            $this->db->prepare("UPDATE messages SET deleted_at = NOW() WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
            $this->db->prepare("UPDATE deadlines SET deleted_at = NOW() WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
        }
        return $success;
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
        $stmt = $this->db->prepare("UPDATE client_entities SET deleted_at = NULL WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            $this->db->prepare("UPDATE entity_directors SET deleted_at = NULL WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
            $this->db->prepare("UPDATE documents SET deleted_at = NULL WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
            $this->db->prepare("UPDATE document_requests SET deleted_at = NULL WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
            $this->db->prepare("UPDATE messages SET deleted_at = NULL WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
            $this->db->prepare("UPDATE deadlines SET deleted_at = NULL WHERE entity_id = :entity_id")->execute(['entity_id' => $id]);
        }
        return $success;
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
        $stmt = $this->db->query("SELECT e.*, u.name AS client_name FROM client_entities e JOIN clients c ON c.id=e.client_id JOIN users u ON u.id=c.user_id WHERE e.deleted_at IS NOT NULL ORDER BY e.deleted_at DESC");
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    private function hydrate(array $row): array
    {
        $attributes = json_decode((string)($row['attributes'] ?? '{}'), true);
        $row['attributes'] = is_array($attributes) ? $attributes : [];
        return $row;
    }
}
