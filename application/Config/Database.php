<?php

namespace Application\Config;

class Database
{
    private static function env(string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
                return (string)$_ENV[$key];
            }
            if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
                return (string)$_SERVER[$key];
            }
            $val = getenv($key);
            if ($val !== false && $val !== '') {
                return (string)$val;
            }
        }
        return null;
    }

    public static function getConfig(): array
    {
        $dbUrl = self::env('MYSQL_URL', 'DATABASE_URL', 'JAWSDB_URL', 'CLEARDB_DATABASE_URL');
        $urlConfig = [];

        if ($dbUrl) {
            $cleanDbUrl = trim($dbUrl, " \t\n\r\0\x0B)\"';");
            $parsed = parse_url($cleanDbUrl);
            if (is_array($parsed)) {
                if (!empty($parsed['host'])) $urlConfig['host'] = $parsed['host'];
                if (!empty($parsed['port'])) $urlConfig['port'] = (string)$parsed['port'];
                if (!empty($parsed['user'])) $urlConfig['username'] = urldecode($parsed['user']);
                if (isset($parsed['pass']))  $urlConfig['password'] = urldecode($parsed['pass']);
                if (!empty($parsed['path'])) $urlConfig['dbname'] = trim(ltrim($parsed['path'], '/'), " \t\n\r\0\x0B)\"';");
            }
        }

        return [
            'host'     => self::env('DB_HOST', 'MYSQL_HOST', 'MYSQLHOST', 'DB_HOSTNAME') ?? $urlConfig['host'] ?? '127.0.0.1',
            'port'     => self::env('DB_PORT', 'MYSQL_PORT', 'MYSQLPORT') ?? $urlConfig['port'] ?? '3306',
            'dbname'   => self::env('DB_NAME', 'DB_DATABASE', 'MYSQL_DATABASE', 'MYSQLDATABASE') ?? $urlConfig['dbname'] ?? 'trinova_portal',
            'username' => self::env('DB_USER', 'DB_USERNAME', 'MYSQL_USER', 'MYSQLUSER') ?? $urlConfig['username'] ?? 'root',
            'password' => self::env('DB_PASS', 'DB_PASSWORD', 'MYSQL_PASSWORD', 'MYSQLPASSWORD') ?? $urlConfig['password'] ?? '',
            'charset'  => 'utf8mb4',
        ];
    }
}

