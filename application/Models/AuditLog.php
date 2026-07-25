<?php

namespace Application\Models;

use Application\Core\Model;
use PDO;

class AuditLog extends Model
{
    public function getRecentActivity(int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name AS user_name, u.role AS user_role
            FROM audit_log a
            JOIN users u ON u.id = a.user_id
            ORDER BY a.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllFiltered(?string $action = null, int $limit = 50): array
    {
        $sql = "
            SELECT a.*, u.name AS user_name, u.role AS user_role, u.email AS user_email
            FROM audit_log a
            JOIN users u ON u.id = a.user_id
        ";
        $params = [];

        if (!empty($action)) {
            $sql .= " WHERE a.action_type = :action ";
            $params['action'] = $action;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(":$k", $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
