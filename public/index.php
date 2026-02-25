<?php

declare(strict_types=1);

$container = require dirname(__DIR__) . '/app/bootstrap.php';

$router = \App\Core\Router::fromConfig(dirname(__DIR__) . '/app/config/routes.php');
$router->setContainer($container);
$router->dispatch();
