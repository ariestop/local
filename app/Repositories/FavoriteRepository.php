<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Favorite;

class FavoriteRepository
{
    public function __construct(
        private Favorite $model
    ) {}

    public function add(int $userId, int $postId): bool
    {
        return $this->model->add($userId, $postId);
    }

    public function remove(int $userId, int $postId): bool
    {
        return $this->model->remove($userId, $postId);
    }

    public function toggle(int $userId, int $postId): bool
    {
        return $this->model->toggle($userId, $postId);
    }

    public function has(int $userId, int $postId): bool
    {
        return $this->model->has($userId, $postId);
    }

    public function getPostIdsByUserId(int $userId): array
    {
        return $this->model->getPostIdsByUserId($userId);
    }
}
