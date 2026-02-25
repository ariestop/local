<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\PostPhoto;
use App\Models\Reference;
use App\Services\ImageService;
use App\Validation;

class MainController extends Controller
{
    public function index(): void
    {
        $perPage = 20;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $perPage;
        $postModel = new Post($this->db);
        $total = $postModel->count();
        $posts = $postModel->getList($perPage, $offset);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $user = $this->getLoggedUser();
        $this->render('main/index', [
            'posts' => $posts,
            'user' => $user,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    public function detail(string $id): void
    {
        $postModel = new Post($this->db);
        $photoModel = new PostPhoto($this->db);
        $post = $postModel->getById((int) $id);
        if (!$post) {
            http_response_code(404);
            $this->render('main/404');
            return;
        }
        $photos = $photoModel->getByPostId((int) $id);
        $this->render('main/detail', [
            'post' => $post,
            'photos' => $photos,
            'user' => $this->getLoggedUser(),
        ]);
    }

    public function add(): void
    {
        $this->requireAuth();
        $ref = new Reference($this->db);
        $areas = $ref->getAreas();
        $areasByCity = [];
        foreach ($areas as $a) {
            $cid = (int) $a['city_id'];
            if (!isset($areasByCity[$cid])) {
                $areasByCity[$cid] = [];
            }
            $areasByCity[$cid][] = $a;
        }
        $this->render('main/add', [
            'actions' => $ref->getActions(),
            'objects' => $ref->getObjects(),
            'cities' => $ref->getCities(),
            'areasByCity' => $areasByCity,
        ]);
    }

    public function addSubmit(): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], 403);
            return;
        }
        $user = $this->getLoggedUser();
        $v = new Validation();
        $v->required($_POST, ['action_id', 'object_id', 'city_id', 'area_id', 'street', 'phone', 'cost', 'descr_post']);
        if (!$v->isValid()) {
            $this->json(['success' => false, 'error' => $v->firstError() ?? 'Заполните все обязательные поля'], 400);
            return;
        }
        $postModel = new Post($this->db);
        $id = $postModel->create([
            'user_id' => $user['id'],
            'action_id' => (int) $_POST['action_id'],
            'object_id' => (int) $_POST['object_id'],
            'city_id' => (int) $_POST['city_id'],
            'area_id' => (int) $_POST['area_id'],
            'room' => $_POST['room'] ?? 0,
            'm2' => $_POST['m2'] ?? 0,
            'street' => trim($_POST['street']),
            'phone' => trim($_POST['phone']),
            'cost' => (int) preg_replace('/\D/', '', $_POST['cost']),
            'title' => trim($_POST['title'] ?? 'Объявление'),
            'descr_post' => trim($_POST['descr_post']),
            'new_house' => !empty($_POST['new_house']),
        ]);
        if (!empty($_FILES['photos']['name'][0])) {
            try {
                $imgService = new ImageService(dirname(__DIR__, 2) . '/public/images');
                $uploaded = $imgService->upload((int) $user['id'], $id, $_FILES['photos']);
                if (!empty($uploaded)) {
                    $photoModel = new PostPhoto($this->db);
                    $photoModel->addBatch($id, $uploaded);
                }
            } catch (\Throwable $e) {
                // Объявление создано, фото не загрузились
            }
        }
        $this->json(['success' => true, 'id' => $id]);
    }

    public function myPosts(): void
    {
        $this->requireAuth();
        $user = $this->getLoggedUser();
        $postModel = new Post($this->db);
        $photoModel = new PostPhoto($this->db);
        $posts = $postModel->getByUserId((int) $user['id']);
        $postIds = array_column($posts, 'id');
        $firstPhotos = $postIds ? $photoModel->getFirstByPostIds($postIds) : [];
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
        $postId = (int) $id;
        $postModel = new Post($this->db);
        $photoModel = new PostPhoto($this->db);
        $post = $postModel->getById($postId);
        if (!$post || (int) $post['user_id'] !== (int) $user['id']) {
            http_response_code(404);
            $this->render('main/404');
            return;
        }
        $ref = new Reference($this->db);
        $areas = $ref->getAreas();
        $areasByCity = [];
        foreach ($areas as $a) {
            $cid = (int) $a['city_id'];
            if (!isset($areasByCity[$cid])) {
                $areasByCity[$cid] = [];
            }
            $areasByCity[$cid][] = $a;
        }
        $photos = $photoModel->getByPostId($postId);
        $this->render('main/edit', [
            'post' => $post,
            'photos' => $photos,
            'actions' => $ref->getActions(),
            'objects' => $ref->getObjects(),
            'cities' => $ref->getCities(),
            'areasByCity' => $areasByCity,
        ]);
    }

    public function editSubmit(string $id): void
    {
        $this->requireAuth();
        if (!$this->validateCsrf()) {
            $this->json(['success' => false, 'error' => 'Ошибка безопасности. Обновите страницу.'], 403);
            return;
        }
        $user = $this->getLoggedUser();
        $postId = (int) $id;
        $postModel = new Post($this->db);
        $photoModel = new PostPhoto($this->db);
        $post = $postModel->getById($postId);
        if (!$post || (int) $post['user_id'] !== (int) $user['id']) {
            $this->json(['success' => false, 'error' => 'Объявление не найдено'], 404);
            return;
        }
        $v = new Validation();
        $v->required($_POST, ['action_id', 'object_id', 'city_id', 'area_id', 'street', 'phone', 'cost', 'descr_post']);
        if (!$v->isValid()) {
            $this->json(['success' => false, 'error' => $v->firstError() ?? 'Заполните все обязательные поля'], 400);
            return;
        }
        $postModel->update($postId, (int) $user['id'], [
            'action_id' => (int) $_POST['action_id'],
            'object_id' => (int) $_POST['object_id'],
            'city_id' => (int) $_POST['city_id'],
            'area_id' => (int) $_POST['area_id'],
            'room' => $_POST['room'] ?? 0,
            'm2' => $_POST['m2'] ?? 0,
            'street' => trim($_POST['street']),
            'phone' => trim($_POST['phone']),
            'cost' => (int) preg_replace('/\D/', '', $_POST['cost']),
            'title' => trim($_POST['title'] ?? 'Объявление'),
            'descr_post' => trim($_POST['descr_post']),
            'new_house' => !empty($_POST['new_house']),
        ]);
        $currentCount = $photoModel->countByPostId($postId);
        $deletePhotos = $_POST['delete_photos'] ?? [];
        if (is_string($deletePhotos)) {
            $deletePhotos = $deletePhotos ? explode(',', $deletePhotos) : [];
        }
        $imgService = new ImageService(dirname(__DIR__, 2) . '/public/images');
        $uid = (int) $user['id'];
        foreach ($deletePhotos as $fn) {
            $fn = trim(basename($fn));
            if ($fn) {
                $photoModel->deleteByFilename($postId, $fn);
                $imgService->deletePhoto($uid, $postId, $fn);
            }
        }
        $remainingSlots = 5 - ($currentCount - count($deletePhotos));
        if (!empty($_FILES['photos']['name'][0]) && $remainingSlots > 0) {
            try {
                $uploaded = $imgService->upload($uid, $postId, $_FILES['photos'], $remainingSlots);
                if (!empty($uploaded)) {
                    $stmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM post_photo WHERE post_id = ?");
                    $stmt->execute([$postId]);
                    $maxSort = (int) $stmt->fetchColumn();
                    foreach ($uploaded as $i => $p) {
                        $photoModel->add($postId, $p['filename'], $maxSort + 1 + $i);
                    }
                }
            } catch (\Throwable $e) {
                // Фото не загрузились
            }
        }
        $this->json(['success' => true, 'id' => $postId]);
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
        $postId = (int) $id;
        $postModel = new Post($this->db);
        $photoModel = new PostPhoto($this->db);
        $post = $postModel->getById($postId);
        if (!$post || (int) $post['user_id'] !== (int) $user['id']) {
            $this->json(['success' => false, 'error' => 'Объявление не найдено'], 404);
            return;
        }
        $uid = (int) $user['id'];
        $photoModel->deleteByPostId($postId);
        $imgService = new ImageService(dirname(__DIR__, 2) . '/public/images');
        $imgService->deletePostFolder($uid, $postId);
        $postModel->delete($postId, $uid);
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
