<?php

function ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrf_token(): string
{
    ensure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function app_entry_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptName === '') {
        return '';
    }
    $entry = '/' . ltrim($scriptName, '/');
    return rtrim($entry, '/');
}

function app_base_path(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptName !== '') {
        $runtimeBase = rtrim(dirname($scriptName), '/');
        if ($runtimeBase !== '' && $runtimeBase !== '/' && $runtimeBase !== '.' && $runtimeBase !== '/.') {
            return $runtimeBase;
        }
        return '';
    }

    $configuredAppUrl = (string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: '');
    $path = rtrim((string) (parse_url($configuredAppUrl, PHP_URL_PATH) ?: ''), '/');
    if ($path === '' || $path === '/') {
        return '';
    }
    return $path;
}

function use_front_controller_urls(): bool
{
    $raw = (string) ($_ENV['APP_USE_FRONT_CONTROLLER_URLS'] ?? getenv('APP_USE_FRONT_CONTROLLER_URLS') ?: '0');
    return filter_var($raw, FILTER_VALIDATE_BOOLEAN);
}

function app_request_prefix(): string
{
    if (!use_front_controller_urls()) {
        return app_base_path();
    }
    $entry = app_entry_path();
    if ($entry !== '') {
        return $entry;
    }
    return app_base_path();
}

function route_url(string $path = '/'): string
{
    $normalized = '/' . ltrim($path, '/');
    if (!use_front_controller_urls()) {
        return $normalized;
    }
    $entry = app_entry_path();
    if ($entry === '') {
        return $normalized;
    }
    if ($normalized === '/') {
        return $entry;
    }
    return $entry . $normalized;
}

function absolute_url(string $path = '/', array $query = [], ?string $baseOverride = null): string
{
    $base = rtrim((string) ($baseOverride ?? ''), '/');
    if ($base === '') {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: ''), '/');
    }
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = $scheme . '://' . $host;
    }

    $url = $base . route_url($path);
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }
    return $url;
}

function _photo_url(int $userId, int $postId, string $filename, string $suffix): string
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return "/images/{$userId}/{$postId}/{$base}_{$suffix}.{$ext}";
}

function photo_thumb_url(int $userId, int $postId, string $filename, int $w, int $h): string
{
    return _photo_url($userId, $postId, $filename, "{$w}x{$h}");
}

function photo_large_url(int $userId, int $postId, string $filename): string
{
    return _photo_url($userId, $postId, $filename, '1200x675');
}
