<?php

function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
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

function photo_thumb_url(int $userId, int $postId, string $filename, int $w, int $h): string
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return "/images/{$userId}/{$postId}/{$base}_{$w}x{$h}.{$ext}";
}

function photo_large_url(int $userId, int $postId, string $filename): string
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    return "/images/{$userId}/{$postId}/{$base}_1200x675.{$ext}";
}
