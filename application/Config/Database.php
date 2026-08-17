<?php

namespace Application\Config;

class Database
{
    public static function getConfig(): array
    {
        return [
            'host'     => $_ENV['DB_HOST'] ?? $_ENV['MYSQLHOST'] ?? '127.0.0.1',
            'port'     => $_ENV['DB_PORT'] ?? $_ENV['MYSQLPORT'] ?? '3306',
            'dbname'   => $_ENV['DB_NAME'] ?? $_ENV['MYSQLDATABASE'] ?? 'trinova_portal',
            'username' => $_ENV['DB_USER'] ?? $_ENV['MYSQLUSER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? $_ENV['MYSQLPASSWORD'] ?? '',
            'charset'  => 'utf8mb4',
        ];
    }
}
