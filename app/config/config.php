<?php

declare(strict_types=1);

$env = fn(string $key, string $default = ''): string => $_ENV[$key] ?? $default;

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
        'url' => '',
        'timezone' => 'Europe/Moscow',
    ],
    'session' => [
        'name' => 'm2saratov_sess',
        'lifetime' => 86400,
    ],
];
