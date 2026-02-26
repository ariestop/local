<?php

declare(strict_types=1);

namespace App\Services;

class MailService
{
    public function __construct(
        private string $appUrl,
        private string $fromEmail = 'noreply@localhost'
    ) {}

    public function send(string $to, string $subject, string $bodyHtml): bool
    {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $this->fromEmail,
        ];
        return @mail($to, $subject, $bodyHtml, implode("\r\n", $headers));
    }

    public function sendConfirmEmail(string $email, string $name, string $token): bool
    {
        $url = rtrim($this->appUrl, '/') . '/verify-email?token=' . urlencode($token);
        $subject = 'Подтверждение регистрации - ' . ($_ENV['APP_NAME'] ?? 'Доска объявлений');
        $body = "<h2>Здравствуйте, " . htmlspecialchars($name) . "!</h2>" .
            "<p>Подтвердите регистрацию, перейдя по ссылке:</p>" .
            "<p><a href=\"{$url}\">{$url}</a></p>" .
            "<p>Ссылка действительна 24 часа.</p>";
        return $this->send($email, $subject, $body);
    }

    public function sendPasswordReset(string $email, string $name, string $token): bool
    {
        $url = rtrim($this->appUrl, '/') . '/reset-password?token=' . urlencode($token);
        $subject = 'Восстановление пароля - ' . ($_ENV['APP_NAME'] ?? 'Доска объявлений');
        $body = "<h2>Здравствуйте, " . htmlspecialchars($name) . "!</h2>" .
            "<p>Перейдите по ссылке для смены пароля:</p>" .
            "<p><a href=\"{$url}\">{$url}</a></p>" .
            "<p>Ссылка действительна 1 час.</p>";
        return $this->send($email, $subject, $body);
    }
}
