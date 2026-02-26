<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class Favorite
{
    public function __construct(
        private PDO $db
    ) {}

    public function add(int $userId, int $postId): bool
    {
        try {
            $stmt = $this->db->prepare("INSERT IGNORE INTO user_favorite (user_id, post_id) VALUES (?, ?)");
            $stmt->execute([$userId, $postId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function remove(int $userId, int $postId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM user_favorite WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$userId, $postId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function toggle(int $userId, int $postId): bool
    {
        if ($this->has($userId, $postId)) {
            $this->remove($userId, $postId);
            return false;
        }
        return $this->add($userId, $postId);
    }

    public function has(int $userId, int $postId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM user_favorite WHERE user_id = ? AND post_id = ?");
            $stmt->execute([$userId, $postId]);
            return (bool) $stmt->fetch();
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function getPostIdsByUserId(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT post_id FROM user_favorite WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'post_id');
        } catch (\PDOException $e) {
            return [];
        }
    }
}
