<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = ['GET' => [], 'POST' => []];
    private ?Container $container = null;
    private string $basePath = '';

    public function setContainer(Container $container): self
    {
        $this->container = $container;
        return $this;
    }

    public function setBasePath(string $path): self
    {
        $this->basePath = rtrim($path, '/');
        return $this;
    }

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

        // Compatibility for setups without mod_rewrite: /index.php/... should route as /...
        if ($path === '/index.php') {
            $path = '/';
        } elseif (str_starts_with($path, '/index.php/')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }

        $base = $this->basePath !== ''
            ? $this->basePath
            : rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        $handlers = $this->routes[$method] ?? [];
        foreach ($handlers as $route => $handler) {
            $pattern = '#^' . preg_replace('#\{[^}]+\}#', '([^/]+)', $route) . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                [$controller, $action] = $handler;
                $instance = $this->container?->get($controller) ?? new $controller();
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
