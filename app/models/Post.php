<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Post
{
    public function __construct(
        private PDO $db
    ) {}

    public function count(): int
    {
        return $this->countFiltered([]);
    }

    public function countFiltered(array $filters): int
    {
        $where = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM post p JOIN action a ON p.action_id = a.id JOIN objectsale o ON p.object_id = o.id JOIN city c ON p.city_id = c.id JOIN area ar ON p.area_id = ar.id" . $where['sql'];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($where['params']);
        return (int) $stmt->fetchColumn();
    }

    private function buildWhere(array $filters): array
    {
        $conds = [];
        $params = [];
        $includeArchived = !empty($filters['include_archived']);
        $status = (string) ($filters['status'] ?? '');
        if (!$includeArchived) {
            if ($status === 'archived') {
                $conds[] = "p.status = 'archived'";
            } else {
                $conds[] = "p.status = 'active'";
            }
        } elseif (in_array($status, ['active', 'archived'], true)) {
            $conds[] = 'p.status = ?';
            $params[] = $status;
        }
        if (!empty($filters['post_id'])) {
            $conds[] = 'p.id = ?';
            $params[] = (int) $filters['post_id'];
        }
        if (!empty($filters['city_id'])) {
            $conds[] = 'p.city_id = ?';
            $params[] = (int) $filters['city_id'];
        }
        if (!empty($filters['action_id'])) {
            $conds[] = 'p.action_id = ?';
            $params[] = (int) $filters['action_id'];
        }
        if (isset($filters['room']) && $filters['room'] !== '') {
            $conds[] = 'p.room = ?';
            $params[] = (int) $filters['room'];
        }
        if (!empty($filters['price_min'])) {
            $conds[] = 'p.cost >= ?';
            $params[] = (int) $filters['price_min'];
        }
        if (!empty($filters['price_max'])) {
            $conds[] = 'p.cost <= ?';
            $params[] = (int) $filters['price_max'];
        }
        return ['sql' => $conds ? ' WHERE ' . implode(' AND ', $conds) : '', 'params' => $params];
    }

    public function getList(int $limit = 50, int $offset = 0, array $filters = [], string $sort = 'date_desc'): array
    {
        $where = $this->buildWhere($filters);
        $order = match ($sort) {
            'price_asc' => 'p.cost ASC',
            'price_desc' => 'p.cost DESC',
            'date_asc' => 'p.created_at ASC',
            default => 'p.created_at DESC',
        };
        $sql = "SELECT p.id, p.user_id, p.action_id, p.created_at, p.room, p.m2, p.street, p.phone, p.cost, p.title, p.descr_post, p.new_house, p.view_count,
                a.name AS action_name, o.name AS object_name, c.name AS city_name, ar.name AS area_name
                FROM post p
                JOIN action a ON p.action_id = a.id
                JOIN objectsale o ON p.object_id = o.id
                JOIN city c ON p.city_id = c.id
                JOIN area ar ON p.area_id = ar.id"
                . $where['sql'] . " ORDER BY " . $order . " LIMIT ? OFFSET ?";
        $params = array_merge($where['params'], [$limit, $offset]);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT p.*, a.name AS action_name, o.name AS object_name, c.name AS city_name, ar.name AS area_name, u.name AS user_name
                FROM post p
                JOIN action a ON p.action_id = a.id
                JOIN objectsale o ON p.object_id = o.id
                JOIN city c ON p.city_id = c.id
                JOIN area ar ON p.area_id = ar.id
                JOIN user u ON p.user_id = u.id
                WHERE p.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByIds(array $ids, bool $includeArchived = false): array
    {
        if (empty($ids)) return [];
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT p.id, p.user_id, p.action_id, p.created_at, p.room, p.m2, p.street, p.phone, p.cost, p.title, p.descr_post, p.new_house, p.view_count,
                a.name AS action_name, o.name AS object_name, c.name AS city_name, ar.name AS area_name
                FROM post p
                JOIN action a ON p.action_id = a.id
                JOIN objectsale o ON p.object_id = o.id
                JOIN city c ON p.city_id = c.id
                JOIN area ar ON p.area_id = ar.id
                WHERE p.id IN ($placeholders)";
        if (!$includeArchived) {
            $sql .= " AND p.status = 'active'";
        }
        $sql .= "
                ORDER BY FIELD(p.id, " . implode(',', $ids) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUserId(int $userId): array
    {
        $sql = "SELECT p.id, p.user_id, p.created_at, p.room, p.m2, p.street, p.phone, p.cost, p.title, p.descr_post, p.new_house, p.view_count, p.status, p.expires_at,
                a.name AS action_name, o.name AS object_name, c.name AS city_name, ar.name AS area_name
                FROM post p
                JOIN action a ON p.action_id = a.id
                JOIN objectsale o ON p.object_id = o.id
                JOIN city c ON p.city_id = c.id
                JOIN area ar ON p.area_id = ar.id
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE post SET action_id=?, object_id=?, city_id=?, area_id=?, room=?, m2=?, street=?, phone=?, cost=?, title=?, descr_post=?, new_house=? WHERE id=? AND user_id=?");
        return $stmt->execute([
            $data['action_id'],
            $data['object_id'],
            $data['city_id'],
            $data['area_id'],
            (int) ($data['room'] ?? 0),
            (int) ($data['m2'] ?? 0),
            $data['street'] ?? '',
            $data['phone'] ?? '',
            (int) ($data['cost'] ?? 0),
            $data['title'] ?? 'Объявление',
            $data['descr_post'] ?? '',
            (int) (!empty($data['new_house'])),
            $id,
            $userId,
        ]);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO post (user_id, action_id, object_id, city_id, area_id, room, m2, street, phone, cost, title, descr_post, client_ip, new_house, status, published_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY))";
        $stmt = $this->db->prepare($sql);
        $ip = ip2long($_SERVER['REMOTE_ADDR'] ?? '0') ?: 0;
        $stmt->execute([
            $data['user_id'],
            $data['action_id'],
            $data['object_id'],
            $data['city_id'],
            $data['area_id'],
            (int) ($data['room'] ?? 0),
            (int) ($data['m2'] ?? 0),
            $data['street'] ?? '',
            $data['phone'] ?? '',
            (int) ($data['cost'] ?? 0),
            $data['title'] ?? 'Объявление',
            $data['descr_post'] ?? '',
            $ip,
            (int) (!empty($data['new_house'])),
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM post WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function archive(int $id, int $actorUserId, string $reason): bool
    {
        $stmt = $this->db->prepare("
            UPDATE post
            SET status = 'archived',
                archived_at = NOW(),
                archived_by_user_id = ?,
                archive_reason = ?
            WHERE id = ? AND status <> 'archived'
        ");
        return $stmt->execute([$actorUserId, $reason, $id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("
            UPDATE post
            SET status = 'active',
                archived_at = NULL,
                archived_by_user_id = NULL,
                archive_reason = NULL,
                expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY),
                expiry_notified_at = NULL
            WHERE id = ? AND status = 'archived'
        ");
        return $stmt->execute([$id]);
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM post WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getExpiredActiveForProcessing(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $sql = "
            SELECT p.id, p.user_id, p.title, p.expiry_notified_at, u.email, u.name
            FROM post p
            JOIN user u ON u.id = p.user_id
            WHERE p.status = 'active'
              AND p.expires_at <= NOW()
            ORDER BY p.expires_at ASC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countExpiredActiveForProcessing(): int
    {
        $stmt = $this->db->query("
            SELECT COUNT(*)
            FROM post
            WHERE status = 'active'
              AND expires_at <= NOW()
        ");

        return (int) $stmt->fetchColumn();
    }

    public function markExpiryNotified(int $postId): void
    {
        $stmt = $this->db->prepare("UPDATE post SET expiry_notified_at = NOW() WHERE id = ?");
        $stmt->execute([$postId]);
    }

    public function getExpiryAutomationTotals(): array
    {
        $stmt = $this->db->query("
            SELECT
                SUM(CASE WHEN status = 'archived' AND archive_reason = 'expired' THEN 1 ELSE 0 END) AS archived_total,
                SUM(CASE WHEN archive_reason = 'expired' AND expiry_notified_at IS NOT NULL THEN 1 ELSE 0 END) AS notified_total
            FROM post
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'archived_total' => (int) ($row['archived_total'] ?? 0),
            'notified_total' => (int) ($row['notified_total'] ?? 0),
        ];
    }

    public function incrementViewCount(int $postId): void
    {
        $stmt = $this->db->prepare("UPDATE post SET view_count = view_count + 1 WHERE id = ?");
        $stmt->execute([$postId]);
    }

    public function addViewEvent(int $postId, ?int $userId, string $sessionHash, string $ipHash, string $userAgent): void
    {
        $stmt = $this->db->prepare("INSERT INTO post_view_event (post_id, user_id, session_hash, ip_hash, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$postId, $userId, $sessionHash, $ipHash, substr($userAgent, 0, 255)]);
    }

    public function getPopular(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $sql = "SELECT p.id, p.view_count, p.cost, p.room, p.m2, p.street, p.created_at,
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

    public function getActivity(int $days = 7): array
    {
        $days = max(1, min(30, $days));
        $from = (new \DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');

        $viewStmt = $this->db->prepare("
            SELECT DATE(viewed_at) AS d, COUNT(*) AS c
            FROM post_view_event
            WHERE viewed_at >= ?
            GROUP BY DATE(viewed_at)
        ");
        $viewStmt->execute([$from]);
        $viewsByDay = [];
        foreach ($viewStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $viewsByDay[(string) $row['d']] = (int) $row['c'];
        }

        $postStmt = $this->db->prepare("
            SELECT DATE(created_at) AS d, COUNT(*) AS c
            FROM post
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
        ");
        $postStmt->execute([$from]);
        $postsByDay = [];
        foreach ($postStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $postsByDay[(string) $row['d']] = (int) $row['c'];
        }

        $result = [];
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

    public function getActiveForSitemap(int $limit = 50000): array
    {
        $limit = max(1, min(100000, $limit));
        $stmt = $this->db->prepare("
            SELECT id, created_at
            FROM post
            WHERE status = 'active'
            ORDER BY id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveSitemapFilterValues(int $limit = 200): array
    {
        $limit = max(1, min(2000, $limit));

        $cityStmt = $this->db->prepare("
            SELECT DISTINCT city_id
            FROM post
            WHERE status = 'active' AND city_id > 0
            ORDER BY city_id ASC
            LIMIT ?
        ");
        $cityStmt->bindValue(1, $limit, PDO::PARAM_INT);
        $cityStmt->execute();
        $cityIds = array_map('intval', $cityStmt->fetchAll(PDO::FETCH_COLUMN));

        $actionStmt = $this->db->prepare("
            SELECT DISTINCT action_id
            FROM post
            WHERE status = 'active' AND action_id > 0
            ORDER BY action_id ASC
            LIMIT ?
        ");
        $actionStmt->bindValue(1, $limit, PDO::PARAM_INT);
        $actionStmt->execute();
        $actionIds = array_map('intval', $actionStmt->fetchAll(PDO::FETCH_COLUMN));

        $roomStmt = $this->db->prepare("
            SELECT DISTINCT room
            FROM post
            WHERE status = 'active' AND room > 0
            ORDER BY room ASC
            LIMIT ?
        ");
        $roomStmt->bindValue(1, $limit, PDO::PARAM_INT);
        $roomStmt->execute();
        $rooms = array_map('intval', $roomStmt->fetchAll(PDO::FETCH_COLUMN));

        $comboStmt = $this->db->prepare("
            SELECT city_id, action_id, room, COUNT(*) AS c
            FROM post
            WHERE status = 'active'
              AND city_id > 0
              AND action_id > 0
              AND room > 0
            GROUP BY city_id, action_id, room
            ORDER BY c DESC
            LIMIT ?
        ");
        $comboStmt->bindValue(1, $limit, PDO::PARAM_INT);
        $comboStmt->execute();
        $combos = [];
        foreach ($comboStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $combos[] = [
                'city_id' => (int) ($row['city_id'] ?? 0),
                'action_id' => (int) ($row['action_id'] ?? 0),
                'room' => (int) ($row['room'] ?? 0),
            ];
        }

        return [
            'city_ids' => $cityIds,
            'action_ids' => $actionIds,
            'rooms' => $rooms,
            'combos' => $combos,
        ];
    }
}
