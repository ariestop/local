<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function __construct(
        private User $model
    ) {}

    public function findByEmail(string $email): ?array
    {
        return $this->model->findByEmail($email);
    }

    public function findById(int $id): ?array
    {
        return $this->model->findById($id);
    }

    public function emailExists(string $email): bool
    {
        return $this->model->emailExists($email);
    }

    public function create(array $data, ?string $confirmToken = null): int
    {
        return $this->model->register($data, $confirmToken);
    }

    public function findByConfirmToken(string $token): ?array
    {
        return $this->model->findByConfirmToken($token);
    }

    public function verifyEmail(int $userId): void
    {
        $this->model->verifyEmail($userId);
    }

    public function setPasswordResetToken(string $email, string $token): bool
    {
        return $this->model->setPasswordResetToken($email, $token);
    }

    public function findByPasswordResetToken(string $token): ?array
    {
        return $this->model->findByPasswordResetToken($token);
    }

    public function updatePassword(int $userId, string $password): void
    {
        $this->model->updatePassword($userId, $password);
    }

    public function verifyCredentials(string $email, string $password): ?array
    {
        return $this->model->login($email, $password);
    }
}
