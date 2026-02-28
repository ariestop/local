<?php

declare(strict_types=1);

use App\Controllers\ApiController;
use App\Repositories\FavoriteRepository;
use App\Repositories\UserRepository;
use App\Services\AppErrorService;
use App\Services\RateLimiter;
use PHPUnit\Framework\TestCase;
use Tests\Bootstrap\TestContainer;

final class ApiControllerContractTest extends TestCase
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

    public function testCheckEmailReturns429ContractWhenRateLimited(): void
    {
        $controller = $this->makeController(['allowed' => false, 'retry_after' => 10]);
        $_GET['email'] = 'user@example.test';

        $response = $this->invokeAndDecode(fn() => $controller->checkEmail());

        $this->assertFalse((bool) ($response['json']['success'] ?? true));
        $this->assertSame(429, $response['json']['code'] ?? null);
        $this->assertSame(10, $response['json']['retry_after'] ?? null);
    }

    public function testToggleFavoriteRequiresAuthAndReturnsContract(): void
    {
        $controller = $this->makeController(['allowed' => true, 'retry_after' => 0]);
        $_POST = [
            'post_id' => '123',
            'csrf_token' => csrf_token(),
        ];

        $response = $this->invokeAndDecode(fn() => $controller->toggleFavorite());

        $this->assertFalse((bool) ($response['json']['success'] ?? true));
        $this->assertSame(401, $response['json']['code'] ?? null);
        $this->assertArrayHasKey('added', $response['json']);
    }

    public function testCheckEmailSuccessReturnsExistsField(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('emailExists')
            ->with('exists@example.test')
            ->willReturn(true);

        $controller = $this->makeController(
            ['allowed' => true, 'retry_after' => 0],
            userRepository: $userRepo
        );
        $_GET['email'] = 'exists@example.test';

        $response = $this->invokeAndDecode(fn() => $controller->checkEmail());

        $this->assertTrue((bool) ($response['json']['success'] ?? false));
        $this->assertTrue((bool) ($response['json']['exists'] ?? false));
    }

    public function testToggleFavoriteWithoutPostIdReturns400Contract(): void
    {
        $_SESSION['user'] = ['id' => 7, 'email' => 'u@example.test'];
        $controller = $this->makeController(['allowed' => true, 'retry_after' => 0]);
        $_POST = [
            'csrf_token' => csrf_token(),
        ];

        $response = $this->invokeAndDecode(fn() => $controller->toggleFavorite());

        $this->assertFalse((bool) ($response['json']['success'] ?? true));
        $this->assertSame(400, $response['json']['code'] ?? null);
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

    /**
     * @param array{allowed:bool,retry_after:int} $rateResult
     */
    private function makeController(
        array $rateResult,
        ?UserRepository $userRepository = null,
        ?FavoriteRepository $favoriteRepository = null
    ): ApiController
    {
        $userRepo = $userRepository ?? $this->createMock(UserRepository::class);
        $favoriteRepo = $favoriteRepository ?? $this->createMock(FavoriteRepository::class);
        $rateLimiter = $this->createMock(RateLimiter::class);
        $errorService = $this->createMock(AppErrorService::class);
        $rateLimiter->method('hit')->willReturn($rateResult);

        $container = new TestContainer(
            [
                'app' => ['url' => 'http://localhost'],
                'db' => [],
            ],
            [
                \PDO::class => new \PDO('sqlite::memory:'),
                UserRepository::class => $userRepo,
                FavoriteRepository::class => $favoriteRepo,
                RateLimiter::class => $rateLimiter,
                AppErrorService::class => $errorService,
            ]
        );

        return new ApiController($container);
    }
}
