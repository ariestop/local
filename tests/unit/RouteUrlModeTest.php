<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouteUrlModeTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['SCRIPT_NAME']);
        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['HTTPS']);
        unset($_ENV['APP_USE_FRONT_CONTROLLER_URLS']);
        unset($_ENV['APP_URL']);
        putenv('APP_USE_FRONT_CONTROLLER_URLS');
        putenv('APP_URL');
    }

    public function testRouteUrlUsesCleanModeByDefault(): void
    {
        $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] = '0';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->assertSame('/detail/99', route_url('/detail/99'));
        $this->assertSame('', app_request_prefix());
        $this->assertSame('https://example.test/detail/99', absolute_url('/detail/99', [], 'https://example.test'));
    }

    public function testRouteUrlUsesFrontControllerModeWhenEnabled(): void
    {
        $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] = '1';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->assertSame('/index.php/detail/99', route_url('/detail/99'));
        $this->assertSame('/index.php', route_url('/'));
        $this->assertSame('/index.php', app_request_prefix());
        $this->assertSame('https://example.test/index.php/detail/99', absolute_url('/detail/99', [], 'https://example.test'));
    }

    public function testAbsoluteUrlFallsBackToEnvAppUrl(): void
    {
        $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] = '0';
        $_ENV['APP_URL'] = 'http://localhost:8888';

        $this->assertSame('http://localhost:8888/detail/55', absolute_url('/detail/55'));
    }

    public function testAbsoluteUrlSupportsQueryParametersInBothModes(): void
    {
        $_ENV['APP_USE_FRONT_CONTROLLER_URLS'] = '1';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $this->assertSame(
            'https://example.test/index.php?page=2&sort=price_desc',
            absolute_url('/', ['page' => '2', 'sort' => 'price_desc'], 'https://example.test')
        );
    }
}
