<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$schemaMigrator = file_get_contents($root . '/application/Core/SchemaMigrator.php');
$schemaGuard = file_get_contents($root . '/application/Services/SchemaGuard.php');
$databaseSql = file_get_contents($root . '/config/database.sql');

function check(bool $cond, string $msg): void
{
    if (!$cond) throw new RuntimeException($msg);
}

// 1. Verify soft-delete tables in SchemaGuard are all covered in SchemaMigrator and database.sql
$requiredSoftDeleteTables = [
    'users', 'clients', 'client_entities', 'entity_directors',
    'documents', 'document_requests', 'messages', 'deadlines',
    'meetings', 'client_csv_imports'
];

foreach ($requiredSoftDeleteTables as $table) {
    check(str_contains($schemaMigrator, "'{$table}'"), "SchemaMigrator is missing soft-delete management for {$table}.");
    check(str_contains($databaseSql, "CREATE TABLE IF NOT EXISTS `{$table}`"), "database.sql is missing table {$table}.");
}

// 2. Verify entity_contacts columns required by SchemaGuard
$requiredContactCols = [
    'original_full_name', 'email', 'phone', 'director_utr',
    'address', 'id_number', 'ch_verification_number', 'is_primary',
    'needs_contact_details', 'last_director_import_id'
];

foreach ($requiredContactCols as $col) {
    check(str_contains($schemaMigrator, "'{$col}'"), "SchemaMigrator is missing column check for entity_contacts.{$col}.");
    check(str_contains($databaseSql, "`{$col}`"), "database.sql is missing entity_contacts.{$col}.");
}

// 3. Verify ensureCompleteSchema is invoked in SchemaMigrator::run
check(str_contains($schemaMigrator, 'self::ensureCompleteSchema($pdo);'), 'SchemaMigrator::run does not call ensureCompleteSchema.');

echo "SchemaGuard & SchemaMigrator alignment tests passed successfully.\n";
