<?php

declare(strict_types=1);

namespace App\Log;

class NullLogger implements LoggerInterface
{
    public function info(\Stringable|string $message, array $context = []): void {}
    public function warning(\Stringable|string $message, array $context = []): void {}
}
