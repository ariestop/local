<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Services\AuthService;

class UserController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->authService = $container->get(AuthService::class);
    }

    private AuthService $authService;

    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        if ($this->getLoggedUser()) {
            $this->json(['success' => true, 'user' => $this->getLoggedUser()]);
            return;
        }
        $result = $this->authService->login($_POST);
        if (!$result['success']) {
            $this->json(['success' => false, 'error' => $result['error']], $result['code'] ?? 400);
            return;
        }
        ensure_session();
        $_SESSION['user'] = $result['user'];
        $this->json(['success' => true, 'user' => $result['user']]);
    }

    public function register(): void
    {
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $expected = $_SESSION['captcha'] ?? '';
        $result = $this->authService->register($_POST, $expected);
        if (!$result['success']) {
            if (isset($_SESSION['captcha'])) {
                unset($_SESSION['captcha']);
            }
            $this->json(['success' => false, 'error' => $result['error']], $result['code'] ?? 400);
            return;
        }
        unset($_SESSION['captcha']);
        if (empty($result['email_confirm_required'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user'] = $result['user'];
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
        ensure_session();
        $_SESSION['user'] = $result['user'];
        $this->render('main/verify-email', ['success' => true]);
    }

    public function forgotPassword(): void
    {
        $this->render('main/forgot-password', []);
    }

    public function forgotPasswordSubmit(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'error' => 'Ошибка безопасности'], 403);
            return;
        }
        $email = trim($_POST['email'] ?? '');
        if (!$email) {
            $this->json(['success' => false, 'error' => 'Введите email'], 400);
            return;
        }
        try {
            $result = $this->authService->requestPasswordReset($email);
            $this->json($result);
        } catch (\Throwable $e) {
            $this->json(['success' => false, 'error' => 'Временная ошибка сервера. Попробуйте позже.'], 500);
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
            $this->json(['success' => false, 'error' => 'Ошибка безопасности'], 403);
            return;
        }
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if ($password !== $password2) {
            $this->json(['success' => false, 'error' => 'Пароли не совпадают'], 400);
            return;
        }
        $result = $this->authService->resetPassword($token, $password);
        if (!$result['success']) {
            $this->json(['success' => false, 'error' => $result['error']], $result['code'] ?? 400);
            return;
        }
        ensure_session();
        $_SESSION['user'] = $result['user'];
        $this->json(['success' => true, 'message' => 'Пароль изменён']);
    }

    public function logout(): void
    {
        ensure_session();
        unset($_SESSION['user']);
        $this->redirect('/');
    }
}
