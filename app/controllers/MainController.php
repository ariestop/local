<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Reference;

class MainController extends Controller
{
    public function index(): void
    {
        $postModel = new Post($this->db);
        $posts = $postModel->getList(50, 0);
        $user = $this->getLoggedUser();
        $this->render('main/index', [
            'posts' => $posts,
            'user' => $user,
        ]);
    }

    public function detail(string $id): void
    {
        $postModel = new Post($this->db);
        $post = $postModel->getById((int) $id);
        if (!$post) {
            http_response_code(404);
            $this->render('main/404');
            return;
        }
        $user = $this->getLoggedUser();
        $this->render('main/detail', [
            'post' => $post,
            'user' => $user,
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
        $user = $this->getLoggedUser();
        $required = ['action_id', 'object_id', 'city_id', 'area_id', 'street', 'phone', 'cost', 'descr_post'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $this->json(['success' => false, 'error' => 'Заполните все обязательные поля'], 400);
                return;
            }
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
        $this->json(['success' => true, 'id' => $id]);
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
