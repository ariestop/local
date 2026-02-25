<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/load_env.php';
require dirname(__DIR__) . '/app/autoload.php';
require dirname(__DIR__) . '/app/helpers.php';

date_default_timezone_set('Europe/Moscow');

if (session_status() === PHP_SESSION_NONE) {
    session_name('m2saratov_sess');
    session_start();
}

$router = \App\Core\Router::fromConfig(dirname(__DIR__) . '/app/config/routes.php');
$router->dispatch();
