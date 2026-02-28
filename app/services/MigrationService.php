<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

class MigrationService
{
    public function __construct(private PDO $db) {}

    public function getStatus(): array
    {
        $this->ensureSchemaMigrations();
        $files = $this->getMigrationFiles();
        $applied = $this->getAppliedMap();

        $rows = [];
        foreach ($files as $name => $path) {
            $rows[] = [
                'name' => $name,
                'applied' => isset($applied[$name]),
            ];
        }

        return [
            'rows' => $rows,
            'pending_count' => count(array_filter($rows, static fn(array $r): bool => !$r['applied'])),
            'total_count' => count($rows),
        ];
    }

    public function applyNext(): array
    {
        $this->ensureSchemaMigrations();
        $this->ensureMigrationRunLog();
        $files = $this->getMigrationFiles();
        $applied = $this->getAppliedMap();

        foreach ($files as $name => $path) {
            if (!isset($applied[$name])) {
                $this->applyFile($name, $path, null);
                return ['applied' => true, 'name' => $name];
            }
        }

        return ['applied' => false, 'name' => null];
    }

    public function applyOne(string $name, ?array $actor = null): void
    {
        $this->ensureSchemaMigrations();
        $this->ensureMigrationRunLog();
        $files = $this->getMigrationFiles();
        if (!isset($files[$name])) {
            throw new RuntimeException('Миграция не найдена: ' . $name);
        }
        $applied = $this->getAppliedMap();
        if (isset($applied[$name])) {
            return;
        }
        $this->applyFile($name, $files[$name], $actor);
    }

    public function applyNextByActor(?array $actor = null): array
    {
        $this->ensureSchemaMigrations();
        $this->ensureMigrationRunLog();
        $files = $this->getMigrationFiles();
        $applied = $this->getAppliedMap();

        foreach ($files as $name => $path) {
            if (!isset($applied[$name])) {
                $this->applyFile($name, $path, $actor);
                return ['applied' => true, 'name' => $name];
            }
        }

        return ['applied' => false, 'name' => null];
    }

    public function getRecentRuns(int $limit = 20): array
    {
        $this->ensureMigrationRunLog();
        $limit = max(1, min(100, $limit));
        $stmt = $this->db->prepare('
            SELECT migration, user_id, user_email, status, message, created_at
            FROM migration_run_log
            ORDER BY id DESC
            LIMIT ' . $limit
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function applyFile(string $name, string $path, ?array $actor): void
    {
        $actorId = isset($actor['id']) ? (int) $actor['id'] : null;
        $actorEmail = isset($actor['email']) ? (string) $actor['email'] : null;
        try {
            $this->db->beginTransaction();
            if (str_ends_with($name, '.sql')) {
                $this->executeSqlMigration($path);
            } else {
                $this->executePhpMigration($path);
            }
            $mark = $this->db->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
            $mark->execute([$name]);
            $this->logRun($name, $actorId, $actorEmail, 'success', null);
            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logRun($name, $actorId, $actorEmail, 'error', $e->getMessage());
            throw new RuntimeException('Ошибка миграции ' . $name . ': ' . $e->getMessage(), 0, $e);
        }
    }

    private function ensureSchemaMigrations(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id int UNSIGNED NOT NULL AUTO_INCREMENT,
                migration varchar(190) NOT NULL,
                applied_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function ensureMigrationRunLog(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migration_run_log (
                id int UNSIGNED NOT NULL AUTO_INCREMENT,
                migration varchar(190) NOT NULL,
                user_id int NULL,
                user_email varchar(191) NULL,
                status varchar(16) NOT NULL,
                message text NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_created_at (created_at),
                KEY idx_migration (migration)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function logRun(string $migration, ?int $userId, ?string $userEmail, string $status, ?string $message): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO migration_run_log (migration, user_id, user_email, status, message)
            VALUES (:migration, :user_id, :user_email, :status, :message)
        ');
        $stmt->bindValue(':migration', $migration);
        $stmt->bindValue(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':user_email', $userEmail, $userEmail === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':message', $message, $message === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();
    }

    private function getAppliedMap(): array
    {
        $rows = $this->db->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        return array_fill_keys(array_map('strval', $rows), true);
    }

    private function getMigrationFiles(): array
    {
        $dir = dirname(__DIR__, 2) . '/migrations';
        if (!is_dir($dir)) {
            return [];
        }
        $paths = glob($dir . '/*.{sql,php}', GLOB_BRACE) ?: [];
        usort($paths, 'strnatcasecmp');
        $files = [];
        foreach ($paths as $path) {
            $name = basename($path);
            $files[$name] = $path;
        }
        return $files;
    }

    private function executePhpMigration(string $file): void
    {
        $pdo = $this->db;
        $migration = require $file;
        if (is_callable($migration)) {
            $migration($this->db);
            return;
        }
        require $file;
    }

    private function executeSqlMigration(string $file): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('Не удалось прочитать SQL-файл: ' . $file);
        }
        foreach ($this->splitSqlStatements($sql) as $stmt) {
            $query = $this->db->prepare($stmt);
            $query->execute();
            do {
                try {
                    $query->fetchAll();
                } catch (Throwable) {
                    // Для не-SELECT выражений result set отсутствует.
                }
            } while ($query->nextRowset());
            $query->closeCursor();
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $len = strlen($sql);
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($inLineComment) {
                if ($ch === "\n") {
                    $inLineComment = false;
                }
                continue;
            }
            if ($inBlockComment) {
                if ($ch === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }
            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($ch === '-' && $next === '-' && ($i + 2 >= $len || ctype_space($sql[$i + 2]))) {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($ch === '#') {
                    $inLineComment = true;
                    continue;
                }
                if ($ch === '/' && $next === '*') {
                    if ($i + 2 < $len && $sql[$i + 2] === '!') {
                        $end = strpos($sql, '*/', $i + 3);
                        if ($end === false) {
                            break;
                        }
                        $payload = substr($sql, $i + 3, $end - ($i + 3));
                        $payload = preg_replace('/^\d+\s*/', '', $payload) ?? '';
                        if ($payload !== '') {
                            $buffer .= $payload;
                        }
                        $i = $end + 1;
                        continue;
                    }
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($ch === "'" && !$inDouble && !$inBacktick) {
                $escaped = $i > 0 && $sql[$i - 1] === '\\';
                if (!$escaped) {
                    $inSingle = !$inSingle;
                }
                $buffer .= $ch;
                continue;
            }
            if ($ch === '"' && !$inSingle && !$inBacktick) {
                $escaped = $i > 0 && $sql[$i - 1] === '\\';
                if (!$escaped) {
                    $inDouble = !$inDouble;
                }
                $buffer .= $ch;
                continue;
            }
            if ($ch === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
                $buffer .= $ch;
                continue;
            }
            if ($ch === ';' && !$inSingle && !$inDouble && !$inBacktick) {
                $stmt = trim($buffer);
                if ($stmt !== '') {
                    $statements[] = $stmt;
                }
                $buffer = '';
                continue;
            }
            $buffer .= $ch;
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }
}

