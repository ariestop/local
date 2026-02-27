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
        'allow_legacy_password_login' => filter_var($env('AUTH_ALLOW_LEGACY_PASSWORD', '1'), FILTER_VALIDATE_BOOLEAN),
        'timezone' => 'Europe/Moscow',
        'max_price' => (int) ($env('MAX_PRICE', '999000000')),
        'history_limit' => max(1, (int) ($env('HISTORY_LIMIT', '10'))),
    ],
    'session' => [
        'name' => $env('SESSION_NAME', 'm2saratov_sess'),
        'lifetime' => (int) ($env('SESSION_LIFETIME', '86400')),
        'secure' => filter_var($env('SESSION_COOKIE_SECURE', '0'), FILTER_VALIDATE_BOOLEAN),
        'httponly' => filter_var($env('SESSION_COOKIE_HTTPONLY', '1'), FILTER_VALIDATE_BOOLEAN),
        'samesite' => $env('SESSION_COOKIE_SAMESITE', 'Lax'),
    ],
    'security' => [
        'headers_enabled' => filter_var($env('SECURITY_HEADERS_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN),
        'x_frame_options' => $env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),
        'x_content_type_options' => $env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'csp' => $env(
            'SECURITY_CSP',
            "default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
        ),
        'hsts_enabled' => filter_var($env('SECURITY_HSTS_ENABLED', '1'), FILTER_VALIDATE_BOOLEAN),
        'hsts_max_age' => max(0, (int) ($env('SECURITY_HSTS_MAX_AGE', '31536000'))),
        'hsts_include_subdomains' => filter_var($env('SECURITY_HSTS_INCLUDE_SUBDOMAINS', '1'), FILTER_VALIDATE_BOOLEAN),
        'hsts_preload' => filter_var($env('SECURITY_HSTS_PRELOAD', '0'), FILTER_VALIDATE_BOOLEAN),
    ],
    'images_path' => dirname(__DIR__, 2) . '/public/images',
    'log_path' => dirname(__DIR__, 2) . '/storage/logs',
];
