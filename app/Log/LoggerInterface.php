<?php

declare(strict_types=1);

namespace App\Log;

/**
 * Минимальный интерфейс логгера (совместим с PSR-3).
 * При наличии Monolog используется Psr\Log\LoggerInterface.
 */
interface LoggerInterface
{
    public function info(\Stringable|string $message, array $context = []): void;
    public function warning(\Stringable|string $message, array $context = []): void;
}
