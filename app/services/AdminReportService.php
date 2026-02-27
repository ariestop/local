<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

class AdminReportService
{
    public function __construct(private PDO $db) {}

    public function getSummary(): array
    {
        return [
            'posts_total' => $this->scalarInt("SELECT COUNT(*) FROM post"),
            'users_total' => $this->scalarInt("SELECT COUNT(*) FROM user"),
            'views_total' => $this->safeTableExists('post_view_event')
                ? $this->scalarInt("SELECT COUNT(*) FROM post_view_event")
                : 0,
            'errors_total' => $this->safeTableExists('app_error_event')
                ? $this->scalarInt("SELECT COUNT(*) FROM app_error_event")
                : 0,
            'views_24h' => $this->safeTableExists('post_view_event')
                ? $this->scalarInt("SELECT COUNT(*) FROM post_view_event WHERE viewed_at >= (NOW() - INTERVAL 1 DAY)")
                : 0,
            'errors_24h' => $this->safeTableExists('app_error_event')
                ? $this->scalarInt("SELECT COUNT(*) FROM app_error_event WHERE created_at >= (NOW() - INTERVAL 1 DAY)")
                : 0,
        ];
    }

    public function getPopularPosts(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $sql = "SELECT p.id, p.view_count, p.cost, p.created_at,
                a.name AS action_name, o.name AS object_name, c.name AS city_name, ar.name AS area_name
                FROM post p
                JOIN action a ON p.action_id = a.id
                JOIN objectsale o ON p.object_id = o.id
                JOIN city c ON p.city_id = c.id
                JOIN area ar ON p.area_id = ar.id
                ORDER BY p.view_count DESC, p.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDailyActivity(int $days = 7): array
    {
        $days = max(1, min(30, $days));
        $from = (new \DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
        $result = [];

        $viewsByDay = [];
        if ($this->safeTableExists('post_view_event')) {
            $stmt = $this->db->prepare("SELECT DATE(viewed_at) d, COUNT(*) c FROM post_view_event WHERE viewed_at >= ? GROUP BY DATE(viewed_at)");
            $stmt->execute([$from]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $viewsByDay[(string) $row['d']] = (int) $row['c'];
            }
        }

        $postsByDay = [];
        $stmt = $this->db->prepare("SELECT DATE(created_at) d, COUNT(*) c FROM post WHERE created_at >= ? GROUP BY DATE(created_at)");
        $stmt->execute([$from]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $postsByDay[(string) $row['d']] = (int) $row['c'];
        }

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = (new \DateTimeImmutable('today'))->modify('-' . $i . ' days')->format('Y-m-d');
            $result[] = [
                'date' => $day,
                'views' => $viewsByDay[$day] ?? 0,
                'new_posts' => $postsByDay[$day] ?? 0,
            ];
        }

        return $result;
    }

    public function getRecentErrors(int $limit = 20): array
    {
        if (!$this->safeTableExists('app_error_event')) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $stmt = $this->db->prepare("SELECT level, message, url, created_at FROM app_error_event ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function scalarInt(string $sql): int
    {
        $stmt = $this->db->query($sql);
        return (int) $stmt->fetchColumn();
    }

    private function safeTableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
            ");
            $stmt->execute([$table]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
