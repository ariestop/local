<?php

/**
 * Миграция: создаёт таблицу post_photo и расширяет колонку password.
 * Запуск: php scripts/migrate.php
 * Либо через браузер: http://localhost/test/scripts/migrate.php (если настроен доступ)
 */

declare(strict_types=1);

require dirname(__DIR__) . '/app/load_env.php';
$config = require dirname(__DIR__) . '/app/config/config.php';
$db = $config['db'];
$dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
$pdo = new PDO($dsn, $db['user'], $db['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "Миграция...\n";

$pdo->exec("CREATE TABLE IF NOT EXISTS post_photo (
    id int UNSIGNED NOT NULL AUTO_INCREMENT,
    post_id int UNSIGNED NOT NULL,
    filename varchar(100) NOT NULL,
    sort_order int NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY post_id (post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "- Таблица post_photo создана или уже существует\n";

try {
    $pdo->exec("ALTER TABLE user MODIFY COLUMN password varchar(255) NOT NULL");
    echo "- Колонка user.password расширена до varchar(255)\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "- Колонка password уже обновлена\n";
    } else {
        echo "Предупреждение: " . $e->getMessage() . "\n";
    }
}

echo "Готово.\n";
