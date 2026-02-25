<?php

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
