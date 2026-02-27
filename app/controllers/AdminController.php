<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Services\AdminReportService;

class AdminController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->reportService = $container->get(AdminReportService::class);
    }

    private AdminReportService $reportService;

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

    private function isAdminAuthorizedByUser(): bool
    {
        $user = $this->getLoggedUser();
        return (int) ($user['is_admin'] ?? 0) === 1;
    }
}
