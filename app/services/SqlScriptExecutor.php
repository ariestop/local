<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use RuntimeException;
use Throwable;

final class SqlScriptExecutor
{
    /**
     * @return array<int, string>
     */
    public function splitStatements(string $sql): array
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

    public function executeSql(PDO $pdo, string $sql): void
    {
        foreach ($this->splitStatements($sql) as $stmt) {
            $this->executeStatement($pdo, $stmt);
        }
    }

    public function executeFile(PDO $pdo, string $file): void
    {
        $sql = file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('Не удалось прочитать SQL-файл: ' . $file);
        }
        $this->executeSql($pdo, $sql);
    }

    public function executeStatement(PDO $pdo, string $stmt): void
    {
        $query = $pdo->prepare($stmt);
        $query->execute();
        do {
            try {
                $query->fetchAll();
            } catch (Throwable) {
                // No result set for this statement/rowset.
            }
        } while ($query->nextRowset());
        $query->closeCursor();
    }
}
