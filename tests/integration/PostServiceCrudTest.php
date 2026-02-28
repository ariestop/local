<?php

declare(strict_types=1);

use App\Repositories\PostPhotoRepository;
use App\Repositories\PostRepository;
use App\Repositories\ReferenceRepository;
use App\Services\ImageService;
use App\Services\MailService;
use App\Services\PostService;
use PHPUnit\Framework\TestCase;

final class PostServiceCrudTest extends TestCase
{
    public function testCreateHappyPathReturnsId(): void
    {
        $postRepo = $this->createMock(PostRepository::class);
        $photoRepo = $this->createMock(PostPhotoRepository::class);
        $refRepo = $this->createMock(ReferenceRepository::class);
        $imageService = $this->createMock(ImageService::class);
        $mailService = $this->createMock(MailService::class);
        $db = new \PDO('sqlite::memory:');

        $postRepo->method('create')->willReturn(101);
        $imageService->method('upload')->willReturn([
            ['filename' => '1_x.jpg', 'sort_order' => 0],
        ]);
        $photoRepo->expects($this->once())->method('addBatch')->with(
            101,
            [['filename' => '1_x.jpg', 'sort_order' => 0]]
        );

        $service = new PostService(
            $postRepo,
            $photoRepo,
            $refRepo,
            $imageService,
            $mailService,
            $db
        );

        $result = $service->create($this->validPostInput(), $this->validFilesPayload(), 7);

        $this->assertTrue((bool) ($result['success'] ?? false));
        $this->assertSame(101, $result['id'] ?? null);
    }

    public function testUpdateForNonOwnerReturnsNotFoundContract(): void
    {
        $postRepo = $this->createMock(PostRepository::class);
        $photoRepo = $this->createMock(PostPhotoRepository::class);
        $refRepo = $this->createMock(ReferenceRepository::class);
        $imageService = $this->createMock(ImageService::class);
        $mailService = $this->createMock(MailService::class);
        $db = new \PDO('sqlite::memory:');

        $postRepo->method('getById')->willReturn(['id' => 5, 'user_id' => 99]);
        $service = new PostService(
            $postRepo,
            $photoRepo,
            $refRepo,
            $imageService,
            $mailService,
            $db
        );

        $result = $service->update(5, $this->validPostInput(), [], 7);

        $this->assertFalse((bool) ($result['success'] ?? true));
        $this->assertSame(404, $result['code'] ?? null);
    }

    public function testDeleteForbiddenForNonOwnerAndNonAdmin(): void
    {
        $postRepo = $this->createMock(PostRepository::class);
        $photoRepo = $this->createMock(PostPhotoRepository::class);
        $refRepo = $this->createMock(ReferenceRepository::class);
        $imageService = $this->createMock(ImageService::class);
        $mailService = $this->createMock(MailService::class);
        $db = new \PDO('sqlite::memory:');

        $postRepo->method('getById')->willReturn(['id' => 6, 'user_id' => 99]);
        $service = new PostService(
            $postRepo,
            $photoRepo,
            $refRepo,
            $imageService,
            $mailService,
            $db
        );

        $result = $service->delete(6, 7, false);

        $this->assertFalse((bool) ($result['success'] ?? true));
        $this->assertSame(403, $result['code'] ?? null);
    }

    public function testHardDeleteForbiddenForNonAdmin(): void
    {
        $postRepo = $this->createMock(PostRepository::class);
        $photoRepo = $this->createMock(PostPhotoRepository::class);
        $refRepo = $this->createMock(ReferenceRepository::class);
        $imageService = $this->createMock(ImageService::class);
        $mailService = $this->createMock(MailService::class);
        $db = new \PDO('sqlite::memory:');

        $service = new PostService(
            $postRepo,
            $photoRepo,
            $refRepo,
            $imageService,
            $mailService,
            $db
        );

        $result = $service->hardDelete(6, 7, false);

        $this->assertFalse((bool) ($result['success'] ?? true));
        $this->assertSame(403, $result['code'] ?? null);
    }

    /**
     * @return array<string,mixed>
     */
    private function validPostInput(): array
    {
        return [
            'action_id' => '1',
            'object_id' => '2',
            'city_id' => '1',
            'area_id' => '1',
            'street' => 'Ленина 1',
            'phone' => '79991234567',
            'cost' => '5 400 000',
            'descr_post' => 'Отличная квартира',
            'room' => '2',
            'm2' => '52',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function validFilesPayload(): array
    {
        return [
            'photos' => [
                'name' => ['flat.jpg'],
                'type' => ['image/jpeg'],
                'tmp_name' => ['/tmp/php123'],
                'error' => [0],
                'size' => [1024],
            ],
        ];
    }
}
