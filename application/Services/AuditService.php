<?php

namespace Application\Services;

use Application\Core\Database;
use Application\Core\Request;
use Application\Core\Session;
use PDO;

class AuditService
{
    public static function log(string $actionType, string $targetType, ?int $targetId = null, ?int $userId = null, array $metadata = []): void
    {
        $db = Database::getInstance();
        $request = new Request();

        $activeUserId = $userId ?? Session::get('user_id');
        $ipAddress = $request->getIp();

        $metadataSql=$metadata?', import_metadata':'';
        $metadataValue=$metadata?', :import_metadata':'';
        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action_type, target_type, target_id{$metadataSql}, ip_address, created_at)
            VALUES (:user_id, :action_type, :target_type, :target_id{$metadataValue}, :ip_address, NOW())
        ");

        $params=[
            'user_id'     => $activeUserId,
            'action_type' => $actionType,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'ip_address'  => $ipAddress,
        ];
        if($metadata)$params['import_metadata']=json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $stmt->execute($params);
    }
}
