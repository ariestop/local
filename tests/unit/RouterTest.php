<?php

declare(strict_types=1);

use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        RouterTestProbeController::$calls = [];
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        if (function_exists('header_remove')) {
            header_remove();
        }
    }

    protected function tearDown(): void
    {
        if (function_exists('header_remove')) {
            header_remove();
        }
    }

    public function testStaticRouteWithDotDoesNotRegexMatchAnotherPath(): void
    {
        $router = (new Router())->get('/sitemap.xml', [RouterTestProbeController::class, 'ping']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/sitemap-xml';

        ob_start();
        $router->dispatch();
        ob_get_clean();

        $this->assertSame([], RouterTestProbeController::$calls);
    }

    public function testPlaceholderRouteExtractsParameter(): void
    {
        $router = (new Router())->get('/detail/{id}', [RouterTestProbeController::class, 'ping']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/detail/9673';

        ob_start();
        $router->dispatch();
        ob_get_clean();

        $this->assertSame([['action' => 'ping', 'args' => ['9673']]], RouterTestProbeController::$calls);
    }

    public function testMethodMismatchReturns405(): void
    {
        $router = (new Router())->post('/login', [RouterTestProbeController::class, 'ping']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/login';

        ob_start();
        $router->dispatch();
        $body = (string) ob_get_clean();

        $this->assertStringContainsString('405', $body);
        $this->assertSame([], RouterTestProbeController::$calls);
    }

    public function testMissingActionDoesNotCauseFatalAndFallsBackTo404(): void
    {
        $router = (new Router())->get('/broken', [RouterTestProbeController::class, 'missingAction']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/broken';

        ob_start();
        $router->dispatch();
        ob_get_clean();

        $this->assertSame([], RouterTestProbeController::$calls);
    }

    public function testFrontControllerPrefixedUriIsDispatchedToSameRoute(): void
    {
        $router = (new Router())->get('/detail/{id}', [RouterTestProbeController::class, 'ping']);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/index.php/detail/123';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        ob_start();
        $router->dispatch();
        ob_get_clean();

        $this->assertSame([['action' => 'ping', 'args' => ['123']]], RouterTestProbeController::$calls);
    }
}

final class RouterTestProbeController
{
    /** @var array<int, array{action: string, args: array<int, string>}> */
    public static array $calls = [];

    public function ping(string $id): void
    {
        self::$calls[] = ['action' => 'ping', 'args' => [$id]];
    }
}
