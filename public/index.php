<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$container = require $root . '/app/bootstrap.php';

$config = $container->getConfig();
$basePath = rtrim(parse_url($config['app']['url'] ?? '', PHP_URL_PATH) ?: '', '/');

$appEnv = $config['app']['env'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';

// Debug Bar init (только APP_ENV=dev)
if (function_exists('init_debugbar')) {
    $GLOBALS['_debugbar_renderer'] = init_debugbar($basePath ?: '/', $appEnv);
}

// Serve DebugBar assets (только при APP_ENV=dev)
if ($appEnv === 'dev') {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = $basePath && str_starts_with((string) $uri, $basePath) ? substr((string) $uri, strlen($basePath)) : $uri;
    if (str_starts_with($path, '/debugbar/')) {
        $file = $root . '/vendor/php-debugbar/php-debugbar/resources' . substr($path, 9);
        $vendorReal = realpath($root . '/vendor/php-debugbar');
        if ($file && is_file($file) && $vendorReal && str_starts_with(realpath($file), $vendorReal)) {
            $mimes = ['css' => 'text/css', 'js' => 'application/javascript', 'woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf'];
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
            readfile($file);
            exit;
        }
    }
}

$router = \App\Core\Router::fromConfig($root . '/app/config/routes.php');
$router->setContainer($container);
$router->setBasePath($basePath);
$router->dispatch();
