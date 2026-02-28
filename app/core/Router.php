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
        $path = $this->normalizePath((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));

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
            $path = $this->normalizePath((string) (substr($path, strlen($base)) ?: '/'));
        }

        $match = $this->matchRouteForMethod($method, $path);
        if ($match !== null) {
            if ($this->invokeHandler($match['handler'], $match['params'])) {
                return;
            }
            $this->respondNotFound();
            return;
        }

        $allowedMethods = $this->detectAllowedMethodsForPath($path);
        if ($allowedMethods !== []) {
            $this->respondMethodNotAllowed($allowedMethods);
            return;
        }

        $this->respondNotFound();
    }

    private function normalizePath(string $path): string
    {
        $normalized = '/' . trim($path, '/');
        return $normalized === '' ? '/' : $normalized;
    }

    /**
     * @return array{handler: array{0: string, 1: string}, params: array<int, string>}|null
     */
    private function matchRouteForMethod(string $method, string $path): ?array
    {
        $handlers = $this->routes[$method] ?? [];
        foreach ($handlers as $route => $handler) {
            $pattern = $this->compileRoutePattern($route);
            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }
            array_shift($matches);
            return [
                'handler' => $handler,
                'params' => array_map(static fn(mixed $v): string => (string) $v, $matches),
            ];
        }
        return null;
    }

    private function compileRoutePattern(string $route): string
    {
        $parts = preg_split('/(\{[^}]+\})/', $route, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $pattern = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match('/^\{[^}]+\}$/', $part) === 1) {
                $pattern .= '([^/]+)';
                continue;
            }
            $pattern .= preg_quote($part, '#');
        }
        return '#^' . $pattern . '$#';
    }

    /**
     * @param array{0: string, 1: string} $handler
     * @param array<int, string> $params
     */
    private function invokeHandler(array $handler, array $params): bool
    {
        [$controller, $action] = $handler;
        if (!class_exists($controller)) {
            return false;
        }
        $instance = $this->container?->get($controller) ?? new $controller();
        if (!method_exists($instance, $action) || !is_callable([$instance, $action])) {
            return false;
        }
        $instance->{$action}(...$params);
        return true;
    }

    /**
     * @return array<int, string>
     */
    private function detectAllowedMethodsForPath(string $path): array
    {
        $allowed = [];
        foreach (array_keys($this->routes) as $method) {
            if ($this->matchRouteForMethod($method, $path) !== null) {
                $allowed[] = $method;
            }
        }
        return $allowed;
    }

    private function respondNotFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        $viewFile = dirname(__DIR__, 2) . '/app/views/404.php';
        if (file_exists($viewFile)) {
            require $viewFile;
            return;
        }
        echo '404 Not Found';
    }

    /**
     * @param array<int, string> $allowedMethods
     */
    private function respondMethodNotAllowed(array $allowedMethods): void
    {
        $allowed = implode(', ', array_values(array_unique($allowedMethods)));
        http_response_code(405);
        header('Allow: ' . $allowed);
        header('Content-Type: text/plain; charset=utf-8');
        echo '405 Method Not Allowed';
    }
}
