<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/bootstrap/TestContainer.php';

// CLI tests do not need cookie/session headers.
ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
