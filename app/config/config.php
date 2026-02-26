<?php

declare(strict_types=1);

$env = fn(string $key, string $default = ''): string => $_ENV[$key] ?? (string) getenv($key) ?: $default;

return [
    'db' => [
        'host' => $env('DB_HOST', 'MySQL-8.0'),
        'dbname' => $env('DB_NAME', 'infosee2_m2sar'),
        'charset' => $env('DB_CHARSET', 'utf8mb4'),
        'user' => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', ''),
    ],
    'app' => [
        'name' => 'Квадратный метр',
        'url' => $env('APP_URL', 'http://localhost/test/public'),
        'env' => $env('APP_ENV', 'production'),
        'email_confirm_required' => filter_var($env('EMAIL_CONFIRM_REQUIRED', '0'), FILTER_VALIDATE_BOOLEAN),
        'timezone' => 'Europe/Moscow',
        'max_price' => (int) ($env('MAX_PRICE', '999000000')),
    ],
    'session' => [
        'name' => $env('SESSION_NAME', 'm2saratov_sess'),
        'lifetime' => 86400,
    ],
    'images_path' => dirname(__DIR__, 2) . '/public/images',
    'log_path' => dirname(__DIR__, 2) . '/storage/logs',
];
