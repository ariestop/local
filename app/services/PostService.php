<?php

declare(strict_types=1);

namespace App\Services;

use App\Log\LoggerInterface;
use App\Repositories\PostPhotoRepository;
use App\Repositories\PostRepository;
use App\Repositories\ReferenceRepository;
use App\Validation;
use PDO;

class PostService
{
    public function __construct(
        private PostRepository $postRepo,
        private PostPhotoRepository $photoRepo,
        private ReferenceRepository $refRepo,
        private ImageService $imageService,
        private MailService $mailService,
        private PDO $db,
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

    public function getPopular(int $limit = 5): array
    {
        return $this->postRepo->getPopular($limit);
    }

    public function getActivity(int $days = 7): array
    {
        return $this->postRepo->getActivity($days);
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
        $id = 0;
        $stagingDirs = [];
        $createdPhotoFilenames = [];
        try {
            $this->db->beginTransaction();
            $id = $this->postRepo->create($data);
            $photos = $this->normalizeFilesArray($files['photos'] ?? $files);
            if (!empty($photos['name'][0])) {
                $staged = $this->imageService->stageUpload($userId, $id, $photos);
                $stagingDirs[] = (string) ($staged['staging_dir'] ?? '');
                $uploaded = is_array($staged['photos'] ?? null) ? $staged['photos'] : [];
                if ($uploaded !== []) {
                    $this->photoRepo->addBatch($id, $uploaded);
                    $createdPhotoFilenames = array_values(array_filter(array_map(
                        static fn(array $p): string => (string) ($p['filename'] ?? ''),
                        $uploaded
                    )));
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->cleanupStagingDirs($stagingDirs);
            if ($id > 0) {
                $this->imageService->deletePostFolder($userId, $id);
            }
            $this->logger?->warning('Post create transaction failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Не удалось создать объявление', 'code' => 500];
        }

        try {
            $this->promoteStagingDirs($stagingDirs, $userId, $id);
        } catch (\Throwable $e) {
            $this->cleanupStagingDirs($stagingDirs);
            $this->rollbackCreatedPhotoRows($id, $createdPhotoFilenames);
            $this->compensateCreatePostFailure($id, $userId);
            $this->logger?->warning('Post create staging promote failed', [
                'post_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Не удалось сохранить фото объявления', 'code' => 500];
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
        $deletePhotos = is_string($input['delete_photos'] ?? '') ? explode(',', $input['delete_photos']) : ($input['delete_photos'] ?? []);
        $deletePhotos = array_map('trim', array_map('basename', (array) $deletePhotos));
        $stagingDirs = [];
        $newPhotoFilenames = [];
        $photosToDeleteAfterCommit = [];
        try {
            $this->db->beginTransaction();
            $this->postRepo->update($id, $userId, $data);
            foreach ($deletePhotos as $fn) {
                if ($fn) {
                    $this->photoRepo->deleteByFilename($id, $fn);
                    $photosToDeleteAfterCommit[] = $fn;
                }
            }
            $photoOrder = is_string($input['photo_order'] ?? '') ? explode(',', $input['photo_order']) : [];
            $photoOrder = array_map('trim', array_map('basename', $photoOrder));
            $existingInOrder = array_values(array_filter($photoOrder, fn($f) => $f !== '' && $f !== '__new__'));
            if (!empty($existingInOrder)) {
                $this->photoRepo->updateSortOrder($id, $existingInOrder);
            }
            $currentCount = $this->photoRepo->countByPostId($id);
            $remainingSlots = max(0, 10 - $currentCount);
            $photos = $this->normalizeFilesArray($files['photos'] ?? $files);
            if (!empty($photos['name'][0]) && $remainingSlots > 0) {
                $staged = $this->imageService->stageUpload($userId, $id, $photos, $remainingSlots);
                $stagingDirs[] = (string) ($staged['staging_dir'] ?? '');
                $uploaded = is_array($staged['photos'] ?? null) ? $staged['photos'] : [];
                if ($uploaded !== []) {
                    $maxSort = $this->photoRepo->getMaxSortOrder($id);
                    foreach ($uploaded as $i => $p) {
                        $filename = (string) ($p['filename'] ?? '');
                        if ($filename === '') {
                            continue;
                        }
                        $this->photoRepo->add($id, $filename, $maxSort + 1 + $i);
                        $newPhotoFilenames[] = $filename;
                    }
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->cleanupStagingDirs($stagingDirs);
            $this->logger?->warning('Post update transaction failed', ['post_id' => $id, 'user_id' => $userId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Не удалось сохранить изменения', 'code' => 500];
        }

        try {
            $this->promoteStagingDirs($stagingDirs, $userId, $id);
        } catch (\Throwable $e) {
            $this->cleanupStagingDirs($stagingDirs);
            $this->rollbackCreatedPhotoRows($id, $newPhotoFilenames);
            $this->logger?->warning('Post update staging promote failed', [
                'post_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => 'Не удалось сохранить новые фото', 'code' => 500];
        }

        foreach ($photosToDeleteAfterCommit as $fn) {
            try {
                $this->imageService->deletePhoto($userId, $id, $fn);
            } catch (\Throwable $e) {
                $this->logger?->warning('Post update deferred photo delete failed', [
                    'post_id' => $id,
                    'user_id' => $userId,
                    'filename' => $fn,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger?->info('Post updated', ['post_id' => $id, 'user_id' => $userId]);
        return ['success' => true, 'id' => $id];
    }

    public function delete(int $id, int $userId, bool $isAdmin = false): array
    {
        $post = $this->postRepo->getById($id);
        if (!$post) {
            return ['success' => false, 'error' => 'Объявление не найдено', 'code' => 404];
        }
        $isOwner = (int) $post['user_id'] === $userId;
        if (!$isOwner && !$isAdmin) {
            return ['success' => false, 'error' => 'Доступ запрещён', 'code' => 403];
        }
        $reason = $isAdmin ? 'manual_admin' : 'manual_owner';
        try {
            $this->db->beginTransaction();
            $this->postRepo->archive($id, $userId, $reason);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logger?->warning('Post archive transaction failed', ['post_id' => $id, 'user_id' => $userId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Не удалось архивировать объявление', 'code' => 500];
        }
        $this->logger?->info('Post archived', ['post_id' => $id, 'user_id' => $userId, 'reason' => $reason]);
        return ['success' => true];
    }

    public function hardDelete(int $id, int $userId, bool $isAdmin): array
    {
        if (!$isAdmin) {
            return ['success' => false, 'error' => 'Доступ запрещён', 'code' => 403];
        }
        $post = $this->postRepo->getById($id);
        if (!$post) {
            return ['success' => false, 'error' => 'Объявление не найдено', 'code' => 404];
        }
        $ownerId = (int) ($post['user_id'] ?? 0);
        try {
            $this->db->beginTransaction();
            $this->photoRepo->deleteByPostId($id);
            $this->postRepo->hardDelete($id);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logger?->warning('Post hard delete transaction failed', ['post_id' => $id, 'user_id' => $userId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Не удалось удалить объявление', 'code' => 500];
        }
        if ($ownerId > 0) {
            $this->imageService->deletePostFolder($ownerId, $id);
        }
        $this->logger?->info('Post hard-deleted', ['post_id' => $id, 'user_id' => $userId]);
        return ['success' => true];
    }

    public function restore(int $id, int $userId, bool $isAdmin = false): array
    {
        $post = $this->postRepo->getById($id);
        if (!$post) {
            return ['success' => false, 'error' => 'Объявление не найдено', 'code' => 404];
        }
        $isOwner = (int) $post['user_id'] === $userId;
        if (!$isOwner && !$isAdmin) {
            return ['success' => false, 'error' => 'Доступ запрещён', 'code' => 403];
        }
        try {
            $this->db->beginTransaction();
            $this->postRepo->restore($id);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logger?->warning('Post restore transaction failed', ['post_id' => $id, 'user_id' => $userId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Не удалось восстановить объявление', 'code' => 500];
        }
        $this->logger?->info('Post restored', ['post_id' => $id, 'user_id' => $userId]);
        return ['success' => true];
    }

    public function getByUserId(int $userId): array
    {
        return $this->postRepo->getByUserId($userId);
    }

    public function getByUserIdPaginated(int $userId, int $perPage, int $page): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $total = $this->postRepo->countByUserId($userId);
        $posts = $this->postRepo->getByUserIdPaginated($userId, $perPage, $offset);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return [
            'posts' => $posts,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ];
    }

    public function getPostsByIds(array $ids): array
    {
        return $this->postRepo->getByIds($ids);
    }

    public function getFirstPhotosForPosts(array $postIds): array
    {
        return empty($postIds) ? [] : $this->photoRepo->getFirstByPostIds($postIds);
    }

    public function registerView(int $postId, ?int $userId): void
    {
        ensure_session();
        $key = 'post_view_last_' . $postId;
        $now = time();
        $cooldown = 300;
        $last = (int) ($_SESSION[$key] ?? 0);
        if ($last > 0 && ($now - $last) < $cooldown) {
            return;
        }

        $_SESSION[$key] = $now;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionHash = hash('sha256', session_id() ?: 'no-session');
        $ipHash = hash('sha256', $ip);

        try {
            $this->postRepo->incrementViewCount($postId);
            $this->postRepo->addViewEvent($postId, $userId, $sessionHash, $ipHash, $ua);
        } catch (\Throwable $e) {
            $this->logger?->warning('Post view tracking failed', ['post_id' => $postId, 'error' => $e->getMessage()]);
        }
    }

    public function processExpiredListings(int $limit = 100): array
    {
        $rows = $this->postRepo->getExpiredActiveForProcessing($limit);
        $archived = 0;
        $notified = 0;

        foreach ($rows as $row) {
            $postId = (int) ($row['id'] ?? 0);
            $ownerId = (int) ($row['user_id'] ?? 0);
            if ($postId <= 0 || $ownerId <= 0) {
                continue;
            }

            try {
                $this->db->beginTransaction();
                $this->postRepo->archive($postId, $ownerId, 'expired');
                $this->db->commit();
                $archived++;
            } catch (\Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $this->logger?->warning('Auto-archive expired post failed', ['post_id' => $postId, 'error' => $e->getMessage()]);
                continue;
            }

            if (!empty($row['expiry_notified_at'])) {
                continue;
            }

            $email = trim((string) ($row['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $name = trim((string) ($row['name'] ?? 'Пользователь'));
            $title = trim((string) ($row['title'] ?? 'Ваше объявление'));
            if ($this->mailService->sendPostExpiredEmail($email, $name, $postId, $title)) {
                $this->postRepo->markExpiryNotified($postId);
                $notified++;
            }
        }

        return ['archived' => $archived, 'notified' => $notified, 'processed' => count($rows)];
    }

    public function countExpiredActiveForProcessing(): int
    {
        return $this->postRepo->countExpiredActiveForProcessing();
    }

    public function getExpiryAutomationTotals(): array
    {
        return $this->postRepo->getExpiryAutomationTotals();
    }

    public function getActiveForSitemap(int $limit = 50000): array
    {
        return $this->postRepo->getActiveForSitemap($limit);
    }

    public function getActiveSitemapFilterValues(int $limit = 200): array
    {
        return $this->postRepo->getActiveSitemapFilterValues($limit);
    }

    /**
     * @param array<int, string> $stagingDirs
     */
    private function cleanupStagingDirs(array $stagingDirs): void
    {
        foreach ($stagingDirs as $stagingDir) {
            if ($stagingDir === '') {
                continue;
            }
            $this->imageService->cleanupStaged($stagingDir);
        }
    }

    /**
     * @param array<int, string> $stagingDirs
     */
    private function promoteStagingDirs(array $stagingDirs, int $userId, int $postId): void
    {
        foreach ($stagingDirs as $stagingDir) {
            if ($stagingDir === '') {
                continue;
            }
            $this->imageService->promoteStaged($stagingDir, $userId, $postId);
        }
    }

    /**
     * @param array<int, string> $filenames
     */
    private function rollbackCreatedPhotoRows(int $postId, array $filenames): void
    {
        foreach (array_values(array_unique($filenames)) as $filename) {
            if ($filename === '') {
                continue;
            }
            try {
                $this->photoRepo->deleteByFilename($postId, $filename);
            } catch (\Throwable $e) {
                $this->logger?->warning('Rollback photo rows failed', [
                    'post_id' => $postId,
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function compensateCreatePostFailure(int $postId, int $userId): void
    {
        try {
            $this->db->beginTransaction();
            $this->photoRepo->deleteByPostId($postId);
            $this->postRepo->hardDelete($postId);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logger?->warning('Create compensation DB cleanup failed', [
                'post_id' => $postId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
        $this->imageService->deletePostFolder($userId, $postId);
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
