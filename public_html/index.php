<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$container = require $root . '/app/bootstrap.php';

$config = $container->getConfig();
$basePath = app_base_path();
$useFrontControllerUrls = use_front_controller_urls();
$scriptEntry = app_entry_path();

$appEnv = $config['app']['env'] ?? $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';

$security = $config['security'] ?? [];
if (($security['headers_enabled'] ?? true) && !headers_sent()) {
    header('X-Frame-Options: ' . ($security['x_frame_options'] ?? 'SAMEORIGIN'));
    header('X-Content-Type-Options: ' . ($security['x_content_type_options'] ?? 'nosniff'));

    $csp = trim((string) ($security['csp'] ?? ''));
    if ($csp !== '') {
        header('Content-Security-Policy: ' . $csp);
    }

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
    );
    if ($isHttps && ($security['hsts_enabled'] ?? true)) {
        $hsts = 'max-age=' . max(0, (int) ($security['hsts_max_age'] ?? 31536000));
        if (!empty($security['hsts_include_subdomains'])) {
            $hsts .= '; includeSubDomains';
        }
        if (!empty($security['hsts_preload'])) {
            $hsts .= '; preload';
        }
        header('Strict-Transport-Security: ' . $hsts);
    }
}

// Debug Bar init (только APP_ENV=dev)
if (function_exists('init_debugbar')) {
    $debugbarBasePath = ($useFrontControllerUrls && $scriptEntry !== '')
        ? $scriptEntry
        : ($basePath ?: '/');
    $GLOBALS['_debugbar_renderer'] = init_debugbar($debugbarBasePath, $appEnv);
}

// Serve DebugBar assets (только при APP_ENV=dev)
if ($appEnv === 'dev') {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = (string) ($uri ?: '/');
    if ($basePath !== '' && str_starts_with($path, $basePath)) {
        $path = substr($path, strlen($basePath)) ?: '/';
    }
    if ($useFrontControllerUrls && $scriptEntry !== '' && str_starts_with($path, $scriptEntry)) {
        $path = substr($path, strlen($scriptEntry)) ?: '/';
    }
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

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'GET') {
    ob_start();
    $router->dispatch();
    $body = (string) ob_get_clean();

    if (!headers_sent()) {
        $etag = '"' . sha1($body) . '"';
        header('ETag: ' . $etag);

        $requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        if (str_contains($requestPath, '/api/')) {
            header('Cache-Control: no-store, max-age=0');
        } else {
            header('Cache-Control: private, max-age=60, must-revalidate');
        }

        $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            http_response_code(304);
            exit;
        }
    }

    echo $body;
    exit;
}

$router->dispatch();
