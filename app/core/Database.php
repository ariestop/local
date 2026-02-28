<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    private static function mysqlBufferedQueryAttr(): ?int
    {
        if (defined('Pdo\\Mysql::ATTR_USE_BUFFERED_QUERY')) {
            return (int) constant('Pdo\\Mysql::ATTR_USE_BUFFERED_QUERY');
        }
        if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
            return (int) constant('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY');
        }
        return null;
    }

    public static function getConnection(array $config): PDO
    {
        if (self::$connection === null) {
            try {
                $port = (int) ($config['port'] ?? 0);
                $portPart = $port > 0 ? ';port=' . $port : '';
                $dsn = sprintf(
                    'mysql:host=%s%s;dbname=%s;charset=%s',
                    $config['host'],
                    $portPart,
                    $config['dbname'],
                    $config['charset']
                );
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $bufferedQueryAttr = self::mysqlBufferedQueryAttr();
                if ($bufferedQueryAttr !== null) {
                    $options[$bufferedQueryAttr] = true;
                }
                self::$connection = new PDO($dsn, $config['user'], $config['password'], $options);
            } catch (\PDOException $e) {
                if (php_sapi_name() !== 'cli') {
                    http_response_code(500);
                    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Ошибка</title></head><body>';
                    echo '<h1>Ошибка подключения к БД</h1>';
                    echo '<p>Выполните в терминале из корня проекта: <code>php install.php</code>.</p>';
                    echo '<p>Первый запуск делает bootstrap (дамп + миграции), повторные запуски применяют только новые миграции.</p>';
                    echo '<p>Проверьте настройки в app/config/config.php</p>';
                    echo '</body></html>';
                    exit;
                }
                throw $e;
            }
        }
        return self::$connection;
    }
}
