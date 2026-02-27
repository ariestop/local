<?php

declare(strict_types=1);

/**
 * Bootstrap приложения: загрузка .env, autoload, конфиг, контейнер.
 */

$root = dirname(__DIR__);

if (file_exists($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
    $dotenv = \Dotenv\Dotenv::createImmutable($root);
    $dotenv->safeLoad();
} else {
    require $root . '/app/load_env.php';
    require $root . '/app/autoload.php';
    require $root . '/app/helpers.php';
}

date_default_timezone_set('Europe/Moscow');

$config = require $root . '/app/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session']['name'] ?? 'm2saratov_sess');
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
    );
    $cookieSecure = (bool) ($config['session']['secure'] ?? false) || $isHttps;
    session_set_cookie_params([
        'lifetime' => (int) ($config['session']['lifetime'] ?? 86400),
        'path' => '/',
        'secure' => $cookieSecure,
        'httponly' => (bool) ($config['session']['httponly'] ?? true),
        'samesite' => (string) ($config['session']['samesite'] ?? 'Lax'),
    ]);
    session_start();
}

$container = new \App\Core\Container($config);

// Debug Bar (только dev) — basePath передаём позже из index.php
if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/app/debugbar.php';
}

return $container;
