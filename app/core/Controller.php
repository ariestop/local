<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Controller
{
    protected PDO $db;
    protected array $config;

    public function __construct(?Container $container = null)
    {
        if ($container !== null) {
            $this->config = $container->getConfig();
            $this->db = $container->get(PDO::class);
        } else {
            $this->config = require dirname(__DIR__) . '/config/config.php';
            $this->db = Database::getConnection($this->config['db']);
        }
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function redirect(string $url, int $code = 302): void
    {
        header('Location: ' . $url, true, $code);
        exit;
    }

    protected function getLoggedUser(): ?array
    {
        ensure_session();
        return $_SESSION['user'] ?? null;
    }

    protected function requireAuth(): void
    {
        if ($this->getLoggedUser() === null) {
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'error' => 'Требуется авторизация',
                    'code' => 401,
                ], 401);
            } else {
                $this->redirect('/');
            }
            exit;
        }
    }

    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected const CSRF_ERROR_MESSAGE = 'Ошибка безопасности. Обновите страницу.';

    protected function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return $token !== '' && hash_equals(csrf_token(), $token);
    }

    protected function jsonError(string $message, int $code = 400): void
    {
        $this->json([
            'success' => false,
            'error' => $message,
            'code' => $code,
        ], $code);
    }

    protected function jsonResult(array $result, int $successCode = 200): void
    {
        if (!empty($result['success'])) {
            $this->json($result, $successCode);
        } else {
            $code = (int) ($result['code'] ?? 400);
            $this->json([
                'success' => false,
                'error' => $result['error'] ?? 'Ошибка',
                'code' => $code,
            ], $code);
        }
    }

    protected function render(string $view, array $data = []): void
    {
        $data['view'] = $view;
        $data['config'] = $this->config;
        $data['user'] = $data['user'] ?? $this->getLoggedUser();
        $data['seo'] = $data['seo'] ?? [
            'robots' => 'noindex,nofollow',
        ];
        extract($data);
        require dirname(__DIR__) . '/views/layout.php';
    }
}
