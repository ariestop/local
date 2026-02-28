<?php

declare(strict_types=1);

$env = fn(string $key, string $default = ''): string => $_ENV[$key] ?? (string) getenv($key) ?: $default;
$envBool = fn(string $key, string $default = '0'): bool => filter_var($env($key, $default), FILTER_VALIDATE_BOOLEAN);
$envInt = fn(string $key, string $default = '0'): int => (int) $env($key, $default);

return [
    'db' => [
        'host' => $env('DB_HOST', 'localhost'),
        'port' => $envInt('DB_PORT', '3306'),
        'dbname' => $env('DB_NAME', 'infosee2_m2'),
        'charset' => $env('DB_CHARSET', 'utf8mb4'),
        'user' => $env('DB_USER', 'root'),
        'password' => $env('DB_PASSWORD', 'root'),
        'dump_path' => $env('DB_DUMP_PATH', 'public/infosee2_m2sar.sql'),
    ],
    'app' => [
        'name' => 'Квадратный метр',
        'url' => $env('APP_URL', 'http://localhost:8888/local/public_html'),
        'env' => $env('APP_ENV', 'production'),
        'email_confirm_required' => $envBool('EMAIL_CONFIRM_REQUIRED', '0'),
        'allow_legacy_password_login' => $envBool('AUTH_ALLOW_LEGACY_PASSWORD', '1'),
        'timezone' => 'Europe/Moscow',
        'max_price' => $envInt('MAX_PRICE', '999000000'),
        'history_limit' => max(1, $envInt('HISTORY_LIMIT', '10')),
    ],
    'session' => [
        'name' => $env('SESSION_NAME', 'm2saratov_sess'),
        'lifetime' => $envInt('SESSION_LIFETIME', '86400'),
        'secure' => $envBool('SESSION_COOKIE_SECURE', '0'),
        'httponly' => $envBool('SESSION_COOKIE_HTTPONLY', '1'),
        'samesite' => $env('SESSION_COOKIE_SAMESITE', 'Lax'),
    ],
    'security' => [
        'headers_enabled' => $envBool('SECURITY_HEADERS_ENABLED', '1'),
        'x_frame_options' => $env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_content_type_options' => $env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'csp' => $env(
            'SECURITY_CSP',
            "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
        ),
        'hsts_enabled' => $envBool('SECURITY_HSTS_ENABLED', '1'),
        'hsts_max_age' => max(0, $envInt('SECURITY_HSTS_MAX_AGE', '31536000')),
        'hsts_include_subdomains' => $envBool('SECURITY_HSTS_INCLUDE_SUBDOMAINS', '1'),
        'hsts_preload' => $envBool('SECURITY_HSTS_PRELOAD', '0'),
    ],
    'images_path' => dirname(__DIR__, 2) . '/public_html/images',
    'log_path' => dirname(__DIR__, 2) . '/storage/logs',
];
