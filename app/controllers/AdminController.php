<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Services\AdminReportService;
use App\Services\MigrationService;
use App\Services\PostService;

class AdminController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->reportService = $container->get(AdminReportService::class);
        $this->migrationService = $container->get(MigrationService::class);
        $this->postService = $container->get(PostService::class);
    }

    private AdminReportService $reportService;
    private MigrationService $migrationService;
    private PostService $postService;

    public function report(): void
    {
        $this->requireAuth();
        if (!$this->isAdminAuthorizedByUser()) {
            http_response_code(403);
            $this->render('main/admin', [
                'adminAuthorized' => false,
                'error' => 'Доступ запрещён. Нужны права администратора.',
            ]);
            return;
        }

        $this->render('main/admin', [
            'adminAuthorized' => true,
            'summary' => $this->reportService->getSummary(),
            'popular' => $this->reportService->getPopularPosts(10),
            'activity' => $this->reportService->getDailyActivity(7),
            'errors' => $this->reportService->getRecentErrors(20),
            'expiryTotals' => $this->postService->getExpiryAutomationTotals(),
            'pendingExpireCount' => $this->postService->countExpiredActiveForProcessing(),
            'flashSuccess' => (string) ($_GET['success'] ?? ''),
            'flashError' => (string) ($_GET['error'] ?? ''),
            'runStats' => $this->extractRunStatsFromQuery(),
        ]);
    }

    public function migrations(): void
    {
        $this->requireAuth();
        if (!$this->isAdminAuthorizedByUser()) {
            http_response_code(403);
            $this->render('main/admin-migrations', [
                'adminAuthorized' => false,
                'error' => 'Доступ запрещён. Нужны права администратора.',
            ]);
            return;
        }

        $this->render('main/admin-migrations', [
            'adminAuthorized' => true,
            'status' => $this->migrationService->getStatus(),
            'runs' => $this->migrationService->getRecentRuns(20),
            'flashSuccess' => (string) ($_GET['success'] ?? ''),
            'flashError' => (string) ($_GET['error'] ?? ''),
        ]);
    }

    public function applyNextMigration(): void
    {
        $this->requireAuth();
        if (!$this->isAdminAuthorizedByUser()) {
            $this->redirect('/admin-migrations?error=' . urlencode('Доступ запрещён.'));
        }
        if (!$this->validateCsrf()) {
            $this->redirect('/admin-migrations?error=' . urlencode(self::CSRF_ERROR_MESSAGE));
        }

        try {
            $result = $this->migrationService->applyNextByActor($this->getLoggedUser());
            if ($result['applied']) {
                $this->redirect('/admin-migrations?success=' . urlencode('Применена миграция: ' . (string) $result['name']));
            }
            $this->redirect('/admin-migrations?success=' . urlencode('Нет неприменённых миграций.'));
        } catch (\Throwable $e) {
            $this->redirect('/admin-migrations?error=' . urlencode($e->getMessage()));
        }
    }

    public function applyMigration(): void
    {
        $this->requireAuth();
        if (!$this->isAdminAuthorizedByUser()) {
            $this->redirect('/admin-migrations?error=' . urlencode('Доступ запрещён.'));
        }
        if (!$this->validateCsrf()) {
            $this->redirect('/admin-migrations?error=' . urlencode(self::CSRF_ERROR_MESSAGE));
        }

        $name = trim((string) ($_POST['migration'] ?? ''));
        if ($name === '') {
            $this->redirect('/admin-migrations?error=' . urlencode('Не указано имя миграции.'));
        }

        try {
            $this->migrationService->applyOne($name, $this->getLoggedUser());
            $this->redirect('/admin-migrations?success=' . urlencode('Миграция применена: ' . $name));
        } catch (\Throwable $e) {
            $this->redirect('/admin-migrations?error=' . urlencode($e->getMessage()));
        }
    }

    public function runExpirePosts(): void
    {
        $this->requireAuth();
        if (!$this->isAdminAuthorizedByUser()) {
            $this->redirect('/admin?error=' . urlencode('Доступ запрещён.'));
        }
        if (!$this->validateCsrf()) {
            $this->redirect('/admin?error=' . urlencode(self::CSRF_ERROR_MESSAGE));
        }

        $pendingBefore = $this->postService->countExpiredActiveForProcessing();
        $targetLimit = $this->normalizeExpireTarget($_POST['limit'] ?? null, $pendingBefore);
        if ($targetLimit === 0) {
            $this->redirect('/admin?success=' . urlencode('Нет объявлений для обработки.'));
        }

        try {
            $processed = 0;
            $archived = 0;
            $notified = 0;
            $batches = 0;

            while ($processed < $targetLimit) {
                $batchLimit = min(100, $targetLimit - $processed);
                $result = $this->postService->processExpiredListings($batchLimit);
                $batchProcessed = (int) ($result['processed'] ?? 0);
                $batchArchived = (int) ($result['archived'] ?? 0);
                $batchNotified = (int) ($result['notified'] ?? 0);

                if ($batchProcessed === 0) {
                    break;
                }

                $processed += $batchProcessed;
                $archived += $batchArchived;
                $notified += $batchNotified;
                $batches++;

                // If nothing could be archived in this batch, stop to avoid infinite loops.
                if ($batchArchived === 0) {
                    break;
                }
            }

            $pendingAfter = $this->postService->countExpiredActiveForProcessing();
            $query = http_build_query([
                'success' => 'Ручной запуск автоархивации выполнен.',
                'processed' => $processed,
                'archived' => $archived,
                'notified' => $notified,
                'target' => $targetLimit,
                'batches' => $batches,
                'pending_before' => $pendingBefore,
                'pending_after' => $pendingAfter,
            ]);
            $this->redirect('/admin?' . $query);
        } catch (\Throwable $e) {
            $this->redirect('/admin?error=' . urlencode($e->getMessage()));
        }
    }

    public function runExpirePostsBatch(): void
    {
        $this->requireAuth();
        if (!$this->isAdminAuthorizedByUser()) {
            $this->jsonError('Доступ запрещён.', 403);
            return;
        }
        if (!$this->validateCsrf()) {
            $this->jsonError(self::CSRF_ERROR_MESSAGE, 419);
            return;
        }

        $limit = max(1, min(100, (int) ($_POST['limit'] ?? 100)));
        try {
            $result = $this->postService->processExpiredListings($limit);
            $pendingAfter = $this->postService->countExpiredActiveForProcessing();

            $this->json([
                'success' => true,
                'processed' => max(0, (int) ($result['processed'] ?? 0)),
                'archived' => max(0, (int) ($result['archived'] ?? 0)),
                'notified' => max(0, (int) ($result['notified'] ?? 0)),
                'pending_after' => max(0, $pendingAfter),
            ]);
        } catch (\Throwable $e) {
            $this->jsonError($e->getMessage(), 500);
        }
    }

    private function isAdminAuthorizedByUser(): bool
    {
        $user = $this->getLoggedUser();
        return (int) ($user['is_admin'] ?? 0) === 1;
    }

    private function extractRunStatsFromQuery(): ?array
    {
        $hasResult = isset($_GET['processed'], $_GET['archived'], $_GET['notified']);
        if (!$hasResult) {
            return null;
        }

        return [
            'processed' => max(0, (int) $_GET['processed']),
            'archived' => max(0, (int) $_GET['archived']),
            'notified' => max(0, (int) $_GET['notified']),
            'target' => max(0, (int) ($_GET['target'] ?? 0)),
            'batches' => max(0, (int) ($_GET['batches'] ?? 0)),
            'pending_before' => max(0, (int) ($_GET['pending_before'] ?? 0)),
            'pending_after' => max(0, (int) ($_GET['pending_after'] ?? 0)),
        ];
    }

    private function normalizeExpireTarget(mixed $rawLimit, int $pendingCount): int
    {
        if ($rawLimit === null || $rawLimit === '') {
            return max(0, $pendingCount);
        }

        $limit = (int) $rawLimit;
        return max(0, min(10_000, $limit));
    }
}
