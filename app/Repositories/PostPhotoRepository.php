<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PostPhoto;

class PostPhotoRepository
{
    public function __construct(
        private PostPhoto $model
    ) {}

    public function getByPostId(int $postId): array
    {
        return $this->model->getByPostId($postId);
    }

    public function getFirstByPostIds(array $postIds): array
    {
        return $this->model->getFirstByPostIds($postIds);
    }

    public function add(int $postId, string $filename, int $sortOrder): void
    {
        $this->model->add($postId, $filename, $sortOrder);
    }

    public function addBatch(int $postId, array $photos): void
    {
        $this->model->addBatch($postId, $photos);
    }

    public function deleteByFilename(int $postId, string $filename): void
    {
        $this->model->deleteByFilename($postId, $filename);
    }

    public function deleteByPostId(int $postId): void
    {
        $this->model->deleteByPostId($postId);
    }

    public function countByPostId(int $postId): int
    {
        return $this->model->countByPostId($postId);
    }

    public function getMaxSortOrder(int $postId): int
    {
        return $this->model->getMaxSortOrder($postId);
    }

    public function updateSortOrder(int $postId, array $filenamesInOrder): void
    {
        $this->model->updateSortOrder($postId, $filenamesInOrder);
    }
}
