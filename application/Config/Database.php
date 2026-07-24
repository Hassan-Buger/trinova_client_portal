<?php

namespace Application\Config;

class Database
{
    public static function getConfig(): array
    {
        return [
            'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'     => $_ENV['DB_PORT'] ?? '3306',
            'dbname'   => $_ENV['DB_NAME'] ?? 'trinova_portal',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset'  => 'utf8mb4',
        ];
    }
}
