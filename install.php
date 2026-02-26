<?php

/**
 * Установка БД: импорт infosee2_m2sar.sql и выполнение миграций
 * Запуск: php install.php
 */

declare(strict_types=1);

$root = __DIR__;
require $root . '/app/load_env.php';
$config = require $root . '/app/config/config.php';
$db = $config['db'];

echo "=== Установка БД ===\n";

// Подключение без БД для создания
$dsn = "mysql:host={$db['host']};charset={$db['charset']}";
$pdo = new PDO($dsn, $db['user'], $db['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$db['dbname']}`");
echo "- База {$db['dbname']} готова\n";

// Импорт начального дампа
$dumpPath = $root . '/public/infosee2_m2sar.sql';
if (!is_file($dumpPath)) {
    die("Файл дампа не найден: {$dumpPath}\n");
}

$mysqli = new mysqli($db['host'], $db['user'], $db['password'] ?? '', $db['dbname']);
if ($mysqli->connect_error) {
    die("Ошибка подключения: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset($db['charset']);

$sql = file_get_contents($dumpPath);
if ($mysqli->multi_query($sql)) {
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->next_result());
}
if ($mysqli->errno) {
    die("Ошибка импорта дампа: " . $mysqli->error . "\n");
}
$mysqli->close();
echo "- Дамп infosee2_m2sar.sql импортирован\n";

// Подключение PDO к БД
$dsnDb = "mysql:host={$db['host']};dbname={$db['dbname']};charset={$db['charset']}";
$pdo = new PDO($dsnDb, $db['user'], $db['password'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Миграции
$migrationsDir = $root . '/migrations';
if (!is_dir($migrationsDir)) {
    echo "- Папка migrations отсутствует, пропуск.\n";
} else {
    $files = glob($migrationsDir . '/*.{sql,php}', GLOB_BRACE);
    usort($files, 'strnatcasecmp');
    foreach ($files as $f) {
        $name = basename($f);
        echo "- Миграция: {$name}\n";
        if (str_ends_with($f, '.sql')) {
            $sql = file_get_contents($f);
            $sql = preg_replace('/--.*$/m', '', $sql);
            $stmts = array_filter(
                array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)),
                fn($s) => $s !== ''
            );
            foreach ($stmts as $stmt) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column') !== false
                        || strpos($e->getMessage(), 'already exists') !== false) {
                        echo "  (пропуск: уже применено)\n";
                    } else {
                        throw $e;
                    }
                }
            }
        } else {
            require $f;
        }
    }
}

echo "=== Готово ===\n";
