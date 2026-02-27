<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Repositories\UserRepository;
use App\Services\AppErrorService;
use App\Services\RateLimiter;

class ApiController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->userRepo = $container->get(UserRepository::class);
        $this->favoriteRepo = $container->get(FavoriteRepository::class);
        $this->rateLimiter = $container->get(RateLimiter::class);
        $this->errorService = $container->get(AppErrorService::class);
    }

    private UserRepository $userRepo;
    private FavoriteRepository $favoriteRepo;
    private RateLimiter $rateLimiter;
    private AppErrorService $errorService;

    public function checkEmail(): void
    {
        $rate = $this->rateLimiter->hit($this->rateKey('check-email'), 60, 60);
        if (!$rate['allowed']) {
            $this->json([
                'success' => false,
                'error' => 'Слишком много запросов. Повторите позже.',
                'code' => 429,
                'retry_after' => $rate['retry_after'],
            ], 429);
            return;
        }
        $email = trim($_GET['email'] ?? '');
        if (!$email) {
            $this->json(['success' => true, 'exists' => false]);
            return;
        }
        $this->json([
            'success' => true,
            'exists' => $this->userRepo->emailExists($email),
        ]);
    }

    public function captcha(): void
    {
        $rate = $this->rateLimiter->hit($this->rateKey('captcha'), 30, 300);
        if (!$rate['allowed']) {
            $this->json([
                'success' => false,
                'error' => 'Слишком много запросов капчи. Повторите позже.',
                'code' => 429,
                'retry_after' => $rate['retry_after'],
            ], 429);
            return;
        }

        ensure_session();
        $code = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5);
        $_SESSION['captcha'] = $code;

        $w = 120;
        $h = 40;
        $img = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($img, 245, 245, 245);
        $text = imagecolorallocate($img, 50, 50, 50);
        $noise = imagecolorallocate($img, 180, 180, 180);
        imagefill($img, 0, 0, $bg);
        for ($i = 0; $i < 50; $i++) {
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $noise);
        }
        imagestring($img, 5, 25, 12, $code, $text);
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store');
        imagepng($img);
        imagedestroy($img);
    }

    public function toggleFavorite(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $user = $this->getLoggedUser();
        if (!$user) {
            $this->json([
                'success' => false,
                'error' => 'Требуется авторизация',
                'code' => 401,
                'added' => false,
            ], 401);
            return;
        }
        $postId = (int) ($_POST['post_id'] ?? $_GET['post_id'] ?? 0);
        if (!$postId) {
            $this->jsonError('Некорректный ID', 400);
            return;
        }
        $added = $this->favoriteRepo->toggle((int) $user['id'], $postId);
        $this->json(['success' => true, 'added' => $added]);
    }

    public function clientError(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $rate = $this->rateLimiter->hit($this->rateKey('client-error'), 30, 60);
        if (!$rate['allowed']) {
            $this->json([
                'success' => false,
                'error' => 'Слишком много сообщений об ошибках',
                'code' => 429,
                'retry_after' => $rate['retry_after'],
            ], 429);
            return;
        }

        $payload = [];
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }
        if ($payload === []) {
            $payload = $_POST;
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            $this->jsonError('Пустое сообщение ошибки', 400);
            return;
        }

        $user = $this->getLoggedUser();
        $userId = $user ? (int) $user['id'] : null;
        try {
            $this->errorService->reportClientError($payload, $userId);
            $this->json(['success' => true]);
        } catch (\Throwable $e) {
            $this->jsonError('Не удалось сохранить ошибку', 500);
        }
    }

    private function rateKey(string $scope): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'api:' . $scope . ':' . $ip;
    }
}
