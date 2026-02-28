<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "[ERROR] This script must be run from CLI.\n");
    exit(1);
}

$root = __DIR__;
/** @var \App\Core\Container $container */
$container = require $root . '/app/bootstrap.php';

$command = (string) ($argv[1] ?? '');

if ($command === '' || in_array($command, ['-h', '--help'], true)) {
    echo "Usage:\n";
    echo "  php cron.php expire-posts [limit]\n";
    exit(0);
}

try {
    switch ($command) {
        case 'expire-posts':
            $limit = max(1, (int) ($argv[2] ?? 100));
            /** @var \App\Services\PostService $service */
            $service = $container->get(\App\Services\PostService::class);
            $result = $service->processExpiredListings($limit);
            echo '[INFO] Processed: ' . (int) ($result['processed'] ?? 0) . PHP_EOL;
            echo '[INFO] Archived: ' . (int) ($result['archived'] ?? 0) . PHP_EOL;
            echo '[INFO] Notified: ' . (int) ($result['notified'] ?? 0) . PHP_EOL;
            exit(0);
        default:
            fwrite(STDERR, "[ERROR] Unknown command: {$command}\n");
            exit(2);
    }
} catch (Throwable $e) {
    fwrite(STDERR, '[ERROR] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

