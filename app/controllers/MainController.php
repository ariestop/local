<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Services\PostService;

class MainController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->postService = $container->get(PostService::class);
    }

    private PostService $postService;

    public function index(): void
    {
        $perPage = 20;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->postService->getPaginatedList($perPage, $page);
        $this->render('main/index', [
            'posts' => $result['posts'],
            'user' => $this->getLoggedUser(),
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
        ]);
    }

    public function detail(string $id): void
    {
        $postId = (int) $id;
        $data = $this->postService->getDetail($postId);
        if (!$data) {
            http_response_code(404);
            $this->render('main/404');
            return;
        }
        $this->render('main/detail', [
            'post' => $data['post'],
            'photos' => $data['photos'],
            'user' => $this->getLoggedUser(),
        ]);
    }

    public function add(): void
    {
        $this->requireAuth();
        $data = $this->postService->getFormData();
        $this->render('main/add', $data);
    }

    public function addSubmit(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], 403);
            return;
        }
        $user = $this->getLoggedUser();
        $result = $this->postService->create($_POST, $_FILES, (int) $user['id']);
        if (!$result['success']) {
            $this->json(['success' => false, 'error' => $result['error']], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true, 'id' => $result['id']]);
    }

    public function myPosts(): void
    {
        $this->requireAuth();
        $user = $this->getLoggedUser();
        $posts = $this->postService->getByUserId((int) $user['id']);
        $postIds = array_column($posts, 'id');
        $firstPhotos = $this->postService->getFirstPhotosForPosts($postIds);
        $this->render('main/edit-advert', [
            'posts' => $posts,
            'firstPhotos' => $firstPhotos,
            'user' => $user,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $user = $this->getLoggedUser();
        $data = $this->postService->getForEdit((int) $id, (int) $user['id']);
        if (!$data) {
            http_response_code(404);
            $this->render('main/404');
            return;
        }
        $this->render('main/edit', $data);
    }

    public function editSubmit(string $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], 403);
            return;
        }
        $user = $this->getLoggedUser();
        $result = $this->postService->update((int) $id, $_POST, $_FILES, (int) $user['id']);
        if (!$result['success']) {
            $this->json(['success' => false, 'error' => $result['error']], $result['code'] ?? 400);
            return;
        }
        $this->json(['success' => true, 'id' => (int) $id]);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if ($token === '' || !hash_equals(csrf_token(), $token)) {
            $this->json(['success' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], 403);
            return;
        }
        $user = $this->getLoggedUser();
        $result = $this->postService->delete((int) $id, (int) $user['id']);
        if (!$result['success']) {
            $this->json(['success' => false, 'error' => $result['error']], $result['code'] ?? 404);
            return;
        }
        $this->json(['success' => true]);
    }

    protected function render(string $view, array $data = []): void
    {
        $data['view'] = $view;
        $data['config'] = $this->config;
        $data['user'] = $data['user'] ?? $this->getLoggedUser();
        extract($data);
        require dirname(__DIR__) . '/views/layout.php';
    }
}
