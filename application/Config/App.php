<?php

namespace Application\Config;

class App
{
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = [
            'name' => 'TriNova Accounting',
            'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
            'env' => $_ENV['APP_ENV'] ?? 'local',
            'debug' => ($_ENV['APP_DEBUG'] ?? 'true') === 'true',
            'secret' => $_ENV['APP_SECRET'] ?? 'trinova_default_secret_key_32bytes!',
            'session_timeout' => 900, // 15 minutes session inactivity timeout in seconds
            'storage_dir' => dirname(__DIR__, 2) . '/storage',
        ];

        return $config[$key] ?? $default;
    }
}
