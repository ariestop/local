<?php

declare(strict_types=1);

namespace Tests\Bootstrap;

use App\Core\Container;
use InvalidArgumentException;

final class TestContainer extends Container
{
    /**
     * @param array<string,mixed> $config
     * @param array<string,mixed> $services
     */
    public function __construct(
        private array $config,
        private array $services
    ) {
        // Intentionally bypasses parent constructor and uses provided stubs.
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        throw new InvalidArgumentException('Service not stubbed in TestContainer: ' . $id);
    }
}
