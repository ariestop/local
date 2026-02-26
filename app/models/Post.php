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
        $sql = "SELECT p.id, p.user_id, p.action_id, p.created_at, p.room, p.m2, p.street, p.phone, p.cost, p.title, p.descr_post, p.new_house,
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

    public function getByIds(array $ids): array
    {
        if (empty($ids)) return [];
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT p.id, p.user_id, p.action_id, p.created_at, p.room, p.m2, p.street, p.phone, p.cost, p.title, p.descr_post, p.new_house,
                a.name AS action_name, o.name AS object_name, c.name AS city_name, ar.name AS area_name
                FROM post p
                JOIN action a ON p.action_id = a.id
                JOIN objectsale o ON p.object_id = o.id
                JOIN city c ON p.city_id = c.id
                JOIN area ar ON p.area_id = ar.id
                WHERE p.id IN ($placeholders)
                ORDER BY FIELD(p.id, " . implode(',', $ids) . ")";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUserId(int $userId): array
    {
        $sql = "SELECT p.id, p.user_id, p.created_at, p.room, p.m2, p.street, p.phone, p.cost, p.title, p.descr_post, p.new_house,
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
        $sql = "INSERT INTO post (user_id, action_id, object_id, city_id, area_id, room, m2, street, phone, cost, title, descr_post, client_ip, new_house)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
}
