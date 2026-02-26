<?php

declare(strict_types=1);

namespace App\Services;

use App\Log\LoggerInterface;
use App\Repositories\PostPhotoRepository;
use App\Repositories\PostRepository;
use App\Repositories\ReferenceRepository;
use App\Validation;

class PostService
{
    public function __construct(
        private PostRepository $postRepo,
        private PostPhotoRepository $photoRepo,
        private ReferenceRepository $refRepo,
        private ImageService $imageService,
        private int $maxPrice = 999_000_000,
        private ?LoggerInterface $logger = null
    ) {}

    public function getMaxPrice(): int
    {
        return $this->maxPrice;
    }

    public function getPaginatedList(int $perPage, int $page, array $filters = [], string $sort = 'date_desc'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = $this->postRepo->countFiltered($filters);
        $posts = $this->postRepo->getList($perPage, $offset, $filters, $sort);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        return [
            'posts' => $posts,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ];
    }

    public function getDetail(int $id): ?array
    {
        $post = $this->postRepo->getById($id);
        if (!$post) {
            return null;
        }
        return [
            'post' => $post,
            'photos' => $this->photoRepo->getByPostId($id),
        ];
    }

    public function getForEdit(int $id, int $userId): ?array
    {
        $post = $this->postRepo->getById($id);
        if (!$post || (int) $post['user_id'] !== $userId) {
            return null;
        }
        return [
            'post' => $post,
            'photos' => $this->photoRepo->getByPostId($id),
            'actions' => $this->refRepo->getActions(),
            'objects' => $this->refRepo->getObjects(),
            'cities' => $this->refRepo->getCities(),
            'areasByCity' => $this->refRepo->getAreasByCity(),
            'max_price' => $this->maxPrice,
            'max_photo_bytes' => ImageService::getMaxSizeBytes(),
        ];
    }

    public function getFormData(): array
    {
        return [
            'actions' => $this->refRepo->getActions(),
            'objects' => $this->refRepo->getObjects(),
            'cities' => $this->refRepo->getCities(),
            'areasByCity' => $this->refRepo->getAreasByCity(),
            'max_price' => $this->maxPrice,
            'max_photo_bytes' => ImageService::getMaxSizeBytes(),
        ];
    }

    public function create(array $input, array $files, int $userId): array
    {
        $v = new Validation();
        $v->required($input, ['action_id', 'object_id', 'city_id', 'area_id', 'street', 'phone', 'cost', 'descr_post']);
        if (!$v->isValid()) {
            return ['success' => false, 'error' => $v->firstError(), 'code' => 400];
        }
        $costError = $this->validateCost($input['cost'] ?? '');
        if ($costError !== null) {
            return ['success' => false, 'error' => $costError, 'code' => 400];
        }
        $data = $this->normalizePostData($input, $userId, true);
        $id = $this->postRepo->create($data);
        $photos = $this->normalizeFilesArray($files['photos'] ?? $files);
        if (!empty($photos['name'][0])) {
            try {
                $uploaded = $this->imageService->upload($userId, $id, $photos);
                if (!empty($uploaded)) {
                    $this->photoRepo->addBatch($id, $uploaded);
                }
            } catch (\Throwable $e) {
                $this->logger?->warning('Photo upload failed after post create', ['post_id' => $id, 'error' => $e->getMessage()]);
            }
        }
        $this->logger?->info('Post created', ['post_id' => $id, 'user_id' => $userId]);
        return ['success' => true, 'id' => $id];
    }

    public function update(int $id, array $input, array $files, int $userId): array
    {
        $post = $this->postRepo->getById($id);
        if (!$post || (int) $post['user_id'] !== $userId) {
            return ['success' => false, 'error' => 'Объявление не найдено', 'code' => 404];
        }
        $v = new Validation();
        $v->required($input, ['action_id', 'object_id', 'city_id', 'area_id', 'street', 'phone', 'cost', 'descr_post']);
        if (!$v->isValid()) {
            return ['success' => false, 'error' => $v->firstError(), 'code' => 400];
        }
        $costError = $this->validateCost($input['cost'] ?? '');
        if ($costError !== null) {
            return ['success' => false, 'error' => $costError, 'code' => 400];
        }
        $data = $this->normalizePostData($input, $userId, false);
        $this->postRepo->update($id, $userId, $data);
        $deletePhotos = is_string($input['delete_photos'] ?? '') ? explode(',', $input['delete_photos']) : ($input['delete_photos'] ?? []);
        $deletePhotos = array_map('trim', array_map('basename', (array) $deletePhotos));
        foreach ($deletePhotos as $fn) {
            if ($fn) {
                $this->photoRepo->deleteByFilename($id, $fn);
                $this->imageService->deletePhoto($userId, $id, $fn);
            }
        }
        $photoOrder = is_string($input['photo_order'] ?? '') ? explode(',', $input['photo_order']) : [];
        $photoOrder = array_map('trim', array_map('basename', $photoOrder));
        $existingInOrder = array_values(array_filter($photoOrder, fn($f) => $f !== '' && $f !== '__new__'));
        if (!empty($existingInOrder)) {
            $this->photoRepo->updateSortOrder($id, $existingInOrder);
        }
        $currentCount = $this->photoRepo->countByPostId($id);
        $remainingSlots = max(0, 5 - $currentCount);
        $photos = $this->normalizeFilesArray($files['photos'] ?? $files);
        if (!empty($photos['name'][0]) && $remainingSlots > 0) {
            try {
                $uploaded = $this->imageService->upload($userId, $id, $photos, $remainingSlots);
                if (!empty($uploaded)) {
                    $maxSort = $this->photoRepo->getMaxSortOrder($id);
                    foreach ($uploaded as $i => $p) {
                        $this->photoRepo->add($id, $p['filename'], $maxSort + 1 + $i);
                    }
                }
            } catch (\Throwable $e) {
                $this->logger?->warning('Photo upload failed on edit', ['post_id' => $id]);
            }
        }
        $this->logger?->info('Post updated', ['post_id' => $id, 'user_id' => $userId]);
        return ['success' => true, 'id' => $id];
    }

    public function delete(int $id, int $userId): array
    {
        $post = $this->postRepo->getById($id);
        if (!$post || (int) $post['user_id'] !== $userId) {
            return ['success' => false, 'error' => 'Объявление не найдено', 'code' => 404];
        }
        $this->photoRepo->deleteByPostId($id);
        $this->imageService->deletePostFolder($userId, $id);
        $this->postRepo->delete($id, $userId);
        $this->logger?->info('Post deleted', ['post_id' => $id, 'user_id' => $userId]);
        return ['success' => true];
    }

    public function getByUserId(int $userId): array
    {
        return $this->postRepo->getByUserId($userId);
    }

    public function getPostsByIds(array $ids): array
    {
        return $this->postRepo->getByIds($ids);
    }

    public function getFirstPhotosForPosts(array $postIds): array
    {
        return empty($postIds) ? [] : $this->photoRepo->getFirstByPostIds($postIds);
    }

    /**
     * Normalize $_FILES structure: PHP may use flat structure for single file (name as string).
     */
    private function normalizeFilesArray(array $files): array
    {
        if (isset($files['name']) && is_array($files['name'])) {
            return $files;
        }
        if (isset($files['name']) && is_string($files['name']) && $files['name'] !== '') {
            return [
                'name' => [$files['name']],
                'type' => [array_key_exists('type', $files) ? $files['type'] : ''],
                'tmp_name' => [array_key_exists('tmp_name', $files) ? $files['tmp_name'] : ''],
                'error' => [array_key_exists('error', $files) ? $files['error'] : UPLOAD_ERR_NO_FILE],
                'size' => [array_key_exists('size', $files) ? $files['size'] : 0],
            ];
        }
        return ['name' => [], 'type' => [], 'tmp_name' => [], 'error' => [], 'size' => []];
    }

    private function validateCost(mixed $cost): ?string
    {
        $value = (int) preg_replace('/\D/', '', (string) $cost);
        if ($value < 0) {
            return 'Цена не может быть отрицательной';
        }
        if ($value > $this->maxPrice) {
            return 'Цена не должна превышать ' . number_format($this->maxPrice, 0, '', ' ') . ' руб.';
        }
        return null;
    }

    private function normalizePostData(array $input, int $userId, bool $forCreate): array
    {
        $data = [
            'action_id' => (int) ($input['action_id'] ?? 0),
            'object_id' => (int) ($input['object_id'] ?? 0),
            'city_id' => (int) ($input['city_id'] ?? 0),
            'area_id' => (int) ($input['area_id'] ?? 0),
            'room' => (int) ($input['room'] ?? 0),
            'm2' => (int) ($input['m2'] ?? 0),
            'street' => trim($input['street'] ?? ''),
            'phone' => trim($input['phone'] ?? ''),
            'cost' => max(0, min(4294967295, (int) preg_replace('/\D/', '', (string) ($input['cost'] ?? 0)))),
            'title' => trim($input['title'] ?? 'Объявление'),
            'descr_post' => trim($input['descr_post'] ?? ''),
            'new_house' => !empty($input['new_house']),
        ];
        if ($forCreate) {
            $data['user_id'] = $userId;
        }
        return $data;
    }
}
