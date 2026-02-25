<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Controller
{
    protected PDO $db;
    protected array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__) . '/config/config.php';
        $this->db = Database::getConnection($this->config['db']);
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }

    protected function requireAuth(): void
    {
        if ($this->getLoggedUser() === null) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Требуется авторизация'], 401);
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
}
