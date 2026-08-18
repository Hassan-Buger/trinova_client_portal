<?php

namespace Application\Core;

use PDO;
use Throwable;

class SchemaMigrator
{
    private static bool $hasRun = false;

    /**
     * Run schema migrations and seed data setup if required.
     */
    public static function run(PDO $pdo): bool
    {
        if (self::$hasRun) {
            return true;
        }

        self::$hasRun = true;

        try {
            // Check if core 'users' table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            $hasUsers = (bool)$stmt->fetchColumn();

            if (!$hasUsers) {
                error_log('[TriNova SchemaMigrator] Fresh database detected. Importing base schema and seed data...');
                self::importSqlFile($pdo, dirname(__DIR__, 2) . '/config/database.sql');
                error_log('[TriNova SchemaMigrator] Base schema and initial seed data imported successfully.');
            } else {
                // Check if user accounts exist in users table
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `users`");
                $userCount = (int)$countStmt->fetchColumn();
                if ($userCount === 0) {
                    error_log('[TriNova SchemaMigrator] Empty users table detected. Seeding default accounts...');
                    self::importSqlFile($pdo, dirname(__DIR__, 2) . '/config/database.sql');
                }
            }

            // Ensure all incremental migrations are applied safely
            self::applyIncrementalMigrations($pdo);

            return true;
        } catch (Throwable $e) {
            error_log('[TriNova SchemaMigrator] Migration warning: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Parse and execute a multi-statement SQL file cleanly.
     */
    public static function importSqlFile(PDO $pdo, string $filePath): bool
    {
        if (!file_exists($filePath)) {
            error_log("[TriNova SchemaMigrator] SQL file not found: {$filePath}");
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return false;
        }

        // Remove SQL comments
        $lines = explode("\n", $content);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (strpos($trimmed, '--') === 0 || strpos($trimmed, '#') === 0) {
                continue;
            }
            $cleanLines[] = $line;
        }
        $cleanSql = implode("\n", $cleanLines);

        // Split by statement delimiters
        $statements = preg_split('/;\s*(\r?\n|$)/', $cleanSql);

        foreach ($statements as $statement) {
            $stmtText = trim($statement);
            if ($stmtText === '') {
                continue;
            }

            // Skip CREATE DATABASE / USE statements which might fail in cloud managed DBs
            if (preg_match('/^(CREATE\s+DATABASE|USE\s+)/i', $stmtText)) {
                continue;
            }

            try {
                $pdo->exec($stmtText);
            } catch (Throwable $e) {
                // Non-fatal duplicate/exists errors are safely ignored
                error_log("[TriNova SchemaMigrator] Statement note: " . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * Apply any additional migrations idempotently.
     */
    private static function applyIncrementalMigrations(PDO $pdo): void
    {
        $migrations = [
            dirname(__DIR__, 2) . '/config/otp_migration.sql',
            dirname(__DIR__, 2) . '/config/client_csv_import_migration.sql',
            dirname(__DIR__, 2) . '/config/client_csv_duplicate_tracking_migration.sql',
            dirname(__DIR__, 2) . '/config/director_import_migration.sql',
            dirname(__DIR__, 2) . '/config/entity_deadlines_migration.sql',
            dirname(__DIR__, 2) . '/config/notifications_migration.sql',
            dirname(__DIR__, 2) . '/config/delete_functionality_migration.sql',
        ];

        foreach ($migrations as $migrationFile) {
            if (file_exists($migrationFile)) {
                self::importSqlFile($pdo, $migrationFile);
            }
        }
    }
}
