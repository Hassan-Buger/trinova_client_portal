<?php

namespace Application\Models;

use Application\Core\Model;

class EntityAccess extends Model
{
    public function accessibleEntities(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT e.*, c.user_id AS owner_user_id
            FROM client_entities e
            JOIN clients c ON c.id = e.client_id
            LEFT JOIN entity_directors ed ON ed.entity_id = e.id AND ed.user_id = :director_user
            WHERE (e.entity_scope = 'company' AND ed.user_id IS NOT NULL)
               OR (e.entity_scope = 'personal' AND c.user_id = :owner_user)
            ORDER BY e.entity_scope, e.company_name
        ");
        $stmt->execute(['director_user' => $userId, 'owner_user' => $userId]);
        return array_map(static function(array $row): array {
            $attributes=json_decode((string)($row['attributes']??'{}'),true);
            $row['attributes']=is_array($attributes)?$attributes:[];
            return $row;
        },$stmt->fetchAll());
    }

    public function canAccessEntity(int $userId, int $entityId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM client_entities e
            JOIN clients c ON c.id = e.client_id
            LEFT JOIN entity_directors ed ON ed.entity_id=e.id AND ed.user_id=:director_user
            WHERE e.id=:entity_id AND (
                (e.entity_scope='company' AND ed.user_id IS NOT NULL)
                OR (e.entity_scope='personal' AND c.user_id=:owner_user)
            ) LIMIT 1
        ");
        $stmt->execute(['director_user'=>$userId, 'owner_user'=>$userId, 'entity_id'=>$entityId]);
        return (bool)$stmt->fetchColumn();
    }

    public function canAccessRecord(int $userId, array $record): bool
    {
        return !empty($record['entity_id']) && $this->canAccessEntity($userId, (int)$record['entity_id']);
    }

    public function recipients(int $entityId): array
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT u.id, u.name, u.email
            FROM client_entities e
            JOIN clients c ON c.id=e.client_id
            JOIN users u ON (
                (e.entity_scope='company' AND u.id IN (SELECT ed.user_id FROM entity_directors ed WHERE ed.entity_id=e.id))
                OR (e.entity_scope='personal' AND u.id=c.user_id)
            )
            WHERE e.id=:entity_id AND u.role='client'
        ");
        $stmt->execute(['entity_id'=>$entityId]);
        return $stmt->fetchAll();
    }

    public function directors(int $entityId): array
    {
        $stmt=$this->db->prepare("SELECT u.id,u.name,u.email FROM entity_directors ed JOIN users u ON u.id=ed.user_id WHERE ed.entity_id=:id ORDER BY u.name");
        $stmt->execute(['id'=>$entityId]);
        return $stmt->fetchAll();
    }

    public function contacts(int $entityId): array
    {
        $stmt=$this->db->prepare("SELECT id,user_id,name,email,phone,is_primary,needs_contact_details FROM entity_contacts WHERE entity_id=:id ORDER BY is_primary DESC,name,id");
        $stmt->execute(['id'=>$entityId]);
        return $stmt->fetchAll();
    }

    public function eligibleDirectors(): array
    {
        return $this->db->query("SELECT u.id,u.name,u.email,c.id AS client_id FROM users u JOIN clients c ON c.user_id=u.id WHERE u.role='client' ORDER BY u.name")->fetchAll();
    }

    public function linkDirector(int $entityId, int $userId, ?int $createdBy): bool
    {
        $stmt=$this->db->prepare("INSERT IGNORE INTO entity_directors(entity_id,user_id,created_by_user_id) SELECT e.id,u.id,:created_by FROM client_entities e JOIN users u ON u.id=:user_id AND u.role='client' WHERE e.id=:entity_id AND e.entity_scope='company'");
        $stmt->execute(['created_by'=>$createdBy,'user_id'=>$userId,'entity_id'=>$entityId]);
        return $stmt->rowCount() > 0;
    }

    public function unlinkDirector(int $entityId, int $userId): bool
    {
        $stmt=$this->db->prepare("DELETE ed FROM entity_directors ed JOIN client_entities e ON e.id=ed.entity_id WHERE ed.entity_id=:entity_id AND ed.user_id=:user_id AND e.entity_scope='company'");
        $stmt->execute(['entity_id'=>$entityId,'user_id'=>$userId]);
        return $stmt->rowCount() > 0;
    }
}
