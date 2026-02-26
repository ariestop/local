<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class PostPhoto
{
    public function __construct(
        private PDO $db
    ) {}

    public function getByPostId(int $postId): array
    {
        $stmt = $this->db->prepare("SELECT filename, sort_order FROM post_photo WHERE post_id = ? ORDER BY sort_order");
        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFirstByPostIds(array $postIds): array
    {
        if (empty($postIds)) return [];
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        $sql = "SELECT post_id, filename FROM post_photo WHERE post_id IN ($placeholders) ORDER BY post_id, sort_order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($postIds));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $r) {
            if (!isset($result[$r['post_id']])) {
                $result[$r['post_id']] = $r['filename'];
            }
        }
        return $result;
    }

    public function add(int $postId, string $filename, int $sortOrder): void
    {
        $stmt = $this->db->prepare("INSERT INTO post_photo (post_id, filename, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $filename, $sortOrder]);
    }

    public function addBatch(int $postId, array $photos): void
    {
        foreach ($photos as $p) {
            $this->add($postId, $p['filename'], $p['sort_order']);
        }
    }

    public function deleteByFilename(int $postId, string $filename): void
    {
        $stmt = $this->db->prepare("DELETE FROM post_photo WHERE post_id = ? AND filename = ?");
        $stmt->execute([$postId, $filename]);
    }

    public function countByPostId(int $postId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM post_photo WHERE post_id = ?");
        $stmt->execute([$postId]);
        return (int) $stmt->fetchColumn();
    }

    public function deleteByPostId(int $postId): void
    {
        $stmt = $this->db->prepare("DELETE FROM post_photo WHERE post_id = ?");
        $stmt->execute([$postId]);
    }

    public function getMaxSortOrder(int $postId): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM post_photo WHERE post_id = ?");
        $stmt->execute([$postId]);
        return (int) $stmt->fetchColumn();
    }

    public function updateSortOrder(int $postId, array $filenamesInOrder): void
    {
        foreach ($filenamesInOrder as $sortOrder => $filename) {
            $filename = basename(trim($filename));
            if ($filename === '') continue;
            $stmt = $this->db->prepare("UPDATE post_photo SET sort_order = ? WHERE post_id = ? AND filename = ?");
            $stmt->execute([$sortOrder, $postId, $filename]);
        }
    }
}
