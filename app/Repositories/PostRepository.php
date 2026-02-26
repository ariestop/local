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

    public function countFiltered(array $filters): int
    {
        return $this->model->countFiltered($filters);
    }

    public function getList(int $limit, int $offset, array $filters = [], string $sort = 'date_desc'): array
    {
        return $this->model->getList($limit, $offset, $filters, $sort);
    }

    public function getById(int $id): ?array
    {
        return $this->model->getById($id);
    }

    public function getByIds(array $ids): array
    {
        return $this->model->getByIds($ids);
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
