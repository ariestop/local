<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;
use PDO;

class PostRepository
{
    public function __construct(
        private Post $model
    ) {}

    public function count(): int
    {
        return $this->model->count();
    }

    public function getList(int $limit, int $offset): array
    {
        return $this->model->getList($limit, $offset);
    }

    public function getById(int $id): ?array
    {
        return $this->model->getById($id);
    }

    public function getByUserId(int $userId): array
    {
        return $this->model->getByUserId($userId);
    }

    public function create(array $data): int
    {
        return $this->model->create($data);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        return $this->model->update($id, $userId, $data);
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->model->delete($id, $userId);
    }
}
