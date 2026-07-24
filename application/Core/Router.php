<?php

namespace Application\Core;

class Router
{
    private array $routes = [];
    private array $groupAttributes = [];

    public function group(array $attributes, callable $callback): void
    {
        $parentGroupAttributes = $this->groupAttributes;
        
        $prefix = ($parentGroupAttributes['prefix'] ?? '') . ($attributes['prefix'] ?? '');
        $middleware = array_merge(
            $parentGroupAttributes['middleware'] ?? [],
            $attributes['middleware'] ?? []
        );

        $this->groupAttributes = [
            'prefix' => $prefix,
            'middleware' => $middleware
        ];

        call_user_func($callback, $this);

        $this->groupAttributes = $parentGroupAttributes;
    }

    public function get(string $path, array|callable $callback): self
    {
        $this->addRoute('GET', $path, $callback);
        return $this;
    }

    public function post(string $path, array|callable $callback): self
    {
        $this->addRoute('POST', $path, $callback);
        return $this;
    }

    private function addRoute(string $method, string $path, array|callable $callback): void
    {
        $prefix = $this->groupAttributes['prefix'] ?? '';
        $fullPath = rtrim($prefix . $path, '/') ?: '/';
        $middleware = $this->groupAttributes['middleware'] ?? [];

        $this->routes[] = [
            'method'     => $method,
            'path'       => $fullPath,
            'callback'   => $callback,
            'middleware' => $middleware,
        ];
    }

    public function middleware(array $middleware): self
    {
        if (!empty($this->routes)) {
            $lastIndex = count($this->routes) - 1;
            $this->routes[$lastIndex]['middleware'] = array_merge(
                $this->routes[$lastIndex]['middleware'],
                $middleware
            );
        }
        return $this;
    }

    public function resolve(Request $request, Response $response): mixed
    {
        $method = $request->getMethod();
        $path = $request->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Execute Middleware Stack
                foreach ($route['middleware'] as $mw) {
                    $args = [];
                    if (str_contains($mw, ':')) {
                        [$mwClass, $argString] = explode(':', $mw, 2);
                        $args = explode(',', $argString);
                    } else {
                        $mwClass = $mw;
                    }

                    $middlewareInstance = new $mwClass();
                    $middlewareResult = $middlewareInstance->handle($request, $response, $args);
                    if ($middlewareResult === false) {
                        return null; // Pipeline terminated by middleware
                    }
                }

                $callback = $route['callback'];
                if (is_array($callback)) {
                    [$class, $action] = $callback;
                    $controller = new $class();
                    return call_user_func_array([$controller, $action], array_merge([$request, $response], $params));
                }

                return call_user_func_array($callback, array_merge([$request, $response], $params));
            }
        }

        $response->setStatusCode(404);
        echo "404 - Page Not Found";
        return null;
    }
}
