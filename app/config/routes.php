<?php

declare(strict_types=1);

return [
    'GET' => [
        '/' => [\App\Controllers\MainController::class, 'index'],
        '/detail/{id}' => [\App\Controllers\MainController::class, 'detail'],
        '/add' => [\App\Controllers\MainController::class, 'add'],
        '/edit-advert' => [\App\Controllers\MainController::class, 'myPosts'],
        '/edit/{id}' => [\App\Controllers\MainController::class, 'edit'],
        '/favorites' => [\App\Controllers\MainController::class, 'favorites'],
        '/admin' => [\App\Controllers\AdminController::class, 'report'],
        '/admin-migrations' => [\App\Controllers\AdminController::class, 'migrations'],
        '/verify-email' => [\App\Controllers\UserController::class, 'verifyEmail'],
        '/forgot-password' => [\App\Controllers\UserController::class, 'forgotPassword'],
        '/reset-password' => [\App\Controllers\UserController::class, 'resetPassword'],
        '/logout' => [\App\Controllers\UserController::class, 'logout'],
        '/api/check-email' => [\App\Controllers\ApiController::class, 'checkEmail'],
        '/api/captcha' => [\App\Controllers\ApiController::class, 'captcha'],
    ],
    'POST' => [
        '/add' => [\App\Controllers\MainController::class, 'addSubmit'],
        '/edit/{id}' => [\App\Controllers\MainController::class, 'editSubmit'],
        '/delete/{id}' => [\App\Controllers\MainController::class, 'delete'],
        '/delete-hard/{id}' => [\App\Controllers\MainController::class, 'hardDelete'],
        '/restore/{id}' => [\App\Controllers\MainController::class, 'restore'],
        '/api/favorite/toggle' => [\App\Controllers\ApiController::class, 'toggleFavorite'],
        '/api/client-error' => [\App\Controllers\ApiController::class, 'clientError'],
        '/admin-migrations/apply-next' => [\App\Controllers\AdminController::class, 'applyNextMigration'],
        '/admin-migrations/apply' => [\App\Controllers\AdminController::class, 'applyMigration'],
        '/admin/expire-posts' => [\App\Controllers\AdminController::class, 'runExpirePosts'],
        '/admin/expire-posts-batch' => [\App\Controllers\AdminController::class, 'runExpirePostsBatch'],
        '/login' => [\App\Controllers\UserController::class, 'login'],
        '/register' => [\App\Controllers\UserController::class, 'register'],
        '/forgot-password' => [\App\Controllers\UserController::class, 'forgotPasswordSubmit'],
        '/reset-password' => [\App\Controllers\UserController::class, 'resetPasswordSubmit'],
    ],
];
