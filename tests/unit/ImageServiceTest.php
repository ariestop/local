<?php

declare(strict_types=1);

use App\Services\ImageService;
use PHPUnit\Framework\TestCase;

final class ImageServiceTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/m2_image_tests_' . uniqid('', true);
        mkdir($this->basePath, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDir($this->basePath);
    }

    public function testUploadSkipsFilesWithInvalidMimeType(): void
    {
        $service = new ImageService($this->basePath);
        $result = $service->upload(1, 1, [
            'name' => ['payload.php'],
            'type' => ['application/x-php'],
            'tmp_name' => ['/tmp/not-uploaded'],
            'size' => [512],
        ]);

        $this->assertSame([], $result);
    }

    public function testUploadSkipsFilesOverMaxSize(): void
    {
        $service = new ImageService($this->basePath);
        $result = $service->upload(1, 1, [
            'name' => ['huge.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => ['/tmp/not-uploaded'],
            'size' => [ImageService::getMaxSizeBytes() + 1],
        ]);

        $this->assertSame([], $result);
    }

    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
                continue;
            }
            @unlink($path);
        }
        @rmdir($dir);
    }
}
