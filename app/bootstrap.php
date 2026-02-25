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
    session_start();
}

$container = new \App\Core\Container($config);

return $container;
