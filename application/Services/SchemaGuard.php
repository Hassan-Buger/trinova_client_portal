<?php

namespace Application\Services;

use Application\Core\Database;
use Application\Exceptions\SystemSetupException;

final class SchemaGuard
{
    public static function assertClientCsvReady(): void
    {
        $db=Database::getInstance();
        $table=$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='entity_contacts'")->fetchColumn();
        $column=$db->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='audit_log' AND column_name='import_metadata'")->fetchColumn();
        if(!(int)$table || !(int)$column){
            throw new SystemSetupException('Client CSV schema migration is incomplete: entity_contacts/import_metadata missing.');
        }
    }
}
