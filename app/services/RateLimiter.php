<?php

declare(strict_types=1);

namespace App\Services;

class RateLimiter
{
    /**
     * @return array{allowed: bool, retry_after: int}
     */
    public function hit(string $key, int $maxAttempts, int $windowSeconds): array
    {
        ensure_session();
        $now = time();
        $bucket = $_SESSION['rate_limits'][$key] ?? ['count' => 0, 'reset_at' => $now + $windowSeconds];

        if (($bucket['reset_at'] ?? 0) <= $now) {
            $bucket = ['count' => 0, 'reset_at' => $now + $windowSeconds];
        }

        $bucket['count'] = (int) ($bucket['count'] ?? 0) + 1;
        $_SESSION['rate_limits'][$key] = $bucket;

        if ($bucket['count'] > $maxAttempts) {
            return [
                'allowed' => false,
                'retry_after' => max(1, (int) $bucket['reset_at'] - $now),
            ];
        }

        return ['allowed' => true, 'retry_after' => 0];
    }
}