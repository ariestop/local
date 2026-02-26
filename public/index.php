<?php

declare(strict_types=1);

$container = require dirname(__DIR__) . '/app/bootstrap.php';

$config = $container->getConfig();
$basePath = rtrim(parse_url($config['app']['url'] ?? '', PHP_URL_PATH) ?: '', '/');

$router = \App\Core\Router::fromConfig(dirname(__DIR__) . '/app/config/routes.php');
$router->setContainer($container);
$router->setBasePath($basePath);
$router->dispatch();
