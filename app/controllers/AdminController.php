<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Services\AdminReportService;
use App\Services\MigrationService;

class AdminController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->reportService = $container->get(AdminReportService::class);
        $this->migrationService = $container->get(MigrationService::class);
    }

    private AdminReportService $reportService;
    private MigrationService $migrationService;

    public function report(): void
    {
        $this->requireAuth();
        if (!$this->isAdminAuthorizedByUser()) {
            http_response_code(403);
            $this->render('main/admin-report', [
                'adminAuthorized' => false,
                'error' => 'Доступ запрещён. Нужны права администратора.',
            ]);
            return;
        }

        $this->render('main/admin-report', [
            'adminAuthorized' => true,
            'summary' => $this->reportService->getSummary(),
            'popular' => $this->reportService->getPopularPosts(10),
            'activity' => $this->reportService->getDailyActivity(7),
            'errors' => $this->reportService->getRecentErrors(20),
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

    private function isAdminAuthorizedByUser(): bool
    {
        $user = $this->getLoggedUser();
        return (int) ($user['is_admin'] ?? 0) === 1;
    }
}
