<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Services\AuthService;
use App\Services\RateLimiter;

class UserController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->authService = $container->get(AuthService::class);
        $this->rateLimiter = $container->get(RateLimiter::class);
    }

    private AuthService $authService;
    private RateLimiter $rateLimiter;

    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $rate = $this->rateLimiter->hit($this->rateKey('login'), 10, 600);
        if (!$rate['allowed']) {
            $this->json([
                'success' => false,
                'error' => 'Слишком много попыток входа. Повторите позже.',
                'code' => 429,
                'retry_after' => $rate['retry_after'],
            ], 429);
            return;
        }
        if ($this->getLoggedUser()) {
            $this->json(['success' => true, 'user' => $this->getLoggedUser()]);
            return;
        }
        $result = $this->authService->login($_POST);
        if (!$result['success']) {
            $this->jsonError($result['error'] ?? 'Ошибка', (int) ($result['code'] ?? 400));
            return;
        }
        $this->establishAuthenticatedSession($result['user']);
        $this->json(['success' => true, 'user' => $result['user']]);
    }

    public function register(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $rate = $this->rateLimiter->hit($this->rateKey('register'), 5, 900);
        if (!$rate['allowed']) {
            $this->json([
                'success' => false,
                'error' => 'Слишком много попыток регистрации. Повторите позже.',
                'code' => 429,
                'retry_after' => $rate['retry_after'],
            ], 429);
            return;
        }
        $expected = $_SESSION['captcha'] ?? '';
        $result = $this->authService->register($_POST, $expected);
        if (!$result['success']) {
            if (isset($_SESSION['captcha'])) {
                unset($_SESSION['captcha']);
            }
            $this->jsonError($result['error'] ?? 'Ошибка', (int) ($result['code'] ?? 400));
            return;
        }
        unset($_SESSION['captcha']);
        if (empty($result['email_confirm_required'])) {
            $this->establishAuthenticatedSession($result['user']);
            $this->json(['success' => true, 'message' => 'Регистрация успешна', 'user' => $result['user']]);
        } else {
            $this->json(['success' => true, 'message' => 'Проверьте почту для подтверждения регистрации']);
        }
    }

    public function verifyEmail(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (!$token) {
            $this->render('main/verify-email', ['success' => false, 'error' => 'Нет токена']);
            return;
        }
        $result = $this->authService->verifyEmail($token);
        if (!$result['success']) {
            $this->render('main/verify-email', ['success' => false, 'error' => $result['error']]);
            return;
        }
        $this->establishAuthenticatedSession($result['user']);
        $this->render('main/verify-email', ['success' => true]);
    }

    public function forgotPassword(): void
    {
        $this->render('main/forgot-password', []);
    }

    public function forgotPasswordSubmit(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError('Ошибка безопасности', 403);
            return;
        }
        $rate = $this->rateLimiter->hit($this->rateKey('forgot-password'), 5, 900);
        if (!$rate['allowed']) {
            $this->json([
                'success' => false,
                'error' => 'Слишком много запросов на восстановление. Повторите позже.',
                'code' => 429,
                'retry_after' => $rate['retry_after'],
            ], 429);
            return;
        }
        $email = trim($_POST['email'] ?? '');
        if (!$email) {
            $this->jsonError('Введите email', 400);
            return;
        }
        try {
            $result = $this->authService->requestPasswordReset($email);
            $this->json($result);
        } catch (\Throwable $e) {
            $this->jsonError('Временная ошибка сервера. Попробуйте позже.', 500);
        }
    }

    public function resetPassword(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (!$token) {
            $this->render('main/reset-password', ['error' => 'Нет токена', 'token' => '']);
            return;
        }
        $this->render('main/reset-password', ['token' => $token, 'error' => null]);
    }

    public function resetPasswordSubmit(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError('Ошибка безопасности', 403);
            return;
        }
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if ($password !== $password2) {
            $this->jsonError('Пароли не совпадают', 400);
            return;
        }
        $result = $this->authService->resetPassword($token, $password);
        if (!$result['success']) {
            $this->jsonError($result['error'] ?? 'Ошибка', (int) ($result['code'] ?? 400));
            return;
        }
        $this->establishAuthenticatedSession($result['user']);
        $this->json(['success' => true, 'message' => 'Пароль изменён']);
    }

    public function logout(): void
    {
        ensure_session();
        unset($_SESSION['user']);
        $this->redirect('/');
    }

    private function rateKey(string $scope): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'user:' . $scope . ':' . $ip;
    }

    private function establishAuthenticatedSession(array $user): void
    {
        ensure_session();
        session_regenerate_id(true);
        $_SESSION['user'] = $user;
    }
}
