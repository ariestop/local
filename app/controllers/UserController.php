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
            $this->json(['success' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], 403);
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = $result['user'];
        $this->json(['success' => true, 'user' => $result['user']]);
    }

    public function register(): void
    {
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], 403);
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = $result['user'];
        $this->json(['success' => true, 'message' => 'Регистрация успешна', 'user' => $result['user']]);
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user']);
        $this->redirect('/');
    }
}
