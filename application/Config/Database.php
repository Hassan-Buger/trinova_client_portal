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

        // Prioritize full connection URL (e.g. Railway MYSQL_URL / DATABASE_URL), then specific Railway vars, then standard DB vars
        return [
            'host'     => $urlConfig['host'] ?? self::env('MYSQLHOST', 'MYSQL_HOST', 'DB_HOST', 'DB_HOSTNAME') ?? '127.0.0.1',
            'port'     => $urlConfig['port'] ?? self::env('MYSQLPORT', 'MYSQL_PORT', 'DB_PORT') ?? '3306',
            'dbname'   => $urlConfig['dbname'] ?? self::env('MYSQLDATABASE', 'MYSQL_DATABASE', 'DB_NAME', 'DB_DATABASE') ?? 'trinova_portal',
            'username' => $urlConfig['username'] ?? self::env('MYSQLUSER', 'MYSQL_USER', 'DB_USER', 'DB_USERNAME') ?? 'root',
            'password' => $urlConfig['password'] ?? self::env('MYSQLPASSWORD', 'MYSQL_PASSWORD', 'DB_PASS', 'DB_PASSWORD') ?? '',
            'charset'  => 'utf8mb4',
        ];
    }
}

