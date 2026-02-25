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

    public function create(array $data): int
    {
        return $this->model->register($data);
    }

    public function verifyCredentials(string $email, string $password): ?array
    {
        return $this->model->login($email, $password);
    }
}
