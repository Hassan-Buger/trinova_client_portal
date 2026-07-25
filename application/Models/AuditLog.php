<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class AuditLog extends Model
{
    public function getRecentActivity(int $limit = 10): array
    {
        $limitVal = (int)$limit;
        $stmt = $this->db->prepare("
            SELECT a.*, u.name AS user_name, u.role AS user_role
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.user_id
            ORDER BY a.created_at DESC
            LIMIT {$limitVal}
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllFiltered(?string $action = null, int $limit = 50): array
    {
        $limitVal = (int)$limit;
        $sql = "
            SELECT a.*, u.name AS user_name, u.role AS user_role, u.email AS user_email
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.user_id
        ";
        $params = [];

        if (!empty($action)) {
            $sql .= " WHERE a.action_type = :action ";
            $params['action'] = $action;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT {$limitVal}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByUserId(int $userId, int $limit = 20): array
    {
        $limitVal = (int)$limit;
        $stmt = $this->db->prepare("
            SELECT * FROM audit_log
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT {$limitVal}
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
