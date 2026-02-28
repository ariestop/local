<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Container;
use App\Core\Controller;
use App\Repositories\FavoriteRepository;
use App\Services\PostService;
use App\Services\SeoService;

class MainController extends Controller
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->postService = $container->get(PostService::class);
        $this->favoriteRepo = $container->get(FavoriteRepository::class);
        $this->seoService = $container->get(SeoService::class);
    }

    private PostService $postService;
    private FavoriteRepository $favoriteRepo;
    private SeoService $seoService;

    public function index(): void
    {
        $perPage = 20;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $user = $this->getLoggedUser();
        $isAdmin = (int) ($user['is_admin'] ?? 0) === 1;
        $filters = [
            'city_id' => $_GET['city_id'] ?? '',
            'action_id' => $_GET['action_id'] ?? '',
            'room' => $_GET['room'] ?? '',
            'price_min' => $_GET['price_min'] ?? '',
            'price_max' => $_GET['price_max'] ?? '',
            'post_id' => $isAdmin ? ($_GET['post_id'] ?? '') : '',
        ];
        $sort = in_array($_GET['sort'] ?? '', ['date_desc', 'date_asc', 'price_asc', 'price_desc'])
            ? $_GET['sort'] : 'date_desc';
        $result = $this->postService->getPaginatedList($perPage, $page, $filters, $sort);
        $formData = $this->postService->getFormData();
        $popularPosts = $this->postService->getPopular(5);
        $activity = $this->postService->getActivity(7);
        $favoriteIds = $user ? $this->favoriteRepo->getPostIdsByUserId((int) $user['id']) : [];
        $postIds = array_map(static fn(array $p): int => (int) ($p['id'] ?? 0), $result['posts']);
        $firstPhotos = $this->postService->getFirstPhotosForPosts(array_values(array_filter($postIds)));
        $seo = $this->seoService->buildIndexSeo(
            $filters,
            $sort,
            $page,
            $formData['actions'] ?? [],
            $formData['cities'] ?? [],
            $_GET
        );
        $this->render('main/index', [
            'posts' => $result['posts'],
            'user' => $user,
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
            'filters' => $filters,
            'sort' => $sort,
            'actions' => $formData['actions'] ?? [],
            'cities' => $formData['cities'] ?? [],
            'favoriteIds' => $favoriteIds,
            'firstPhotos' => $firstPhotos,
            'popularPosts' => $popularPosts,
            'activity' => $activity,
            'isAdmin' => $isAdmin,
            'seo' => $seo,
            'breadcrumbs' => $seo['breadcrumbs'] ?? [],
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
        $isAdmin = (int) ($user['is_admin'] ?? 0) === 1;
        $isOwner = $user && (int) ($data['post']['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
        if (($data['post']['status'] ?? 'active') !== 'active' && !$isAdmin && !$isOwner) {
            http_response_code(404);
            $this->render('main/404');
            return;
        }
        $this->postService->registerView($postId, $user ? (int) $user['id'] : null);
        $data['post']['view_count'] = (int) ($data['post']['view_count'] ?? 0) + 1;
        $isFavorite = $user && $this->favoriteRepo->has((int) $user['id'], $postId);
        $seo = $this->seoService->buildDetailSeo($data['post'], $data['photos']);
        $this->render('main/detail', [
            'post' => $data['post'],
            'photos' => $data['photos'],
            'user' => $user,
            'isFavorite' => $isFavorite,
            'seo' => $seo,
            'breadcrumbs' => $seo['breadcrumbs'] ?? [],
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
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $user = $this->getLoggedUser();
        $result = $this->postService->create($_POST, $_FILES, (int) $user['id']);
        $this->jsonResult($result);
    }

    public function myPosts(): void
    {
        $this->requireAuth();
        $user = $this->getLoggedUser();
        $perPage = 50;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->postService->getByUserIdPaginated((int) $user['id'], $perPage, $page);
        $postIds = array_column($result['posts'], 'id');
        $firstPhotos = $this->postService->getFirstPhotosForPosts($postIds);
        $this->render('main/edit-advert', [
            'posts' => $result['posts'],
            'page' => $result['page'],
            'totalPages' => $result['totalPages'],
            'total' => $result['total'],
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
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $user = $this->getLoggedUser();
        $result = $this->postService->update((int) $id, $_POST, $_FILES, (int) $user['id']);
        $this->jsonResult($result);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $user = $this->getLoggedUser();
        $isAdmin = (int) ($user['is_admin'] ?? 0) === 1;
        $result = $this->postService->delete((int) $id, (int) $user['id'], $isAdmin);
        $this->jsonResult($result);
    }

    public function hardDelete(string $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $user = $this->getLoggedUser();
        $isAdmin = (int) ($user['is_admin'] ?? 0) === 1;
        $result = $this->postService->hardDelete((int) $id, (int) $user['id'], $isAdmin);
        $this->jsonResult($result);
    }

    public function restore(string $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->jsonError(static::CSRF_ERROR_MESSAGE, 403);
            return;
        }
        $user = $this->getLoggedUser();
        $isAdmin = (int) ($user['is_admin'] ?? 0) === 1;
        $result = $this->postService->restore((int) $id, (int) $user['id'], $isAdmin);
        $this->jsonResult($result);
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
