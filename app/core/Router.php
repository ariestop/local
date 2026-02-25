<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public static function fromConfig(string $configPath): self
    {
        $routes = require $configPath;
        $router = new self();
        foreach ($routes['GET'] ?? [] as $path => $handler) {
            $router->routes['GET'][$path] = $handler;
        }
        foreach ($routes['POST'] ?? [] as $path => $handler) {
            $router->routes['POST'][$path] = $handler;
        }
        return $router;
    }

    public function get(string $path, array $handler): self
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }

    public function post(string $path, array $handler): self
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        $handlers = $this->routes[$method] ?? [];
        foreach ($handlers as $route => $handler) {
            $pattern = '#^' . preg_replace('#\{[^}]+\}#', '([^/]+)', $route) . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                [$controller, $action] = $handler;
                $instance = new $controller();
                $instance->{$action}(...$matches);
                return;
            }
        }

        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $viewFile = dirname(__DIR__, 2) . '/app/views/404.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo '404 Not Found';
        }
    }
}
