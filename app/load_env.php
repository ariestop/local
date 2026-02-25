<?php

declare(strict_types=1);

/**
 * Простая загрузка .env без Composer.
 * Формат: KEY=value (строки с # — комментарии)
 */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1), " \t\n\r\0\x0B\"'");
        if ($key !== '') {
            $_ENV[$key] = $value;
        }
    }
}

$root = dirname(__DIR__);
load_env($root . '/.env');
load_env($root . '/.env.local');
