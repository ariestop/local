<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class User
{
    public function __construct(
        private PDO $db
    ) {}

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, email, name FROM user WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function login(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }
        // Поддержка старого plain-text и md5 из дампа
        $stored = $user['password'];
        $ok = ($password === $stored) || (md5($password) === $stored) || password_verify($password, $stored);
        if (!$ok) {
            return null;
        }
        return [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
        ];
    }

    public function register(array $data): int
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $ip = ip2long($_SERVER['REMOTE_ADDR'] ?? '0') ?: 0;
        $stmt = $this->db->prepare("INSERT INTO user (email, password, name, registration_date, user_ip) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$data['email'], $hash, $data['name'], $ip]);
        return (int) $this->db->lastInsertId();
    }
}
