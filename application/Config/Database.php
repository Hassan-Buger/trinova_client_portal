<?php

namespace Application\Config;

class Database
{
    private static function env(string $key, ?string $fallback = null): ?string
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        $val = getenv($key);
        if ($val !== false && $val !== '') {
            return $val;
        }
        return $fallback;
    }

    public static function getConfig(): array
    {
        return [
            'host'     => self::env('DB_HOST', self::env('MYSQLHOST', '127.0.0.1')),
            'port'     => self::env('DB_PORT', self::env('MYSQLPORT', '3306')),
            'dbname'   => self::env('DB_NAME', self::env('MYSQLDATABASE', 'trinova_portal')),
            'username' => self::env('DB_USER', self::env('MYSQLUSER', 'root')),
            'password' => self::env('DB_PASS', self::env('MYSQLPASSWORD', '')),
            'charset'  => 'utf8mb4',
        ];
    }
}

