<?php

declare(strict_types=1);

namespace App\Log;

/**
 * Адаптер Monolog под App\Log\LoggerInterface.
 */
class MonologAdapter implements LoggerInterface
{
    public function __construct(
        private \Monolog\Logger $logger
    ) {}

    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->logger->info((string) $message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        $this->logger->warning((string) $message, $context);
    }
}
