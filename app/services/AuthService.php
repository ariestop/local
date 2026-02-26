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
        private MailService $mailService,
        private array $config,
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
            $u = $this->userRepo->findByEmail($email);
            if ($u && isset($u['email_verified']) && !$u['email_verified']) {
                return ['success' => false, 'error' => 'Подтвердите email. Проверьте почту.', 'code' => 403];
            }
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
        $name = trim($input['name']);
        if ($this->userRepo->emailExists($email)) {
            return ['success' => false, 'error' => 'Этот email уже зарегистрирован', 'code' => 400];
        }
        $confirmRequired = $this->config['app']['email_confirm_required'] ?? false;
        $confirmToken = $confirmRequired ? bin2hex(random_bytes(32)) : null;
        $userId = $this->userRepo->create([
            'email' => $email,
            'password' => $input['password'],
            'name' => $name,
        ], $confirmToken);
        if ($confirmRequired && $confirmToken) {
            $this->mailService->sendConfirmEmail($email, $name, $confirmToken);
        }
        $user = ['id' => $userId, 'email' => $email, 'name' => $name];
        $this->logger?->info('User registered', ['email' => $email, 'id' => $userId]);
        return [
            'success' => true,
            'user' => $user,
            'email_confirm_required' => $confirmRequired,
        ];
    }

    public function verifyEmail(string $token): array
    {
        $user = $this->userRepo->findByConfirmToken($token);
        if (!$user) {
            return ['success' => false, 'error' => 'Ссылка недействительна или истекла', 'code' => 400];
        }
        $this->userRepo->verifyEmail((int) $user['id']);
        $this->logger?->info('Email verified', ['user_id' => $user['id']]);
        return ['success' => true, 'user' => ['id' => (int) $user['id'], 'email' => $user['email'], 'name' => $user['name']]];
    }

    public function requestPasswordReset(string $email): array
    {
        $user = $this->userRepo->findByEmail(trim($email));
        if (!$user) {
            return ['success' => true, 'message' => 'Если email зарегистрирован, вы получите письмо'];
        }
        $token = bin2hex(random_bytes(32));
        $this->userRepo->setPasswordResetToken($user['email'], $token);
        $this->mailService->sendPasswordReset($user['email'], $user['name'] ?? '', $token);
        return ['success' => true, 'message' => 'Если email зарегистрирован, вы получите письмо'];
    }

    public function resetPassword(string $token, string $password): array
    {
        $v = new Validation();
        $v->minLength(['password' => $password], 'password', 5, 'Пароль');
        if (!$v->isValid()) {
            return ['success' => false, 'error' => $v->firstError(), 'code' => 400];
        }
        $user = $this->userRepo->findByPasswordResetToken($token);
        if (!$user) {
            return ['success' => false, 'error' => 'Ссылка недействительна или истекла', 'code' => 400];
        }
        $this->userRepo->updatePassword((int) $user['id'], $password);
        return ['success' => true, 'user' => ['id' => (int) $user['id'], 'email' => $user['email'], 'name' => $user['name']]];
    }
}
