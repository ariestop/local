<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/autoload.php';
require dirname(__DIR__) . '/app/helpers.php';

date_default_timezone_set('Europe/Moscow');

if (session_status() === PHP_SESSION_NONE) {
    session_name('m2saratov_sess');
    session_start();
}

$router = new \App\Core\Router();

$router->get('/', [\App\Controllers\MainController::class, 'index']);
$router->get('/detail/{id}', [\App\Controllers\MainController::class, 'detail']);
$router->get('/add', [\App\Controllers\MainController::class, 'add']);
$router->post('/add', [\App\Controllers\MainController::class, 'addSubmit']);
$router->get('/edit-advert', [\App\Controllers\MainController::class, 'myPosts']);
$router->get('/edit/{id}', [\App\Controllers\MainController::class, 'edit']);
$router->post('/edit/{id}', [\App\Controllers\MainController::class, 'editSubmit']);
$router->post('/delete/{id}', [\App\Controllers\MainController::class, 'delete']);

$router->post('/login', [\App\Controllers\UserController::class, 'login']);
$router->post('/register', [\App\Controllers\UserController::class, 'register']);
$router->get('/logout', [\App\Controllers\UserController::class, 'logout']);

$router->get('/api/check-email', [\App\Controllers\ApiController::class, 'checkEmail']);
$router->get('/api/captcha', [\App\Controllers\ApiController::class, 'captcha']);

$router->dispatch();
