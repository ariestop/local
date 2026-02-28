<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Services\AuthService;
use App\Services\RateLimiter;
use PHPUnit\Framework\TestCase;
use Tests\Bootstrap\TestContainer;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
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
        $sessionBefore = session_id();

        $response = $this->invokeAndDecode(fn() => $controller->login());
        $sessionAfter = session_id();

        $this->assertTrue((bool) ($response['json']['success'] ?? false));
        $this->assertSame(7, $response['json']['user']['id'] ?? null);
        $this->assertNotSame($sessionBefore, $sessionAfter);
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

    public function testRegisterSuccessWithoutEmailConfirmationCreatesSessionAndClearsCaptcha(): void
    {
        $auth = $this->createMock(AuthService::class);
        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->method('hit')->willReturn(['allowed' => true, 'retry_after' => 0]);
        $auth->method('register')->willReturn([
            'success' => true,
            'email_confirm_required' => false,
            'user' => ['id' => 9, 'email' => 'new@example.test', 'name' => 'New'],
        ]);

        $controller = new UserController($this->makeContainer($auth, $rateLimiter));
        $_SESSION['captcha'] = 'ABCDE';
        $_POST = [
            'email' => 'new@example.test',
            'password' => 'secret123',
            'name' => 'New',
            'captcha' => 'ABCDE',
            'csrf_token' => $this->issueCsrfToken(),
        ];
        $sessionBefore = session_id();

        $response = $this->invokeAndDecode(fn() => $controller->register());
        $sessionAfter = session_id();

        $this->assertTrue((bool) ($response['json']['success'] ?? false));
        $this->assertSame(9, $_SESSION['user']['id'] ?? null);
        $this->assertNotSame($sessionBefore, $sessionAfter);
        $this->assertArrayNotHasKey('captcha', $_SESSION);
    }

    public function testForgotPasswordRateLimitReturns429AndRetryAfter(): void
    {
        $auth = $this->createMock(AuthService::class);
        $rateLimiter = $this->createMock(RateLimiter::class);
        $rateLimiter->method('hit')->willReturn(['allowed' => false, 'retry_after' => 55]);
        $controller = new UserController($this->makeContainer($auth, $rateLimiter));
        $_POST = [
            'email' => 'user@example.test',
            'csrf_token' => $this->issueCsrfToken(),
        ];

        $response = $this->invokeAndDecode(fn() => $controller->forgotPasswordSubmit());

        $this->assertFalse((bool) ($response['json']['success'] ?? true));
        $this->assertSame(429, $response['json']['code'] ?? null);
        $this->assertSame(55, $response['json']['retry_after'] ?? null);
    }

    public function testResetPasswordSubmitMismatchedPasswordsReturns400Contract(): void
    {
        $auth = $this->createMock(AuthService::class);
        $rateLimiter = $this->createMock(RateLimiter::class);
        $controller = new UserController($this->makeContainer($auth, $rateLimiter));
        $_POST = [
            'token' => 'tkn',
            'password' => 'a',
            'password2' => 'b',
            'csrf_token' => $this->issueCsrfToken(),
        ];

        $response = $this->invokeAndDecode(fn() => $controller->resetPasswordSubmit());

        $this->assertFalse((bool) ($response['json']['success'] ?? true));
        $this->assertSame(400, $response['json']['code'] ?? null);
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
