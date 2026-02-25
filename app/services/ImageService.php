<?php

declare(strict_types=1);

namespace App\Services;

class ImageService
{
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE = 5 * 1024 * 1024;
    private const MAX_FILES = 5;
    private const MAX_LARGE_W = 1200;
    private const MAX_LARGE_H = 675;

    public function __construct(private string $basePath) {}

    public function upload(int $userId, int $postId, array $files, ?int $maxFiles = null): array
    {
        $maxFiles = $maxFiles ?? self::MAX_FILES;
        $dir = rtrim($this->basePath, '/') . "/{$userId}/{$postId}";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $uploaded = [];
        $count = 0;
        foreach ($files['name'] ?? [] as $i => $name) {
            if ($count >= $maxFiles) break;
            if (empty($name) || empty($files['tmp_name'][$i])) continue;
            $type = $files['type'][$i] ?? '';
            $tmp = $files['tmp_name'][$i];
            if (!in_array($type, self::ALLOWED, true) || ($files['size'][$i] ?? 0) > self::MAX_SIZE) continue;
            $ext = match ($type) {
                'image/jpeg', 'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $filename = ($count + 1) . '_' . uniqid() . '.' . $ext;
            $path = $dir . '/' . $filename;
            if (move_uploaded_file($tmp, $path)) {
                $this->processImage($dir, $filename, $path);
                $uploaded[] = ['filename' => $filename, 'sort_order' => $count];
                $count++;
            }
        }
        return $uploaded;
    }

    private function processImage(string $dir, string $filename, string $path): void
    {
        $info = @getimagesize($path);
        if (!$info) {
            @unlink($path);
            return;
        }
        $src = match ($info[2] ?? 0) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null,
        };
        if (!$src) {
            @unlink($path);
            return;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $save = fn($img, $p) => match ($info[2]) {
            IMAGETYPE_JPEG => imagejpeg($img, $p, 85),
            IMAGETYPE_PNG => imagepng($img, $p, 8),
            IMAGETYPE_GIF => imagegif($img, $p),
            IMAGETYPE_WEBP => imagewebp($img, $p, 85),
            default => false,
        };

        $needResize = $w > self::MAX_LARGE_W || $h > self::MAX_LARGE_H;
        $largeW = $w;
        $largeH = $h;
        if ($needResize) {
            $ratio = min(self::MAX_LARGE_W / $w, self::MAX_LARGE_H / $h);
            $largeW = (int) round($w * $ratio);
            $largeH = (int) round($h * $ratio);
        }
        $largePath = $dir . '/' . $base . '_1200x675.' . $ext;
        $dstLarge = imagecreatetruecolor($largeW, $largeH);
        if ($dstLarge) {
            imagecopyresampled($dstLarge, $src, 0, 0, 0, 0, $largeW, $largeH, $w, $h);
            $save($dstLarge, $largePath);
            imagedestroy($dstLarge);
        }

        foreach ([[200, 150], [400, 300]] as [$tw, $th]) {
            $dst = imagecreatetruecolor($tw, $th);
            if ($dst) {
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
                $save($dst, $dir . '/' . $base . "_{$tw}x{$th}.{$ext}");
                imagedestroy($dst);
            }
        }
        imagedestroy($src);
        @unlink($path);
    }

    public function deletePostFolder(int $userId, int $postId): void
    {
        $dir = rtrim($this->basePath, '/') . "/{$userId}/{$postId}";
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    public function deletePhoto(int $userId, int $postId, string $filename): void
    {
        $dir = rtrim($this->basePath, '/') . "/{$userId}/{$postId}";
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $patterns = [
            $dir . '/' . $filename,
            $dir . '/' . $base . '_200x150.' . $ext,
            $dir . '/' . $base . '_400x300.' . $ext,
            $dir . '/' . $base . '_1200x675.' . $ext,
        ];
        foreach ($patterns as $p) {
            if (is_file($p)) {
                @unlink($p);
            }
        }
    }
}
