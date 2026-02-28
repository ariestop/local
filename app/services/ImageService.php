<?php

declare(strict_types=1);

namespace App\Services;

class ImageService
{
    private const ALLOWED = ['image/jpeg', 'image/png'];
    private const MAX_SIZE = 5 * 1024 * 1024;

    public static function getMaxSizeBytes(): int
    {
        return self::MAX_SIZE;
    }
    private const MAX_FILES = 10;
    private const MAX_LARGE_W = 1200;
    private const MAX_LARGE_H = 675;

    public function __construct(private string $basePath) {}

    public function upload(int $userId, int $postId, array $files, ?int $maxFiles = null): array
    {
        $maxFiles = $maxFiles ?? self::MAX_FILES;
        $dir = $this->resolveTargetDir($userId, $postId);
        return $this->uploadToDir($dir, $files, $maxFiles);
    }

    /**
     * Upload images to an isolated staging directory.
     *
     * @return array{staging_dir: string, photos: array<int, array{filename: string, sort_order: int}>}
     */
    public function stageUpload(int $userId, int $postId, array $files, ?int $maxFiles = null): array
    {
        $maxFiles = $maxFiles ?? self::MAX_FILES;
        $stagingDir = $this->createStagingDir($userId, $postId);
        $photos = $this->uploadToDir($stagingDir, $files, $maxFiles);
        return [
            'staging_dir' => $stagingDir,
            'photos' => $photos,
        ];
    }

    /**
     * Move processed staged files to final post directory.
     */
    public function promoteStaged(string $stagingDir, int $userId, int $postId): void
    {
        if (!is_dir($stagingDir)) {
            return;
        }
        $targetDir = $this->resolveTargetDir($userId, $postId);
        $entries = array_diff(scandir($stagingDir) ?: [], ['.', '..']);
        foreach ($entries as $entry) {
            $from = $stagingDir . '/' . $entry;
            if (!is_file($from)) {
                continue;
            }
            $to = $targetDir . '/' . $entry;
            if (!@rename($from, $to)) {
                @copy($from, $to);
                @unlink($from);
            }
        }
        @rmdir($stagingDir);
    }

    /**
     * Best-effort cleanup for staging directories.
     */
    public function cleanupStaged(string $stagingDir): void
    {
        $this->deleteDirRecursive($stagingDir);
    }

    /**
     * @return array<int, array{filename: string, sort_order: int}>
     */
    private function uploadToDir(string $dir, array $files, int $maxFiles): array
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $uploaded = [];
        $count = 0;
        foreach ($files['name'] ?? [] as $i => $name) {
            if ($count >= $maxFiles) break;
            if (empty($name) || empty($files['tmp_name'][$i])) continue;
            $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            if ($error !== UPLOAD_ERR_OK) continue;

            $tmp = (string) $files['tmp_name'][$i];
            if (!$this->isUploadedTmpFile($tmp)) continue;
            if ((int) ($files['size'][$i] ?? 0) > self::MAX_SIZE) continue;

            $detectedMime = $this->detectServerMime($tmp);
            if ($detectedMime === null || !in_array($detectedMime, self::ALLOWED, true)) continue;

            $ext = $this->extensionFromMime($detectedMime);
            if ($ext === null) continue;

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

    private function isUploadedTmpFile(string $tmp): bool
    {
        if (is_uploaded_file($tmp)) {
            return true;
        }
        // Allow local files in CLI tests while keeping strict web checks.
        return PHP_SAPI === 'cli' && is_file($tmp);
    }

    private function detectServerMime(string $tmp): ?string
    {
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp);
            if (is_string($mime) && $mime !== '') {
                return strtolower(trim($mime));
            }
        }
        if (function_exists('exif_imagetype')) {
            $type = @exif_imagetype($tmp);
            if ($type === IMAGETYPE_JPEG) {
                return 'image/jpeg';
            }
            if ($type === IMAGETYPE_PNG) {
                return 'image/png';
            }
        }
        return null;
    }

    private function extensionFromMime(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => null,
        };
    }

    private function resolveTargetDir(int $userId, int $postId): string
    {
        return rtrim($this->basePath, '/') . "/{$userId}/{$postId}";
    }

    private function createStagingDir(int $userId, int $postId): string
    {
        $target = $this->resolveTargetDir($userId, $postId);
        $token = bin2hex(random_bytes(8));
        return $target . '/.staging_' . $token;
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
            $this->applyWatermark($dstLarge, $largeW, $largeH);
            $save($dstLarge, $largePath);
            imagedestroy($dstLarge);
        }

        foreach ([[200, 150], [750, 470]] as [$tw, $th]) {
            $isVertical = $h > $w;
            $dst = ($tw === 750 && $th === 470 && $isVertical)
                ? $this->createFittedVariant($src, $w, $h, $tw, $th)
                : $this->createCroppedVariant($src, $w, $h, $tw, $th);
            if ($dst) {
                if (!($tw === 200 && $th === 150)) {
                    $this->applyWatermark($dst, $tw, $th);
                }
                $save($dst, $dir . '/' . $base . "_{$tw}x{$th}.{$ext}");
                imagedestroy($dst);
            }
        }
        imagedestroy($src);
        @unlink($path);
    }

    private function createCroppedVariant($src, int $srcW, int $srcH, int $dstW, int $dstH)
    {
        $dst = imagecreatetruecolor($dstW, $dstH);
        if (!$dst) {
            return null;
        }

        $scale = max($dstW / max(1, $srcW), $dstH / max(1, $srcH));
        $cropW = (int) max(1, round($dstW / $scale));
        $cropH = (int) max(1, round($dstH / $scale));
        $srcX = (int) max(0, floor(($srcW - $cropW) / 2));
        $srcY = (int) max(0, floor(($srcH - $cropH) / 2));

        imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $dstW, $dstH, $cropW, $cropH);
        return $dst;
    }

    private function createFittedVariant($src, int $srcW, int $srcH, int $dstW, int $dstH)
    {
        $dst = imagecreatetruecolor($dstW, $dstH);
        if (!$dst) {
            return null;
        }

        $bg = imagecolorallocate($dst, 246, 248, 251);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $bg);

        $scale = min($dstW / max(1, $srcW), $dstH / max(1, $srcH));
        $fitW = (int) max(1, round($srcW * $scale));
        $fitH = (int) max(1, round($srcH * $scale));
        $dstX = (int) floor(($dstW - $fitW) / 2);
        $dstY = (int) floor(($dstH - $fitH) / 2);

        imagecopyresampled($dst, $src, $dstX, $dstY, 0, 0, $fitW, $fitH, $srcW, $srcH);
        return $dst;
    }

    private function applyWatermark($img, int $imgW, int $imgH): void
    {
        if ($imgW < 120 || $imgH < 80) {
            return;
        }

        imagealphablending($img, true);
        imagesavealpha($img, true);

        $font = $imgW >= 900 ? 5 : ($imgW >= 600 ? 4 : 3);
        $text = 'm2saratov.ru';
        $textW = imagefontwidth($font) * strlen($text);
        $textH = imagefontheight($font);
        $iconSize = max(12, (int) round($textH * 1.2));
        $gap = max(6, (int) round($iconSize * 0.35));
        $padding = max(8, (int) round(min($imgW, $imgH) * 0.025));
        $wmW = $iconSize + $gap + $textW;
        $wmH = max($iconSize, $textH);
        $x = max(0, $imgW - $padding - $wmW);
        $y = max(0, $imgH - $padding - $wmH);

        $textShadow = imagecolorallocatealpha($img, 0, 0, 0, 95);
        $textColor = imagecolorallocatealpha($img, 255, 255, 255, 58);
        $lineColor = imagecolorallocatealpha($img, 255, 255, 255, 58);

        imagestring($img, $font, $x + $iconSize + $gap + 1, $y + 1, $text, $textShadow);
        imagestring($img, $font, $x + $iconSize + $gap, $y, $text, $textColor);

        $ix = $x;
        $iy = $y + max(0, (int) floor(($wmH - $iconSize) / 2));
        $left = $ix;
        $top = $iy;
        $right = $ix + $iconSize;
        $bottom = $iy + $iconSize;
        $midX = (int) floor(($left + $right) / 2);
        $roofY = $top + (int) floor($iconSize * 0.35);
        $baseY = $bottom - 1;

        imagesetthickness($img, 2);
        imageline($img, $left, $roofY, $midX, $top, $lineColor);
        imageline($img, $midX, $top, $right, $roofY, $lineColor);
        imagerectangle($img, $left + 1, $roofY, $right - 1, $baseY, $lineColor);
        $doorW = max(3, (int) floor($iconSize * 0.22));
        $doorH = max(4, (int) floor($iconSize * 0.3));
        $doorX1 = $midX - (int) floor($doorW / 2);
        $doorY1 = $baseY - $doorH;
        imagerectangle($img, $doorX1, $doorY1, $doorX1 + $doorW, $baseY, $lineColor);
        imagesetthickness($img, 1);
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
            $dir . '/' . $base . '_750x470.' . $ext,
            $dir . '/' . $base . '_1200x675.' . $ext,
        ];
        foreach ($patterns as $p) {
            if (is_file($p)) {
                @unlink($p);
            }
        }
    }

    private function deleteDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $entries = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($entries as $entry) {
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->deleteDirRecursive($path);
                continue;
            }
            @unlink($path);
        }
        @rmdir($dir);
    }
}
