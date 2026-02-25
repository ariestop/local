<?php

declare(strict_types=1);

namespace App\Services;

use App\Log\LoggerInterface;
use App\Repositories\UserRepository;
use App\Validation;

class AuthService
{
    public function __construct(
        private UserRepository $userRepo,
        private ?LoggerInterface $logger = null
    ) {}

    public function login(array $input): array
    {
        $v = new Validation();
        $v->required($input, ['email', 'password']);
        if (!$v->isValid()) {
            return ['success' => false, 'error' => $v->firstError() ?? 'Введите email и пароль', 'code' => 400];
        }
        $email = trim($input['email']);
        $password = $input['password'] ?? '';
        $user = $this->userRepo->verifyCredentials($email, $password);
        if (!$user) {
            $this->logger?->info('Login failed', ['email' => $email]);
            return ['success' => false, 'error' => 'Неверный email или пароль', 'code' => 401];
        }
        return ['success' => true, 'user' => $user];
    }

    public function register(array $input, string $captchaExpected): array
    {
        $v = new Validation();
        $v->required($input, ['email', 'password', 'name'])
            ->email($input, 'email')
            ->minLength($input, 'password', 5, 'Пароль')
            ->equals($input, 'password', 'password2', 'Пароли не совпадают');
        if (!$v->isValid()) {
            return ['success' => false, 'error' => $v->firstError(), 'code' => 400];
        }
        $captcha = strtolower(trim($input['captcha'] ?? ''));
        $expected = strtolower(trim($captchaExpected));
        if (!$expected || $captcha !== $expected) {
            return ['success' => false, 'error' => 'Неверная капча', 'code' => 400];
        }
        $email = trim($input['email']);
        if ($this->userRepo->emailExists($email)) {
            return ['success' => false, 'error' => 'Этот email уже зарегистрирован', 'code' => 400];
        }
        $userId = $this->userRepo->create([
            'email' => $email,
            'password' => $input['password'],
            'name' => trim($input['name']),
        ]);
        $user = ['id' => $userId, 'email' => $email, 'name' => trim($input['name'])];
        $this->logger?->info('User registered', ['email' => $email, 'id' => $userId]);
        return ['success' => true, 'user' => $user];
    }
}
