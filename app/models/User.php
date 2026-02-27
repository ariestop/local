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
        $stmt = $this->db->prepare("SELECT id, email, name, COALESCE(is_admin, 0) AS is_admin FROM user WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function login(string $email, string $password, bool $allowLegacy = true): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        $stored = (string) ($user['password'] ?? '');
        $ok = false;
        $isModernHash = password_get_info($stored)['algo'] !== null;

        if ($isModernHash) {
            $ok = password_verify($password, $stored);
            if ($ok && password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                $this->updatePasswordHash((int) $user['id'], $password);
            }
        } elseif ($allowLegacy) {
            $ok = hash_equals($stored, $password) || hash_equals($stored, md5($password));
            if ($ok) {
                $this->updatePasswordHash((int) $user['id'], $password);
            }
        }

        if (!$ok) {
            return null;
        }
        if (isset($user['email_verified']) && !$user['email_verified']) {
            return null; // Требуется подтверждение email
        }
        return [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'is_admin' => (int) ($user['is_admin'] ?? 0),
        ];
    }

    public function register(array $data, ?string $confirmToken = null): int
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $ip = ip2long($_SERVER['REMOTE_ADDR'] ?? '0') ?: 0;
        $cols = array_column($this->db->query("SHOW COLUMNS FROM user")->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $hasConfirm = in_array('confirm_token', $cols, true);
        if ($hasConfirm && $confirmToken) {
            $verified = 0;
            $expires = date('Y-m-d H:i:s', time() + 86400);
            $stmt = $this->db->prepare("INSERT INTO user (email, password, name, registration_date, user_ip, email_verified, confirm_token, confirm_expires) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
            $stmt->execute([$data['email'], $hash, $data['name'], $ip, $verified, $confirmToken, $expires]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO user (email, password, name, registration_date, user_ip) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->execute([$data['email'], $hash, $data['name'], $ip]);
        }
        return (int) $this->db->lastInsertId();
    }

    public function findByConfirmToken(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE confirm_token = ? AND confirm_expires > NOW()");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function verifyEmail(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE user SET email_verified = 1, confirm_token = NULL, confirm_expires = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    }

    public function setPasswordResetToken(string $email, string $token): bool
    {
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $stmt = $this->db->prepare("UPDATE user SET password_reset_token = ?, password_reset_expires = ? WHERE email = ?");
        return $stmt->execute([$token, $expires, $email]);
    }

    public function findByPasswordResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM user WHERE password_reset_token = ? AND password_reset_expires > NOW()");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updatePassword(int $userId, string $password): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE user SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $userId]);
    }

    private function updatePasswordHash(int $userId, string $password): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE user SET password = ? WHERE id = ?");
        $stmt->execute([$hash, $userId]);
    }
}
