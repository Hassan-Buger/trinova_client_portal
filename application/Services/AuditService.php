<?php

namespace Application\Services;

use Application\Core\Database;
use Application\Core\Request;
use Application\Core\Session;
use PDO;

class AuditService
{
    public static function log(string $actionType, string $targetType, ?int $targetId = null, ?int $userId = null): void
    {
        $db = Database::getInstance();
        $request = new Request();

        $activeUserId = $userId ?? Session::get('user_id');
        $ipAddress = $request->getIp();

        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action_type, target_type, target_id, ip_address, created_at)
            VALUES (:user_id, :action_type, :target_type, :target_id, :ip_address, NOW())
        ");

        $stmt->execute([
            'user_id'     => $activeUserId,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'ip_address'  => $ipAddress,
        ]);
    }
}
