<?php

declare(strict_types=1);

namespace App\Services;

use App\Log\LoggerInterface;
use PDO;

class AppErrorService
{
    public function __construct(
        private PDO $db,
        private ?LoggerInterface $logger = null
    ) {}

    public function reportClientError(array $payload, ?int $userId): void
    {
        $message = trim((string) ($payload['message'] ?? 'Unknown client error'));
        $level = trim((string) ($payload['level'] ?? 'error'));
        $url = trim((string) ($payload['url'] ?? ''));
        $context = $payload['context'] ?? null;
        $userAgent = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $ipHash = hash('sha256', $ip);
        $contextJson = null;
        if (is_array($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($context) && $context !== '') {
            $contextJson = json_encode(['raw' => substr($context, 0, 500)], JSON_UNESCAPED_UNICODE);
        }

        $stmt = $this->db->prepare("
            INSERT INTO app_error_event (level, message, context_json, url, user_id, ip_hash, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            substr($level, 0, 20),
            substr($message, 0, 500),
            $contextJson,
            substr($url, 0, 255),
            $userId,
            $ipHash,
            substr($userAgent, 0, 255),
        ]);

        $this->logger?->warning('Client error reported', [
            'level' => $level,
            'message' => $message,
            'url' => $url,
            'user_id' => $userId,
        ]);
    }
}
