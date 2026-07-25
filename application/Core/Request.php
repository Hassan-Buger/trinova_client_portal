<?php

namespace Application\Core;

class Request
{
    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function getUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($uri, '?');
        if ($position !== false) {
            $uri = substr($uri, 0, $position);
        }
        return rtrim($uri, '/') ?: '/';
    }

    public function getBody(): array
    {
        $body = [];
        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                $body[$key] = is_string($value) ? trim(filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS)) : $value;
            }
        }
        if ($this->getMethod() === 'POST') {
            foreach ($_POST as $key => $value) {
                $body[$key] = is_string($value) ? trim(filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS)) : $value;
            }
        }
        return $body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $body = $this->getBody();
        return $body[$key] ?? $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function getQueryParams(): array
    {
        return $_GET ?? [];
    }

    public function getIp(): string
    {
        return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
