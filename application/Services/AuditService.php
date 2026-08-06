<?php

namespace Application\Services;

use Application\Core\Database;
use Application\Core\Request;
use Application\Core\Session;
use PDO;

class AuditService
{
    private static ?bool $supportsImportMetadata = null;

    public static function log(string $actionType, string $targetType, ?int $targetId = null, ?int $userId = null, array $metadata = []): void
    {
        $db = Database::getInstance();
        $request = new Request();

        $activeUserId = $userId ?? Session::get('user_id');
        $ipAddress = $request->getIp();

        if(self::$supportsImportMetadata===null){
            $check=$db->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='audit_log' AND column_name='import_metadata'");
            self::$supportsImportMetadata=(bool)$check->fetchColumn();
        }
        $storeMetadata=$metadata && self::$supportsImportMetadata;
        $metadataSql=$storeMetadata?', import_metadata':'';
        $metadataValue=$storeMetadata?', :import_metadata':'';
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
        if($storeMetadata)$params['import_metadata']=json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $stmt->execute($params);
    }
}
