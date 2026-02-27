<?php

declare(strict_types=1);

/**
 * Установка/обновление БД.
 *
 * Принципы:
 * - baseline-дамп импортируется только один раз (one-time bootstrap)
 * - последующие запуски применяют только новые миграции
 * - единый драйвер БД: PDO
 *
 * Запуск:
 *   php install.php
 *   php install.php --status
 *   php install.php --dry-run
 *   php install.php --force-bootstrap
 */

const EXIT_OK = 0;
const EXIT_ERROR = 1;
const EXIT_UNSAFE_DB = 2;
const EXIT_BAD_USAGE = 3;

function out(string $level, string $message): void
{
    echo '[' . $level . '] ' . $message . PHP_EOL;
}

function usage(): void
{
    echo <<<TXT
Usage:
  php install.php [--status] [--dry-run] [--force-bootstrap]

Flags:
  --status            Show DB/bootstrap/migration status only.
  --dry-run           Print planned actions without changing DB.
  --force-bootstrap   Allow baseline import even if marker is missing on non-empty DB.
  --help              Show this help.

TXT;
}

function normalizePath(string $root, string $path): string
{
    $path = trim($path);
    if ($path === '') {
        throw new RuntimeException('DB_DUMP_PATH is empty. Set DB_DUMP_PATH in .env (e.g. public/infosee2_m2sar.sql).');
    }
    if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
        return $path;
    }
    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    return $root . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
}

function resolveDumpPath(array $dbConfig, string $root): string
{
    if (!array_key_exists('dump_path', $dbConfig)) {
        throw new RuntimeException('DB_DUMP_PATH is not configured. Add DB_DUMP_PATH to .env/.env.example and app/config/config.php.');
    }
    $configured = (string) $dbConfig['dump_path'];
    return normalizePath($root, $configured);
}

function preflightDumpPath(string $dumpPath): void
{
    out('INFO', "Configured DB dump path: {$dumpPath}");
    if (!is_file($dumpPath)) {
        throw new RuntimeException("DB dump file not found: {$dumpPath}. Check DB_DUMP_PATH in .env.");
    }
    out('INFO', 'DB dump file found.');
}

function connectServer(array $db): PDO
{
    $dsn = sprintf('mysql:host=%s;charset=%s', $db['host'], $db['charset']);
    return new PDO($dsn, $db['user'], $db['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function connectDatabase(array $db): PDO
{
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $db['host'], $db['dbname'], $db['charset']);
    return new PDO($dsn, $db['user'], $db['password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function databaseExists(PDO $serverPdo, string $dbName): bool
{
    $stmt = $serverPdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $stmt->execute([$dbName]);
    return (int) $stmt->fetchColumn() > 0;
}

function createDatabaseIfMissing(PDO $serverPdo, string $dbName, bool $dryRun): bool
{
    if (databaseExists($serverPdo, $dbName)) {
        out('INFO', "Database `{$dbName}` already exists.");
        return false;
    }
    if ($dryRun) {
        out('PLAN', "Would create database `{$dbName}`.");
        return true;
    }
    $serverPdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    out('INFO', "Database `{$dbName}` created.");
    return true;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
    ');
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensureSchemaMigrations(PDO $pdo, bool $dryRun): void
{
    if (tableExists($pdo, 'schema_migrations')) {
        return;
    }
    $sql = "
        CREATE TABLE schema_migrations (
            id int UNSIGNED NOT NULL AUTO_INCREMENT,
            migration varchar(190) NOT NULL,
            applied_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_migration (migration)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if ($dryRun) {
        out('PLAN', 'Would create table `schema_migrations`.');
        return;
    }
    $pdo->exec($sql);
}

function ensureInstallState(PDO $pdo, bool $dryRun): void
{
    $create = "
        CREATE TABLE IF NOT EXISTS app_install_state (
            id tinyint UNSIGNED NOT NULL,
            baseline_imported_at datetime DEFAULT NULL,
            baseline_source varchar(255) DEFAULT NULL,
            app_version varchar(64) DEFAULT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if ($dryRun) {
        out('PLAN', 'Would ensure table `app_install_state` and seed row.');
        return;
    }
    $pdo->exec($create);
    $pdo->exec('INSERT IGNORE INTO app_install_state (id) VALUES (1)');
}

function getInstallState(PDO $pdo): array
{
    if (!tableExists($pdo, 'app_install_state')) {
        return ['exists' => false, 'baseline_imported_at' => null];
    }
    $stmt = $pdo->query('SELECT baseline_imported_at, baseline_source, app_version FROM app_install_state WHERE id = 1');
    $row = $stmt->fetch();
    return [
        'exists' => true,
        'baseline_imported_at' => $row['baseline_imported_at'] ?? null,
        'baseline_source' => $row['baseline_source'] ?? null,
        'app_version' => $row['app_version'] ?? null,
    ];
}

function countDomainTables(PDO $pdo): int
{
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME NOT IN ('schema_migrations', 'app_install_state')
    ");
    return (int) $stmt->fetchColumn();
}

function looksLikeLegacyInstalledDatabase(PDO $pdo): bool
{
    $critical = ['user', 'post', 'action', 'objectsale', 'city', 'area'];
    foreach ($critical as $table) {
        if (!tableExists($pdo, $table)) {
            return false;
        }
    }
    return true;
}

function splitSqlStatements(string $sql): array
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

function executeSqlFile(PDO $pdo, string $file): void
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Failed to read SQL migration: {$file}");
    }
    $statements = splitSqlStatements($sql);
    foreach ($statements as $stmt) {
        $pdo->exec($stmt);
    }
}

function executePhpMigration(PDO $pdo, string $file): void
{
    $migration = require $file;
    if (is_callable($migration)) {
        $migration($pdo);
        return;
    }

    // Legacy compatibility: old migrations rely on $pdo in local scope.
    require $file;
}

function importBaselineDump(PDO $pdo, string $dumpPath, bool $dryRun): void
{
    if (!is_file($dumpPath)) {
        throw new RuntimeException("Baseline dump file not found: {$dumpPath}");
    }
    if ($dryRun) {
        out('PLAN', "Would import baseline dump `{$dumpPath}`.");
        return;
    }
    executeSqlFile($pdo, $dumpPath);
    out('INFO', 'Baseline dump imported.');
}

function markBaselineImported(PDO $pdo, string $dumpPath, bool $dryRun): void
{
    if ($dryRun) {
        out('PLAN', 'Would mark baseline as imported in `app_install_state`.');
        return;
    }
    $stmt = $pdo->prepare('
        UPDATE app_install_state
        SET baseline_imported_at = NOW(), baseline_source = ?, app_version = ?
        WHERE id = 1
    ');
    $stmt->execute([basename($dumpPath), 'install.php']);
}

function markBaselineAdopted(PDO $pdo, bool $dryRun): void
{
    if ($dryRun) {
        out('PLAN', 'Would backfill bootstrap marker for existing database.');
        return;
    }
    $stmt = $pdo->prepare('
        UPDATE app_install_state
        SET baseline_imported_at = COALESCE(baseline_imported_at, NOW()),
            baseline_source = COALESCE(baseline_source, ?),
            app_version = COALESCE(app_version, ?)
        WHERE id = 1
    ');
    $stmt->execute(['legacy-existing-db', 'install.php']);
}

function handleBootstrapPhase(
    PDO $pdo,
    string $dumpPath,
    int $domainTableCount,
    bool $baselineImported,
    bool $statusOnly,
    bool $forceBootstrap,
    bool $dryRun
): int {
    if ($baselineImported || $statusOnly) {
        if (!$statusOnly) {
            out('INFO', 'Bootstrap already completed. Baseline import skipped.');
        }
        return EXIT_OK;
    }

    if ($domainTableCount === 0) {
        out('INFO', 'Detected clean DB. Running one-time baseline bootstrap...');
        importBaselineDump($pdo, $dumpPath, $dryRun);
        markBaselineImported($pdo, $dumpPath, $dryRun);
        return EXIT_OK;
    }

    if ($forceBootstrap) {
        out('WARN', 'Force bootstrap requested on non-empty DB.');
        importBaselineDump($pdo, $dumpPath, $dryRun);
        markBaselineImported($pdo, $dumpPath, $dryRun);
        return EXIT_OK;
    }

    if (looksLikeLegacyInstalledDatabase($pdo)) {
        out('INFO', 'Detected existing legacy DB. Backfilling bootstrap marker, baseline import skipped.');
        markBaselineAdopted($pdo, $dryRun);
        return EXIT_OK;
    }

    out('ERROR', 'Non-empty DB without bootstrap marker detected. Refusing baseline import to avoid conflicts.');
    out('ERROR', 'If you are sure, re-run with --force-bootstrap. Otherwise backup DB and investigate state.');
    return EXIT_UNSAFE_DB;
}

function printStatus(string $dbName, array $state, bool $baselineImported, int $domainTableCount): void
{
    out('INFO', "Database: {$dbName}");
    out('INFO', 'Bootstrap marker table: ' . ($state['exists'] ? 'present' : 'missing'));
    out('INFO', 'Baseline imported: ' . ($baselineImported ? 'yes (' . $state['baseline_imported_at'] . ')' : 'no'));
    out('INFO', "Domain tables count: {$domainTableCount}");
}

function printMigrationSummary(array $migrationStats): void
{
    out('INFO', sprintf(
        'Migrations summary: applied=%d, skipped=%d, pending=%d',
        (int) ($migrationStats['applied'] ?? 0),
        (int) ($migrationStats['skipped'] ?? 0),
        (int) ($migrationStats['pending'] ?? 0)
    ));
}

function applyMigrations(PDO $pdo, string $migrationsDir, bool $dryRun, bool $statusOnly): array
{
    if (!is_dir($migrationsDir)) {
        out('WARN', 'Migrations directory not found, skipping.');
        return ['applied' => 0, 'skipped' => 0, 'pending' => 0];
    }

    ensureSchemaMigrations($pdo, $dryRun);
    if ($dryRun && !tableExists($pdo, 'schema_migrations')) {
        out('PLAN', 'Pending migrations cannot be resolved precisely in dry-run without DB metadata.');
        return ['applied' => 0, 'skipped' => 0, 'pending' => 0];
    }

    $appliedRows = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    $appliedMap = array_fill_keys(array_map('strval', $appliedRows), true);

    $files = glob($migrationsDir . '/*.{sql,php}', GLOB_BRACE) ?: [];
    usort($files, 'strnatcasecmp');

    $result = ['applied' => 0, 'skipped' => 0, 'pending' => 0];

    foreach ($files as $file) {
        $name = basename($file);
        if (isset($appliedMap[$name])) {
            $result['skipped']++;
            continue;
        }
        $result['pending']++;
        if ($statusOnly) {
            out('INFO', "Pending migration: {$name}");
            continue;
        }
        if ($dryRun) {
            out('PLAN', "Would apply migration: {$name}");
            continue;
        }

        out('INFO', "Applying migration: {$name}");
        try {
            $pdo->beginTransaction();
            if (str_ends_with($name, '.sql')) {
                executeSqlFile($pdo, $file);
            } else {
                executePhpMigration($pdo, $file);
            }
            $mark = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
            $mark->execute([$name]);
            $pdo->commit();
            $result['applied']++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new RuntimeException("Migration failed [{$name}]: " . $e->getMessage(), 0, $e);
        }
    }

    return $result;
}

function parseOptions(array $argv): array
{
    $options = [
        'help' => false,
        'status' => false,
        'dry-run' => false,
        'force-bootstrap' => false,
    ];
    foreach (array_slice($argv, 1) as $arg) {
        $key = ltrim($arg, '-');
        if (!array_key_exists($key, $options)) {
            throw new InvalidArgumentException("Unknown option: {$arg}");
        }
        $options[$key] = true;
    }
    return $options;
}

function loadAppConfig(string $root): array
{
    require $root . '/app/load_env.php';
    /** @var array $config */
    $config = require $root . '/app/config/config.php';
    return $config;
}

function runInstaller(array $opts, string $root): int
{
    $config = loadAppConfig($root);
    $db = $config['db'];
    $dumpPath = resolveDumpPath($db, $root);
    $migrationsDir = $root . '/migrations';
    $dryRun = $opts['dry-run'];
    $statusOnly = $opts['status'];
    $forceBootstrap = $opts['force-bootstrap'];

    out('INFO', '=== DB install/update start ===');
    if ($dryRun) {
        out('INFO', 'Dry-run mode enabled. No DB changes will be made.');
    }

    try {
        preflightDumpPath($dumpPath);

        $serverPdo = connectServer($db);
        createDatabaseIfMissing($serverPdo, $db['dbname'], $dryRun);

        if (!databaseExists($serverPdo, $db['dbname'])) {
            throw new RuntimeException('Database does not exist and cannot be created in current mode.');
        }

        $pdo = connectDatabase($db);
        $readonlyMode = $dryRun || $statusOnly;
        ensureInstallState($pdo, $readonlyMode);
        ensureSchemaMigrations($pdo, $readonlyMode);

        $state = getInstallState($pdo);
        $domainTableCount = countDomainTables($pdo);
        $baselineImported = !empty($state['baseline_imported_at']);

        if ($statusOnly) {
            printStatus($db['dbname'], $state, $baselineImported, $domainTableCount);
        }

        $bootstrapStatus = handleBootstrapPhase(
            $pdo,
            $dumpPath,
            $domainTableCount,
            $baselineImported,
            $statusOnly,
            $forceBootstrap,
            $dryRun
        );
        if ($bootstrapStatus !== EXIT_OK) {
            return $bootstrapStatus;
        }

        $migrationStats = applyMigrations($pdo, $migrationsDir, $readonlyMode, $statusOnly);
        printMigrationSummary($migrationStats);

        out('INFO', '=== DB install/update complete ===');
        return EXIT_OK;
    } catch (PDOException $e) {
        out('ERROR', 'PDO error: ' . $e->getMessage());
        return EXIT_ERROR;
    } catch (Throwable $e) {
        out('ERROR', $e->getMessage());
        return EXIT_ERROR;
    }
}

if (PHP_SAPI !== 'cli') {
    out('ERROR', 'This script must be run from CLI.');
    exit(EXIT_BAD_USAGE);
}

try {
    $opts = parseOptions($argv);
} catch (Throwable $e) {
    out('ERROR', $e->getMessage());
    usage();
    exit(EXIT_BAD_USAGE);
}

if ($opts['help']) {
    usage();
    exit(EXIT_OK);
}

$root = __DIR__;
exit(runInstaller($opts, $root));
