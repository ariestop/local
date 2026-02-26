<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Services\PostService;

class MainController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->postService = $container->get(PostService::class);
        $this->favoriteRepo = $container->get(FavoriteRepository::class);
    }

    private PostService $postService;
    private FavoriteRepository $favoriteRepo;

    public function index(): void
    {
        $perPage = 20;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $filters = [
            'city_id' => $_GET['city_id'] ?? '',
            'action_id' => $_GET['action_id'] ?? '',
            'room' => $_GET['room'] ?? '',
            'price_min' => $_GET['price_min'] ?? '',
            'price_max' => $_GET['price_max'] ?? '',
        ];
        $sort = in_array($_GET['sort'] ?? '', ['date_desc', 'date_asc', 'price_asc', 'price_desc'])
            ? $_GET['sort'] : 'date_desc';
        $result = $this->postService->getPaginatedList($perPage, $page, $filters, $sort);
        $user = $this->getLoggedUser();
        $favoriteIds = $user ? $this->favoriteRepo->getPostIdsByUserId((int) $user['id']) : [];
        $this->render('main/index', [
            'posts' => $result['posts'],
            'user' => $user,
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
            'filters' => $filters,
            'sort' => $sort,
            'actions' => $this->postService->getFormData()['actions'] ?? [],
            'cities' => $this->postService->getFormData()['cities'] ?? [],
            'favoriteIds' => $favoriteIds,
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
        $user = $this->getLoggedUser();
        $isFavorite = $user && $this->favoriteRepo->has((int) $user['id'], $postId);
        $this->render('main/detail', [
            'post' => $data['post'],
            'photos' => $data['photos'],
            'user' => $user,
            'isFavorite' => $isFavorite,
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

    public function favorites(): void
    {
        $this->requireAuth();
        $user = $this->getLoggedUser();
        $ids = $this->favoriteRepo->getPostIdsByUserId((int) $user['id']);
        $posts = $this->postService->getPostsByIds($ids);
        $firstPhotos = $this->postService->getFirstPhotosForPosts($ids);
        $this->render('main/favorites', [
            'posts' => $posts,
            'firstPhotos' => $firstPhotos,
            'user' => $user,
        ]);
    }
}
