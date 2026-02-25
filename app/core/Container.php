<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Post;
use App\Models\PostPhoto;
use App\Models\Reference;
use App\Models\User;
use App\Repositories\PostPhotoRepository;
use App\Repositories\PostRepository;
use App\Repositories\ReferenceRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\ImageService;
use App\Log\LoggerInterface;
use App\Services\PostService;
use PDO;

class Container
{
    private array $instances = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function get(string $id): mixed
    {
        if (!isset($this->instances[$id])) {
            $this->instances[$id] = $this->resolve($id);
        }
        return $this->instances[$id];
    }

    private function resolve(string $id): mixed
    {
        return match ($id) {
            PDO::class, 'db' => Database::getConnection($this->config['db']),
            LoggerInterface::class, 'logger' => $this->createLogger(),
            Post::class => new Post($this->get(PDO::class)),
            PostPhoto::class => new PostPhoto($this->get(PDO::class)),
            Reference::class => new Reference($this->get(PDO::class)),
            User::class => new User($this->get(PDO::class)),
            PostRepository::class => new PostRepository($this->get(Post::class)),
            PostPhotoRepository::class => new PostPhotoRepository($this->get(PostPhoto::class)),
            ReferenceRepository::class => new ReferenceRepository($this->get(Reference::class)),
            UserRepository::class => new UserRepository($this->get(User::class)),
            ImageService::class => new ImageService($this->config['images_path'] ?? dirname(__DIR__, 2) . '/public/images'),
            AuthService::class => new AuthService($this->get(UserRepository::class), $this->get(LoggerInterface::class)),
            PostService::class => new PostService(
                $this->get(PostRepository::class),
                $this->get(PostPhotoRepository::class),
                $this->get(ReferenceRepository::class),
                $this->get(ImageService::class),
                (int) ($this->config['app']['max_price'] ?? 999_000_000),
                $this->get(LoggerInterface::class)
            ),
            \App\Controllers\MainController::class => new \App\Controllers\MainController($this),
            \App\Controllers\UserController::class => new \App\Controllers\UserController($this),
            \App\Controllers\ApiController::class => new \App\Controllers\ApiController($this),
            default => throw new \InvalidArgumentException("Unknown service: {$id}"),
        };
    }

    private function createLogger(): LoggerInterface
    {
        if (!class_exists(\Monolog\Logger::class)) {
            return new \App\Log\NullLogger();
        }
        $logPath = $this->config['log_path'] ?? dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logPath)) {
            @mkdir($logPath, 0755, true);
        }
        $monolog = new \Monolog\Logger('app');
        $monolog->pushHandler(new \Monolog\Handler\StreamHandler($logPath . '/app.log', \Monolog\Level::Debug));
        return new \App\Log\MonologAdapter($monolog);
    }
}
