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
        $tmp = tempnam($this->basePath, 'bad_');
        file_put_contents((string) $tmp, "<?php echo 'x';");
        $result = $service->upload(1, 1, [
            'name' => ['payload.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$tmp],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize((string) $tmp) ?: 512],
        ]);

        $this->assertSame([], $result);
    }

    public function testUploadSkipsFilesOverMaxSize(): void
    {
        $service = new ImageService($this->basePath);
        $result = $service->upload(1, 1, [
            'name' => ['huge.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$this->createTempFile('x')],
            'error' => [UPLOAD_ERR_OK],
            'size' => [ImageService::getMaxSizeBytes() + 1],
        ]);

        $this->assertSame([], $result);
    }

    public function testUploadSkipsFilesWithUploadError(): void
    {
        $service = new ImageService($this->basePath);
        $result = $service->upload(1, 1, [
            'name' => ['broken.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$this->createTempFile('broken')],
            'error' => [UPLOAD_ERR_PARTIAL],
            'size' => [128],
        ]);

        $this->assertSame([], $result);
    }

    public function testUploadSkipsUnsupportedWebpEvenWhenClientTypeSaysImage(): void
    {
        $service = new ImageService($this->basePath);
        $tmp = tempnam($this->basePath, 'webp_');
        file_put_contents((string) $tmp, 'RIFFxxxxWEBPVP8 ');
        $result = $service->upload(1, 1, [
            'name' => ['photo.webp'],
            'type' => ['image/webp'],
            'tmp_name' => [$tmp],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize((string) $tmp) ?: 128],
        ]);

        $this->assertSame([], $result);
    }

    private function createTempFile(string $contents): string
    {
        $tmp = tempnam($this->basePath, 'img_');
        file_put_contents((string) $tmp, $contents);
        return (string) $tmp;
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
