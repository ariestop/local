<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Services\AuthService;
use App\Services\RateLimiter;
use PHPUnit\Framework\TestCase;
use Tests\Bootstrap\TestContainer;

final class UserControllerTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }
        $_POST = [];
        $_GET = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    public function testLoginSuccessReturnsUserPayload(): void
    {
        $auth = $this->createMock(AuthService::class);
        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->method('hit')->willReturn(['allowed' => true, 'retry_after' => 0]);
        $auth->method('login')->willReturn([
            'success' => true,
            'user' => ['id' => 7, 'email' => 'user@example.test', 'name' => 'Test', 'is_admin' => 0],
        ]);

        $controller = new UserController($this->makeContainer($auth, $rateLimiter));
        $_POST = [
            'email' => 'user@example.test',
            'password' => 'secret',
            'csrf_token' => $this->issueCsrfToken(),
        ];

        $response = $this->invokeAndDecode(fn() => $controller->login());

        $this->assertTrue((bool) ($response['json']['success'] ?? false));
        $this->assertSame(7, $response['json']['user']['id'] ?? null);
    }

    public function testLoginWithoutCsrfReturns403Contract(): void
    {
        $auth = $this->createMock(AuthService::class);
        $rateLimiter = $this->createMock(RateLimiter::class);
        $controller = new UserController($this->makeContainer($auth, $rateLimiter));
        $_POST = ['email' => 'user@example.test', 'password' => 'secret'];

        $response = $this->invokeAndDecode(fn() => $controller->login());

        $this->assertFalse((bool) ($response['json']['success'] ?? true));
        $this->assertSame(403, $response['json']['code'] ?? null);
    }

    public function testLoginRateLimitReturns429AndRetryAfter(): void
    {
        $auth = $this->createMock(AuthService::class);
        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->method('hit')->willReturn(['allowed' => false, 'retry_after' => 42]);
        $controller = new UserController($this->makeContainer($auth, $rateLimiter));
        $_POST = [
            'email' => 'user@example.test',
            'password' => 'secret',
            'csrf_token' => $this->issueCsrfToken(),
        ];

        $response = $this->invokeAndDecode(fn() => $controller->login());

        $this->assertFalse((bool) ($response['json']['success'] ?? true));
        $this->assertSame(429, $response['json']['code'] ?? null);
        $this->assertSame(42, $response['json']['retry_after'] ?? null);
    }

    private function issueCsrfToken(): string
    {
        return csrf_token();
    }

    /**
     * @return array{json:array<string,mixed>,raw:string}
     */
    private function invokeAndDecode(callable $handler): array
    {
        ob_start();
        $handler();
        $raw = (string) ob_get_clean();
        $decoded = json_decode($raw, true);

        return [
            'json' => is_array($decoded) ? $decoded : [],
            'raw' => $raw,
        ];
    }

    private function makeContainer(AuthService $authService, RateLimiter $rateLimiter): TestContainer
    {
        return new TestContainer(
            [
                'app' => ['url' => 'http://localhost'],
                'db' => [],
            ],
            [
                \PDO::class => new \PDO('sqlite::memory:'),
                AuthService::class => $authService,
                RateLimiter::class => $rateLimiter,
            ]
        );
    }
}
