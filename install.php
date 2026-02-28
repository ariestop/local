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

function currentCliUser(): string
{
    $user = (string) ($_SERVER['USERNAME'] ?? $_ENV['USERNAME'] ?? getenv('USERNAME') ?: '');
    if ($user !== '') {
        return trim($user);
    }
    $user = (string) ($_SERVER['USER'] ?? $_ENV['USER'] ?? getenv('USER') ?: '');
    return trim($user);
}

function resolveAllowedCliUsers(): array
{
    $raw = (string) ($_ENV['INSTALL_ALLOWED_USERS'] ?? getenv('INSTALL_ALLOWED_USERS') ?: 'Administrator');
    $parts = preg_split('/[,\s;]+/', $raw) ?: [];
    $users = [];
    foreach ($parts as $part) {
        $name = trim($part);
        if ($name !== '') {
            $users[] = $name;
        }
    }
    $users = array_values(array_unique($users));
    if ($users === []) {
        throw new RuntimeException('INSTALL_ALLOWED_USERS is empty. Set at least one allowed CLI user.');
    }
    return $users;
}

function ensureCliAdminAllowed(): void
{
    $currentUser = currentCliUser();
    if ($currentUser === '') {
        throw new RuntimeException('Cannot determine current CLI user (USERNAME/USER is empty).');
    }
    $allowedUsers = resolveAllowedCliUsers();
    foreach ($allowedUsers as $allowed) {
        if (strcasecmp($allowed, $currentUser) === 0) {
            out('INFO', "CLI access granted for user `{$currentUser}`.");
            return;
        }
    }
    throw new RuntimeException(
        "Access denied for CLI user `{$currentUser}`. " .
        'Only admin users from INSTALL_ALLOWED_USERS can run install.php.'
    );
}

function normalizePath(string $root, string $path): string
{
    $path = trim($path);
    if ($path === '') {
        throw new RuntimeException('DB_DUMP_PATH is empty. Set DB_DUMP_PATH in .env (e.g. public_html/m2saratov_28.02.sql).');
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

function installSqlExecutor(): App\Services\SqlScriptExecutor
{
    static $executor = null;
    if ($executor instanceof App\Services\SqlScriptExecutor) {
        return $executor;
    }
    $executor = new App\Services\SqlScriptExecutor();
    return $executor;
}

function installMigrationService(PDO $pdo): App\Services\MigrationService
{
    return new App\Services\MigrationService($pdo, installSqlExecutor());
}

function withInstallLock(PDO $pdo, callable $callback): mixed
{
    $dbName = (string) ($pdo->query('SELECT DATABASE()')->fetchColumn() ?: 'app');
    $lockName = App\Services\MigrationService::lockNameForDatabase($dbName);
    $acquire = $pdo->prepare('SELECT GET_LOCK(:name, :timeout)');
    $acquire->execute([':name' => $lockName, ':timeout' => 0]);
    $acquired = (int) $acquire->fetchColumn();
    if ($acquired !== 1) {
        throw new RuntimeException('Another install/migration process is already running for this database.');
    }

    try {
        return $callback();
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(:name)');
        $release->execute([':name' => $lockName]);
    }
}

function mysqlBufferedQueryAttr(): ?int
{
    if (defined('Pdo\\Mysql::ATTR_USE_BUFFERED_QUERY')) {
        return (int) constant('Pdo\\Mysql::ATTR_USE_BUFFERED_QUERY');
    }
    if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
        return (int) constant('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY');
    }
    return null;
}

function connectServer(array $db): PDO
{
    $port = (int) ($db['port'] ?? 0);
    $portPart = $port > 0 ? ';port=' . $port : '';
    $dsn = sprintf('mysql:host=%s%s;charset=%s', $db['host'], $portPart, $db['charset']);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $bufferedQueryAttr = mysqlBufferedQueryAttr();
    if ($bufferedQueryAttr !== null) {
        $options[$bufferedQueryAttr] = true;
    }
    return new PDO($dsn, $db['user'], $db['password'] ?? '', $options);
}

function connectDatabase(array $db): PDO
{
    $port = (int) ($db['port'] ?? 0);
    $portPart = $port > 0 ? ';port=' . $port : '';
    $dsn = sprintf('mysql:host=%s%s;dbname=%s;charset=%s', $db['host'], $portPart, $db['dbname'], $db['charset']);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $bufferedQueryAttr = mysqlBufferedQueryAttr();
    if ($bufferedQueryAttr !== null) {
        $options[$bufferedQueryAttr] = true;
    }
    return new PDO($dsn, $db['user'], $db['password'] ?? '', $options);
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
    return installSqlExecutor()->splitStatements($sql);
}

function shouldSkipBaselineStatement(string $stmt): bool
{
    $normalized = ltrim($stmt);
    if (preg_match('/^CREATE\s+DATABASE\b/i', $normalized) === 1) {
        return true;
    }
    if (preg_match('/^USE\s+[`"]?[a-zA-Z0-9_]+[`"]?$/i', $normalized) === 1) {
        return true;
    }
    return false;
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
    $sql = file_get_contents($dumpPath);
    if ($sql === false) {
        throw new RuntimeException("Failed to read SQL migration: {$dumpPath}");
    }
    $statements = splitSqlStatements($sql);
    foreach ($statements as $stmt) {
        if (shouldSkipBaselineStatement($stmt)) {
            continue;
        }
        installSqlExecutor()->executeStatement($pdo, $stmt);
    }
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

function switchToConfiguredDatabase(PDO $pdo, string $dbName, bool $dryRun): void
{
    if ($dryRun) {
        out('PLAN', "Would switch active database to `{$dbName}` after baseline import.");
        return;
    }
    $safeDbName = str_replace('`', '``', $dbName);
    $pdo->exec("USE `{$safeDbName}`");
}

function handleBootstrapPhase(
    PDO $pdo,
    string $dbName,
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
        switchToConfiguredDatabase($pdo, $dbName, $dryRun);
        markBaselineImported($pdo, $dumpPath, $dryRun);
        return EXIT_OK;
    }

    if ($forceBootstrap) {
        out('WARN', 'Force bootstrap requested on non-empty DB.');
        importBaselineDump($pdo, $dumpPath, $dryRun);
        switchToConfiguredDatabase($pdo, $dbName, $dryRun);
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
    if ($dryRun && !tableExists($pdo, 'schema_migrations')) {
        out('PLAN', 'Pending migrations cannot be resolved precisely in dry-run without DB metadata.');
        return ['applied' => 0, 'skipped' => 0, 'pending' => 0];
    }

    $effectiveDryRun = $dryRun && !$statusOnly;
    $service = installMigrationService($pdo);
    return $service->applyPending($effectiveDryRun, $statusOnly, static function (string $name, bool $dry): void {
        out($dry ? 'PLAN' : 'INFO', ($dry ? 'Would apply migration: ' : 'Pending migration: ') . $name);
    });
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
    require_once $root . '/app/services/SqlScriptExecutor.php';
    require_once $root . '/app/services/MigrationService.php';
    /** @var array $config */
    $config = require $root . '/app/config/config.php';
    return $config;
}

function runInstaller(array $opts, string $root): int
{
    try {
        $config = loadAppConfig($root);
        ensureCliAdminAllowed();
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

        preflightDumpPath($dumpPath);

        $serverPdo = connectServer($db);
        createDatabaseIfMissing($serverPdo, $db['dbname'], $dryRun);

        if (!databaseExists($serverPdo, $db['dbname'])) {
            throw new RuntimeException('Database does not exist and cannot be created in current mode.');
        }

        $pdo = connectDatabase($db);
        $readonlyMode = $dryRun || $statusOnly;
        $runInstallFlow = function () use (
            $pdo,
            $db,
            $dumpPath,
            $migrationsDir,
            $readonlyMode,
            $statusOnly,
            $forceBootstrap,
            $dryRun
        ): int {
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
                $db['dbname'],
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
            return EXIT_OK;
        };

        $flowStatus = $readonlyMode ? $runInstallFlow() : withInstallLock($pdo, $runInstallFlow);
        if ($flowStatus !== EXIT_OK) {
            return $flowStatus;
        }

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
