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

    public function getByIds(array $ids, bool $includeArchived = false): array
    {
        return $this->model->getByIds($ids, $includeArchived);
    }

    public function getByUserId(int $userId): array
    {
        return $this->model->getByUserId($userId);
    }

    public function countByUserId(int $userId): int
    {
        return $this->model->countByUserId($userId);
    }

    public function getByUserIdPaginated(int $userId, int $limit, int $offset): array
    {
        return $this->model->getByUserIdPaginated($userId, $limit, $offset);
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

    public function archive(int $id, int $actorUserId, string $reason): bool
    {
        return $this->model->archive($id, $actorUserId, $reason);
    }

    public function restore(int $id): bool
    {
        return $this->model->restore($id);
    }

    public function hardDelete(int $id): bool
    {
        return $this->model->hardDelete($id);
    }

    public function getExpiredActiveForProcessing(int $limit = 100): array
    {
        return $this->model->getExpiredActiveForProcessing($limit);
    }

    public function countExpiredActiveForProcessing(): int
    {
        return $this->model->countExpiredActiveForProcessing();
    }

    public function markExpiryNotified(int $postId): void
    {
        $this->model->markExpiryNotified($postId);
    }

    public function getExpiryAutomationTotals(): array
    {
        return $this->model->getExpiryAutomationTotals();
    }

    public function incrementViewCount(int $postId): void
    {
        $this->model->incrementViewCount($postId);
    }

    public function addViewEvent(int $postId, ?int $userId, string $sessionHash, string $ipHash, string $userAgent): void
    {
        $this->model->addViewEvent($postId, $userId, $sessionHash, $ipHash, $userAgent);
    }

    public function getPopular(int $limit = 5): array
    {
        return $this->model->getPopular($limit);
    }

    public function getActivity(int $days = 7): array
    {
        return $this->model->getActivity($days);
    }

    public function getActiveForSitemap(int $limit = 50000): array
    {
        return $this->model->getActiveForSitemap($limit);
    }

    public function getActiveSitemapFilterValues(int $limit = 200): array
    {
        return $this->model->getActiveSitemapFilterValues($limit);
    }
}
