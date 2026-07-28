<?php

namespace Application\Services;

use Application\Core\Database;
use Application\Exceptions\SystemSetupException;

final class SchemaGuard
{
    public static function assertClientCsvReady(): void
    {
        $db=Database::getInstance();
        $required=['id','entity_id','user_id','name','email','phone','is_primary','needs_contact_details'];
        $stmt=$db->query("SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='entity_contacts'");
        $available=array_map('strtolower',$stmt->fetchAll(\PDO::FETCH_COLUMN));
        if(array_diff($required,$available)){
            throw new SystemSetupException('Client CSV schema migration is incomplete: entity_contacts is missing required columns.');
        }
        $tracking=$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='client_csv_imports'")->fetchColumn();
        if(!(int)$tracking)throw new SystemSetupException('Client CSV duplicate-tracking migration is incomplete.');
    }
}
