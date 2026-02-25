<?php

declare(strict_types=1);

return [
    'GET' => [
        '/' => [\App\Controllers\MainController::class, 'index'],
        '/detail/{id}' => [\App\Controllers\MainController::class, 'detail'],
        '/add' => [\App\Controllers\MainController::class, 'add'],
        '/edit-advert' => [\App\Controllers\MainController::class, 'myPosts'],
        '/edit/{id}' => [\App\Controllers\MainController::class, 'edit'],
        '/logout' => [\App\Controllers\UserController::class, 'logout'],
        '/api/check-email' => [\App\Controllers\ApiController::class, 'checkEmail'],
        '/api/captcha' => [\App\Controllers\ApiController::class, 'captcha'],
    ],
    'POST' => [
        '/add' => [\App\Controllers\MainController::class, 'addSubmit'],
        '/edit/{id}' => [\App\Controllers\MainController::class, 'editSubmit'],
        '/delete/{id}' => [\App\Controllers\MainController::class, 'delete'],
        '/login' => [\App\Controllers\UserController::class, 'login'],
        '/register' => [\App\Controllers\UserController::class, 'register'],
    ],
];
