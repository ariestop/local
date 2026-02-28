<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouteUrlModeTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['SCRIPT_NAME']);
        unset($_ENV['APP_USE_FRONT_CONTROLLER_URLS']);
        putenv('APP_USE_FRONT_CONTROLLER_URLS');
    }

    public function testRouteUrlUsesCleanModeByDefault(): void
    {
        $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] = '0';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->assertSame('/detail/99', route_url('/detail/99'));
    }

    public function testRouteUrlUsesFrontControllerModeWhenEnabled(): void
    {
        $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] = '1';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->assertSame('/index.php/detail/99', route_url('/detail/99'));
        $this->assertSame('/index.php', route_url('/'));
    }
}
