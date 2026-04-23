<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\VietnameseRecovery;

$options = parseOptions($argv);
$archivePath = (string) ($options['source-archive'] ?? VietnameseRecovery::defaultArchivePath());
$backupDir = resolveOutputDir((string) ($options['backup-dir'] ?? 'scratch/db-backups'));
$reportDir = resolveOutputDir((string) ($options['report-dir'] ?? 'scratch/vietnamese-repair'));
$apply = array_key_exists('apply', $options);
$dryRun = !$apply || array_key_exists('dry-run', $options);

VietnameseRecovery::ensureDirectory($backupDir);
VietnameseRecovery::ensureDirectory($reportDir);
VietnameseRecovery::assertDecoderFixtures();

$connection = VietnameseRecovery::connectPdoWithFallback();
$pdo = $connection['pdo'];

$employeeReference = loadEmployeeReference(
    VietnameseRecovery::beRoot() . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'test_accounts.tsv',
    VietnameseRecovery::beRoot() . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'test_accounts_by_role.md'
);

$archiveSql = VietnameseRecovery::readArchiveEntry(
    $archivePath,
    ['./data.sql', './BE/data.sql', 'data.sql', 'BE/data.sql']
)['raw'];

$archiveRows = [
    'employees' => indexRowsByKey(
        decodeArchiveRows($archiveSql, $pdo, 'employees', ['full_name'], 'employee_code'),
        'employee_code'
    ),
    'news' => indexRowsByKey(
        decodeArchiveRows($archiveSql, $pdo, 'news', ['title', 'summary', 'content'], 'news_code'),
        'news_code'
    ),
    'notifications' => indexRowsByKey(
        decodeArchiveRows($archiveSql, $pdo, 'notifications', ['title', 'content'], 'notification_id'),
        'notification_id'
    ),
];

$operations = [];
$unrecoverable = [];

buildEmployeeOperations($pdo, $employeeReference, $archiveRows['employees'], $operations);
buildCodeMappingOperations($pdo, 'shift_types', 'shift_type_id', 'shift_code', 'shift_name', shiftTypeMap(), 'deterministic_mapping:shift_types', $operations);
buildCodeMappingOperations($pdo, 'leave_types', 'leave_type_id', 'leave_type_code', 'leave_type_name', leaveTypeMap(), 'deterministic_mapping:leave_types', $operations);
buildNewsOperations($pdo, $archiveRows['news'], $operations);
buildNotificationOperations($pdo, $archiveRows['notifications'], $operations);
buildReportTemplateOperations($pdo, $operations);
buildContractUnrecoverableReport($pdo, $unrecoverable);

$groupedOperations = groupOperationsByRow($operations);

$repairPlan = [
    'generated_at' => date(DATE_ATOM),
    'mode' => $apply ? 'apply' : 'dry-run',
    'archive_path' => $archivePath,
    'backup_dir' => $backupDir,
    'report_dir' => $reportDir,
    'connection' => $connection['connection'],
    'operations' => $operations,
    'operation_count' => count($operations),
    'unrecoverable_count' => count($unrecoverable),
];

VietnameseRecovery::writeJson($reportDir . DIRECTORY_SEPARATOR . 'repair_plan.json', $repairPlan);
VietnameseRecovery::writeJson($reportDir . DIRECTORY_SEPARATOR . 'unrecoverable_rows.json', $unrecoverable);

$appliedChanges = [];
$backupManifest = [
    'generated_at' => date(DATE_ATOM),
    'mode' => $apply ? 'apply' : 'dry-run',
    'backup_dir' => $backupDir,
    'full_dump' => null,
    'table_exports' => [],
];

if ($apply) {
    $backupManifest = createBackups($pdo, $backupDir, $groupedOperations, $unrecoverable);
    applyPreRepairDdl($pdo);
    $appliedChanges = applyOperations($pdo, $groupedOperations);
    applyPostRepairDdl($pdo);
}

$remainingIssues = collectRemainingIssues($pdo);
$repairReport = [
    'generated_at' => date(DATE_ATOM),
    'mode' => $apply ? 'apply' : 'dry-run',
    'applied_change_count' => count($appliedChanges),
    'remaining_issue_count' => count($remainingIssues),
    'unrecoverable_count' => count($unrecoverable),
    'remaining_issues' => $remainingIssues,
];

VietnameseRecovery::writeJson($reportDir . DIRECTORY_SEPARATOR . 'applied_changes.json', $appliedChanges);
VietnameseRecovery::writeJson($reportDir . DIRECTORY_SEPARATOR . 'backup_manifest.json', $backupManifest);
VietnameseRecovery::writeJson($reportDir . DIRECTORY_SEPARATOR . 'repair_report.json', $repairReport);

$markdown = buildRepairMarkdown($repairPlan, $appliedChanges, $unrecoverable, $remainingIssues, $backupManifest);
if (file_put_contents($reportDir . DIRECTORY_SEPARATOR . 'repair_report.md', $markdown) === false) {
    throw new RuntimeException('Cannot write repair_report.md');
}

echo "[repair_vietnamese_data]\n";
echo 'mode=' . ($apply ? 'apply' : 'dry-run') . PHP_EOL;
echo 'archive_path=' . $archivePath . PHP_EOL;
echo 'report_dir=' . $reportDir . PHP_EOL;
echo 'backup_dir=' . $backupDir . PHP_EOL;
echo 'planned_changes=' . count($operations) . PHP_EOL;
echo 'applied_changes=' . count($appliedChanges) . PHP_EOL;
echo 'unrecoverable_rows=' . count($unrecoverable) . PHP_EOL;
echo 'remaining_issues=' . count($remainingIssues) . PHP_EOL;

/**
 * @return array<string, string>
 */
function parseOptions(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $argument) {
        if (!str_starts_with($argument, '--')) {
            continue;
        }

        $argument = substr($argument, 2);
        if (!str_contains($argument, '=')) {
            $options[$argument] = '1';
            continue;
        }

        [$key, $value] = explode('=', $argument, 2);
        $options[$key] = $value;
    }

    return $options;
}

function resolveOutputDir(string $path): string
{
    if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1 || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
        return $path;
    }

    return VietnameseRecovery::repoRoot() . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

/**
 * @return array<string, string>
 */
function loadEmployeeReference(string $tsvPath, string $mdPath): array
{
    $raw = file_get_contents($tsvPath);
    if ($raw === false || !mb_check_encoding($raw, 'UTF-8')) {
        throw new RuntimeException('Cannot load clean employee TSV reference.');
    }

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
    $lines = preg_split("/\r\n|\n|\r/", trim($raw));
    if (!is_array($lines) || count($lines) < 2) {
        throw new RuntimeException('Employee TSV reference is empty.');
    }

    $headers = array_map('trim', str_getcsv((string) array_shift($lines), "\t"));
    $names = [];
    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }
        $values = str_getcsv($line, "\t");
        if (count($values) !== count($headers)) {
            continue;
        }
        $row = array_combine($headers, $values);
        if (!is_array($row)) {
            continue;
        }
        $code = trim((string) ($row['employee_code'] ?? ''));
        $name = trim((string) ($row['full_name'] ?? ''));
        if ($code !== '' && $name !== '') {
            $names[$code] = $name;
        }
    }

    $mdNames = parseEmployeeNamesFromMarkdown($mdPath);
    foreach ($mdNames as $code => $name) {
        if (isset($names[$code]) && $names[$code] !== $name) {
            throw new RuntimeException("Employee reference mismatch for {$code}: TSV vs Markdown.");
        }
    }

    return $names;
}

/**
 * @return array<string, string>
 */
function parseEmployeeNamesFromMarkdown(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false || !mb_check_encoding($raw, 'UTF-8')) {
        return [];
    }

    $rows = [];
    foreach (preg_split("/\r\n|\n|\r/", $raw) ?: [] as $line) {
        if (!str_starts_with(trim($line), '|')) {
            continue;
        }

        $parts = array_map('trim', explode('|', trim($line, " \t|")));
        if (count($parts) < 2) {
            continue;
        }

        $employeeCode = $parts[0] ?? '';
        $fullName = $parts[1] ?? '';
        if ($employeeCode === '' || $employeeCode === 'Mã NV' || $fullName === '' || $fullName === 'Họ tên') {
            continue;
        }

        $rows[$employeeCode] = $fullName;
    }

    return $rows;
}

/**
 * @param array<string, mixed> $archiveRows
 */
function buildEmployeeOperations(PDO $pdo, array $cleanReference, array $archiveRows, array &$operations): void
{
    $stmt = $pdo->query('SELECT employee_id, employee_code, full_name FROM employees ORDER BY employee_id');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($rows as $row) {
        $employeeCode = (string) ($row['employee_code'] ?? '');
        $before = (string) ($row['full_name'] ?? '');
        $after = null;
        $sourceUsed = null;

        if ($employeeCode === '') {
            continue;
        }

        if (isset($cleanReference[$employeeCode])) {
            $after = $cleanReference[$employeeCode];
            $sourceUsed = 'repo:BE/scripts/test_accounts.tsv';
        } elseif (isset($archiveRows[$employeeCode]['full_name'])) {
            $after = (string) $archiveRows[$employeeCode]['full_name'];
            $sourceUsed = 'archive:data.sql';
        }

        if ($after === null || $after === '' || $before === $after) {
            continue;
        }

        $operations[] = [
            'table' => 'employees',
            'primary_key' => 'employee_id',
            'primary_key_value' => (int) $row['employee_id'],
            'column' => 'full_name',
            'before' => $before,
            'after' => $after,
            'source_used' => $sourceUsed,
            'match_key' => $employeeCode,
        ];
    }
}

/**
 * @param array<string, string> $mapping
 */
function buildCodeMappingOperations(
    PDO $pdo,
    string $table,
    string $primaryKey,
    string $codeColumn,
    string $valueColumn,
    array $mapping,
    string $sourceUsed,
    array &$operations
): void {
    $sql = sprintf(
        'SELECT %s, %s, %s FROM %s ORDER BY %s',
        VietnameseRecovery::quoteIdentifier($primaryKey),
        VietnameseRecovery::quoteIdentifier($codeColumn),
        VietnameseRecovery::quoteIdentifier($valueColumn),
        VietnameseRecovery::quoteIdentifier($table),
        VietnameseRecovery::quoteIdentifier($primaryKey)
    );
    $stmt = $pdo->query($sql);
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($rows as $row) {
        $code = (string) ($row[$codeColumn] ?? '');
        $before = (string) ($row[$valueColumn] ?? '');
        $after = $mapping[$code] ?? null;

        if ($after === null || $before === $after) {
            continue;
        }

        $operations[] = [
            'table' => $table,
            'primary_key' => $primaryKey,
            'primary_key_value' => (int) $row[$primaryKey],
            'column' => $valueColumn,
            'before' => $before,
            'after' => $after,
            'source_used' => $sourceUsed,
            'match_key' => $code,
        ];
    }
}

/**
 * @param array<string, array<string, mixed>> $archiveRows
 */
function buildNewsOperations(PDO $pdo, array $archiveRows, array &$operations): void
{
    $stmt = $pdo->query('SELECT news_id, news_code, title, summary, content, status, priority FROM news ORDER BY news_id');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($rows as $row) {
        $newsCode = (string) ($row['news_code'] ?? '');
        $archive = $archiveRows[$newsCode] ?? null;
        if (is_array($archive)) {
            foreach (['title', 'summary', 'content'] as $column) {
                $before = (string) ($row[$column] ?? '');
                $after = (string) ($archive[$column] ?? '');
                if ($after !== '' && $before !== $after) {
                    $operations[] = [
                        'table' => 'news',
                        'primary_key' => 'news_id',
                        'primary_key_value' => (int) $row['news_id'],
                        'column' => $column,
                        'before' => $before,
                        'after' => $after,
                        'source_used' => 'archive:data.sql',
                        'match_key' => $newsCode,
                    ];
                }
            }
        }

        if (str_contains((string) ($row['status'] ?? ''), '?')) {
            $operations[] = [
                'table' => 'news',
                'primary_key' => 'news_id',
                'primary_key_value' => (int) $row['news_id'],
                'column' => 'status',
                'before' => (string) $row['status'],
                'after' => 'ĐÃ_XUẤT_BẢN',
                'source_used' => 'deterministic_mapping:news.status',
                'match_key' => $newsCode,
            ];
        }

        if (str_contains((string) ($row['priority'] ?? ''), '?')) {
            $operations[] = [
                'table' => 'news',
                'primary_key' => 'news_id',
                'primary_key_value' => (int) $row['news_id'],
                'column' => 'priority',
                'before' => (string) $row['priority'],
                'after' => 'TRUNG_BÌNH',
                'source_used' => 'deterministic_mapping:news.priority',
                'match_key' => $newsCode,
            ];
        }
    }
}

/**
 * @param array<string, array<string, mixed>> $archiveRows
 */
function buildNotificationOperations(PDO $pdo, array $archiveRows, array &$operations): void
{
    $stmt = $pdo->query('SELECT notification_id, notification_type, title, content, priority FROM notifications ORDER BY notification_id');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($rows as $row) {
        $notificationId = (string) ($row['notification_id'] ?? '');
        $archive = $archiveRows[$notificationId] ?? null;
        if (is_array($archive)) {
            foreach (['title', 'content'] as $column) {
                $before = (string) ($row[$column] ?? '');
                $after = (string) ($archive[$column] ?? '');
                if ($after !== '' && $before !== $after) {
                    $operations[] = [
                        'table' => 'notifications',
                        'primary_key' => 'notification_id',
                        'primary_key_value' => (int) $row['notification_id'],
                        'column' => $column,
                        'before' => $before,
                        'after' => $after,
                        'source_used' => 'archive:data.sql',
                        'match_key' => $notificationId,
                    ];
                }
            }
        }

        if (str_contains((string) ($row['priority'] ?? ''), '?')) {
            $operations[] = [
                'table' => 'notifications',
                'primary_key' => 'notification_id',
                'primary_key_value' => (int) $row['notification_id'],
                'column' => 'priority',
                'before' => (string) $row['priority'],
                'after' => 'TRUNG_BÌNH',
                'source_used' => 'deterministic_mapping:notifications.priority',
                'match_key' => $notificationId,
            ];
        }
    }
}

function buildReportTemplateOperations(PDO $pdo, array &$operations): void
{
    $stmt = $pdo->query('SELECT template_id, template_code, template_name, columns_config, sql_query FROM report_templates ORDER BY template_id');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $nameMap = reportTemplateNameMap();
    $columnsMap = reportTemplateColumnsConfigMap();

    foreach ($rows as $row) {
        $templateCode = (string) ($row['template_code'] ?? '');
        $templateId = (int) ($row['template_id'] ?? 0);

        $nameAfter = $nameMap[$templateCode] ?? null;
        if ($nameAfter !== null && (string) ($row['template_name'] ?? '') !== $nameAfter) {
            $operations[] = [
                'table' => 'report_templates',
                'primary_key' => 'template_id',
                'primary_key_value' => $templateId,
                'column' => 'template_name',
                'before' => (string) $row['template_name'],
                'after' => $nameAfter,
                'source_used' => 'deterministic_mapping:report_templates.template_name',
                'match_key' => $templateCode,
            ];
        }

        $columnsAfter = $columnsMap[$templateCode] ?? null;
        if ($columnsAfter !== null && (string) ($row['columns_config'] ?? '') !== $columnsAfter) {
            $operations[] = [
                'table' => 'report_templates',
                'primary_key' => 'template_id',
                'primary_key_value' => $templateId,
                'column' => 'columns_config',
                'before' => (string) $row['columns_config'],
                'after' => $columnsAfter,
                'source_used' => 'deterministic_mapping:report_templates.columns_config',
                'match_key' => $templateCode,
            ];
        }

        if ($templateCode === 'RP_DEPARTMENT' && str_contains((string) ($row['sql_query'] ?? ''), '?ANG_L?M_VI?C')) {
            $operations[] = [
                'table' => 'report_templates',
                'primary_key' => 'template_id',
                'primary_key_value' => $templateId,
                'column' => 'sql_query',
                'before' => (string) $row['sql_query'],
                'after' => str_replace('?ANG_L?M_VI?C', 'ĐANG_LÀM_VIỆC', (string) $row['sql_query']),
                'source_used' => 'deterministic_mapping:report_templates.sql_query',
                'match_key' => $templateCode,
            ];
        }
    }
}

function buildContractUnrecoverableReport(PDO $pdo, array &$unrecoverable): void
{
    $stmt = $pdo->query(
        'SELECT contract_id, contract_code, work_location, job_title
         FROM contracts
         WHERE (work_location IS NOT NULL AND work_location LIKE "%?%")
            OR (job_title IS NOT NULL AND job_title LIKE "%?%")
         ORDER BY contract_id'
    );
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($rows as $row) {
        foreach (['work_location', 'job_title'] as $column) {
            $value = $row[$column] ?? null;
            if (!is_string($value) || !str_contains($value, '?')) {
                continue;
            }

            $unrecoverable[] = [
                'table' => 'contracts',
                'primary_key' => 'contract_id',
                'primary_key_value' => (int) $row['contract_id'],
                'match_key' => (string) ($row['contract_code'] ?? ''),
                'column' => $column,
                'current_value' => $value,
                'hex_value' => strtoupper(bin2hex($value)),
                'reason' => 'original_vietnamese_lost_without_trusted_source',
            ];
        }
    }
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return array<string, array<string, mixed>>
 */
function indexRowsByKey(array $rows, string $key): array
{
    $indexed = [];
    foreach ($rows as $row) {
        $value = $row[$key] ?? null;
        if ($value === null || $value === '') {
            continue;
        }
        $indexed[(string) $value] = $row;
    }

    return $indexed;
}

/**
 * @param string[] $decodeColumns
 * @return array<int, array<string, mixed>>
 */
function decodeArchiveRows(string $archiveSql, PDO $pdo, string $table, array $decodeColumns, string $keyColumn): array
{
    $columns = VietnameseRecovery::loadTableColumns($pdo, $table);
    $rows = VietnameseRecovery::parseInsertRows($archiveSql, $table, $columns);

    foreach ($rows as &$row) {
        foreach ($decodeColumns as $column) {
            if (!isset($row[$column]) || !is_string($row[$column])) {
                continue;
            }
            $row[$column] = VietnameseRecovery::decodeArchiveMojibake((string) $row[$column]);
        }
    }
    unset($row);

    return array_values(array_filter(
        $rows,
        static fn (array $row): bool => isset($row[$keyColumn]) && $row[$keyColumn] !== null && $row[$keyColumn] !== ''
    ));
}

/**
 * @param array<int, array<string, mixed>> $operations
 * @return array<string, array<string, mixed>>
 */
function groupOperationsByRow(array $operations): array
{
    $grouped = [];
    foreach ($operations as $operation) {
        $key = sprintf(
            '%s|%s|%s',
            (string) $operation['table'],
            (string) $operation['primary_key'],
            (string) $operation['primary_key_value']
        );
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'table' => $operation['table'],
                'primary_key' => $operation['primary_key'],
                'primary_key_value' => $operation['primary_key_value'],
                'match_key' => $operation['match_key'] ?? null,
                'changes' => [],
            ];
        }

        $grouped[$key]['changes'][$operation['column']] = $operation;
    }

    return $grouped;
}

/**
 * @param array<string, array<string, mixed>> $groupedOperations
 * @param array<int, array<string, mixed>> $unrecoverable
 * @return array<string, mixed>
 */
function createBackups(PDO $pdo, string $backupDir, array $groupedOperations, array $unrecoverable): array
{
    $timestamp = VietnameseRecovery::timestamp();
    $fullDumpPath = $backupDir . DIRECTORY_SEPARATOR . "full_db_{$timestamp}.sql";
    createFullDatabaseDump($pdo, $fullDumpPath);

    $tables = [];
    foreach ($groupedOperations as $row) {
        $tables[(string) $row['table']] = true;
    }
    foreach ($unrecoverable as $row) {
        $tables[(string) $row['table']] = true;
    }

    $tableExports = [];
    foreach (array_keys($tables) as $table) {
        $stmt = $pdo->query('SELECT * FROM ' . VietnameseRecovery::quoteIdentifier($table));
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $path = $backupDir . DIRECTORY_SEPARATOR . "{$table}_{$timestamp}.csv";
        VietnameseRecovery::writeCsv($path, $rows);
        $tableExports[$table] = $path;
    }

    return [
        'generated_at' => date(DATE_ATOM),
        'full_dump' => $fullDumpPath,
        'table_exports' => $tableExports,
    ];
}

function createFullDatabaseDump(PDO $pdo, string $outputPath): void
{
    if (is_file($outputPath)) {
        @unlink($outputPath);
    }

    $dbName = (string) env('MYSQL_DATABASE', env('DB_DATABASE', 'hrm_db'));
    $rootPassword = (string) env('MYSQL_ROOT_PASSWORD', '');
    $envFile = is_file(VietnameseRecovery::repoRoot() . DIRECTORY_SEPARATOR . '.env.deploy')
        ? VietnameseRecovery::repoRoot() . DIRECTORY_SEPARATOR . '.env.deploy'
        : VietnameseRecovery::repoRoot() . DIRECTORY_SEPARATOR . '.env';

    $command = null;
    if ($rootPassword !== '') {
        $command = sprintf(
            'docker compose --env-file %s exec -T mysql mysqldump --default-character-set=utf8mb4 -uroot -p%s %s > %s',
            escapeshellarg($envFile),
            escapeshellarg($rootPassword),
            escapeshellarg($dbName),
            escapeshellarg($outputPath)
        );
        $result = VietnameseRecovery::runCommand($command, VietnameseRecovery::repoRoot());
        if (($result['exit_code'] ?? 1) === 0 && is_file($outputPath)) {
            return;
        }
        if (is_file($outputPath) && filesize($outputPath) === 0) {
            @unlink($outputPath);
        }
    }

    if (commandExists('mysqldump')) {
        $connection = VietnameseRecovery::connectPdoWithFallback();
        $config = VietnameseRecovery::loadDatabaseConfig();
        $host = (string) ($connection['connection']['host'] ?? $config['host']);
        $port = (int) ($connection['connection']['port'] ?? $config['port']);
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $command = sprintf(
            'mysqldump --default-character-set=utf8mb4 -h%s -P%d -u%s -p%s %s > %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($dbName),
            escapeshellarg($outputPath)
        );
        $result = VietnameseRecovery::runCommand($command, VietnameseRecovery::repoRoot());
        if (($result['exit_code'] ?? 1) === 0 && is_file($outputPath)) {
            return;
        }
        if (is_file($outputPath) && filesize($outputPath) === 0) {
            @unlink($outputPath);
        }
    }

    createPdoSqlDump($pdo, $outputPath);
}

function commandExists(string $command): bool
{
    $check = stripos(PHP_OS_FAMILY, 'Windows') !== false
        ? sprintf('where %s', escapeshellarg($command))
        : sprintf('command -v %s', escapeshellarg($command));

    $result = VietnameseRecovery::runCommand($check, VietnameseRecovery::repoRoot());

    return ($result['exit_code'] ?? 1) === 0;
}

function createPdoSqlDump(PDO $pdo, string $outputPath): void
{
    $handle = fopen($outputPath, 'wb');
    if ($handle === false) {
        throw new RuntimeException('Cannot create fallback SQL dump file.');
    }

    fwrite($handle, "-- Fallback SQL dump generated by repair_vietnamese_data.php\n");
    fwrite($handle, '-- Generated at: ' . date(DATE_ATOM) . "\n");
    fwrite($handle, "SET NAMES utf8mb4;\n");
    fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

    try {
        $tablesStmt = $pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tables = $tablesStmt ? $tablesStmt->fetchAll(PDO::FETCH_NUM) : [];

        foreach ($tables as $tableRow) {
            $table = (string) ($tableRow[0] ?? '');
            if ($table === '') {
                continue;
            }

            $createStmt = $pdo->query('SHOW CREATE TABLE ' . VietnameseRecovery::quoteIdentifier($table));
            $createRow = $createStmt ? $createStmt->fetch(PDO::FETCH_NUM) : false;
            if (!is_array($createRow) || !isset($createRow[1])) {
                continue;
            }

            fwrite($handle, '-- Table: ' . $table . "\n");
            fwrite($handle, 'DROP TABLE IF EXISTS ' . VietnameseRecovery::quoteIdentifier($table) . ";\n");
            fwrite($handle, (string) $createRow[1] . ";\n\n");

            $rowsStmt = $pdo->query('SELECT * FROM ' . VietnameseRecovery::quoteIdentifier($table));
            if (!$rowsStmt) {
                fwrite($handle, "\n");
                continue;
            }

            while (($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) !== false) {
                $columns = array_map(
                    static fn (string $column): string => VietnameseRecovery::quoteIdentifier($column),
                    array_keys($row)
                );
                $values = array_map(
                    static fn ($value): string => toSqlLiteral($value),
                    array_values($row)
                );

                fwrite(
                    $handle,
                    sprintf(
                        "INSERT INTO %s (%s) VALUES (%s);\n",
                        VietnameseRecovery::quoteIdentifier($table),
                        implode(', ', $columns),
                        implode(', ', $values)
                    )
                );
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    } finally {
        fclose($handle);
    }
}

function toSqlLiteral($value): string
{
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_int($value) || is_float($value)) {
        return (string) $value;
    }

    $value = (string) $value;

    return "'" . str_replace(
        ["\\", "'", "\0", "\n", "\r", "\x1a"],
        ["\\\\", "\\'", "\\0", "\\n", "\\r", "\\Z"],
        $value
    ) . "'";
}

function applyPreRepairDdl(PDO $pdo): void
{
    $statements = [
        'ALTER TABLE news MODIFY status VARCHAR(64) NULL',
        'ALTER TABLE news MODIFY priority VARCHAR(64) NULL',
        'ALTER TABLE notifications MODIFY priority VARCHAR(64) NULL',
    ];

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

function applyPostRepairDdl(PDO $pdo): void
{
    $statements = [
        "ALTER TABLE news MODIFY status ENUM('NHÁP', 'ĐÃ_XUẤT_BẢN', 'LƯU_TRỮ') DEFAULT 'NHÁP'",
        "ALTER TABLE news MODIFY priority ENUM('THẤP', 'TRUNG_BÌNH', 'CAO', 'KHẨN_CẤP') DEFAULT 'TRUNG_BÌNH'",
        "ALTER TABLE notifications MODIFY priority ENUM('THẤP', 'TRUNG_BÌNH', 'CAO') DEFAULT 'TRUNG_BÌNH'",
    ];

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

/**
 * @param array<string, array<string, mixed>> $groupedOperations
 * @return array<int, array<string, mixed>>
 */
function applyOperations(PDO $pdo, array $groupedOperations): array
{
    $applied = [];
    foreach ($groupedOperations as $row) {
        $setParts = [];
        $params = ['pk' => $row['primary_key_value']];
        foreach ($row['changes'] as $column => $change) {
            $setParts[] = VietnameseRecovery::quoteIdentifier((string) $column) . ' = :' . $column;
            $params[$column] = $change['after'];
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :pk',
            VietnameseRecovery::quoteIdentifier((string) $row['table']),
            implode(', ', $setParts),
            VietnameseRecovery::quoteIdentifier((string) $row['primary_key'])
        );
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        foreach ($row['changes'] as $change) {
            $applied[] = $change;
        }
    }

    return $applied;
}

/**
 * @return array<int, array<string, mixed>>
 */
function collectRemainingIssues(PDO $pdo): array
{
    $datasets = [
        ['table' => 'employees', 'primary_key' => 'employee_id', 'column' => 'full_name'],
        ['table' => 'shift_types', 'primary_key' => 'shift_type_id', 'column' => 'shift_name'],
        ['table' => 'leave_types', 'primary_key' => 'leave_type_id', 'column' => 'leave_type_name'],
        ['table' => 'news', 'primary_key' => 'news_id', 'column' => 'title'],
        ['table' => 'news', 'primary_key' => 'news_id', 'column' => 'summary'],
        ['table' => 'news', 'primary_key' => 'news_id', 'column' => 'content'],
        ['table' => 'news', 'primary_key' => 'news_id', 'column' => 'status'],
        ['table' => 'news', 'primary_key' => 'news_id', 'column' => 'priority'],
        ['table' => 'notifications', 'primary_key' => 'notification_id', 'column' => 'title'],
        ['table' => 'notifications', 'primary_key' => 'notification_id', 'column' => 'content'],
        ['table' => 'notifications', 'primary_key' => 'notification_id', 'column' => 'priority'],
        ['table' => 'report_templates', 'primary_key' => 'template_id', 'column' => 'template_name'],
        ['table' => 'report_templates', 'primary_key' => 'template_id', 'column' => 'columns_config'],
        ['table' => 'report_templates', 'primary_key' => 'template_id', 'column' => 'sql_query'],
        ['table' => 'contracts', 'primary_key' => 'contract_id', 'column' => 'work_location'],
        ['table' => 'contracts', 'primary_key' => 'contract_id', 'column' => 'job_title'],
    ];

    $issues = [];
    foreach ($datasets as $dataset) {
        $sql = sprintf(
            'SELECT %s AS pk, %s AS value FROM %s',
            VietnameseRecovery::quoteIdentifier($dataset['primary_key']),
            VietnameseRecovery::quoteIdentifier($dataset['column']),
            VietnameseRecovery::quoteIdentifier($dataset['table'])
        );
        $stmt = $pdo->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as $row) {
            $value = $row['value'] ?? null;
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            if ($dataset['table'] === 'report_templates' && $dataset['column'] === 'sql_query') {
                continue;
            }

            $kind = App\Core\VietnameseRecovery::detectCorruptionKind($value);
            if ($kind === null) {
                continue;
            }

            $issues[] = [
                'table' => $dataset['table'],
                'primary_key' => $dataset['primary_key'],
                'primary_key_value' => $row['pk'],
                'column' => $dataset['column'],
                'current_value' => $value,
                'hex_value' => strtoupper(bin2hex($value)),
                'corruption_kind' => $kind,
            ];
        }
    }

    return $issues;
}

/**
 * @param array<string, mixed> $plan
 * @param array<int, array<string, mixed>> $applied
 * @param array<int, array<string, mixed>> $unrecoverable
 * @param array<int, array<string, mixed>> $remaining
 * @param array<string, mixed> $backupManifest
 */
function buildRepairMarkdown(
    array $plan,
    array $applied,
    array $unrecoverable,
    array $remaining,
    array $backupManifest
): string {
    $lines = [
        '# Vietnamese Data Repair',
        '',
        '- Mode: `' . (string) ($plan['mode'] ?? 'dry-run') . '`',
        '- Planned changes: `' . (int) ($plan['operation_count'] ?? 0) . '`',
        '- Applied changes: `' . count($applied) . '`',
        '- Unrecoverable rows: `' . count($unrecoverable) . '`',
        '- Remaining issues: `' . count($remaining) . '`',
        '',
        '## Backups',
        '- Full dump: `' . (string) ($backupManifest['full_dump'] ?? 'n/a') . '`',
    ];

    foreach (($backupManifest['table_exports'] ?? []) as $table => $path) {
        $lines[] = '- `' . (string) $table . '`: `' . (string) $path . '`';
    }

    $lines[] = '';
    $lines[] = '## Applied Changes';
    foreach ($applied as $change) {
        $lines[] = sprintf(
            '- `%s` `%s=%s` `%s`',
            (string) ($change['table'] ?? ''),
            (string) ($change['primary_key'] ?? ''),
            (string) ($change['primary_key_value'] ?? ''),
            (string) ($change['column'] ?? '')
        );
    }

    $lines[] = '';
    $lines[] = '## Unrecoverable Rows';
    foreach ($unrecoverable as $row) {
        $lines[] = sprintf(
            '- `%s` `%s=%s` `%s`: `%s`',
            (string) ($row['table'] ?? ''),
            (string) ($row['primary_key'] ?? ''),
            (string) ($row['primary_key_value'] ?? ''),
            (string) ($row['column'] ?? ''),
            (string) ($row['reason'] ?? '')
        );
    }

    $lines[] = '';
    $lines[] = '## Remaining Issues';
    foreach ($remaining as $issue) {
        $lines[] = sprintf(
            '- `%s` `%s=%s` `%s`: `%s`',
            (string) ($issue['table'] ?? ''),
            (string) ($issue['primary_key'] ?? ''),
            (string) ($issue['primary_key_value'] ?? ''),
            (string) ($issue['column'] ?? ''),
            (string) ($issue['corruption_kind'] ?? '')
        );
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
}

/**
 * @return array<string, string>
 */
function shiftTypeMap(): array
{
    return [
        'HC' => 'Hành chính',
        'CA1' => 'Ca Sáng',
        'CA2' => 'Ca Chiều',
        'CA3' => 'Ca Đêm',
        'HC_LINH' => 'Hành chính Linh hoạt',
        'CUOI_TUAN' => 'Ca Cuối tuần',
    ];
}

/**
 * @return array<string, string>
 */
function leaveTypeMap(): array
{
    return [
        'PHEP_NAM' => 'Nghỉ phép năm',
        'OM_DAU' => 'Nghỉ ốm đau',
        'KHONG_LUONG' => 'Nghỉ không lương',
    ];
}

/**
 * @return array<string, string>
 */
function reportTemplateNameMap(): array
{
    return [
        'RP_EMP_LIST' => 'Danh sách nhân viên',
        'RP_LEAVE_SUM' => 'Tổng hợp nghỉ phép theo phòng ban',
        'RP_SALARY' => 'Bảng lương tháng',
        'RP_ATTENDANCE' => 'Báo cáo chấm công tháng',
        'RP_DEPARTMENT' => 'Thống kê nhân sự theo phòng ban',
        'RP_LEAVE_BALANCE' => 'Số dư phép nhân viên',
    ];
}

/**
 * @return array<string, string>
 */
function reportTemplateColumnsConfigMap(): array
{
    return [
        'RP_EMP_LIST' => '{"columns":["Mã NV","Họ tên","Phòng ban","Chức vụ","Ngày vào"]}',
        'RP_LEAVE_SUM' => '{"columns":["Phòng ban","Số đơn","Tổng ngày nghỉ"]}',
        'RP_SALARY' => '{"columns":["Họ tên","Mã NV","Lương CB","Lương Gross","Lương Net","Trạng thái"]}',
        'RP_ATTENDANCE' => '{"columns":["Họ tên","Ngày","Giờ vào","Giờ ra","Số giờ"]}',
        'RP_DEPARTMENT' => '{"columns":["Phòng ban","Tổng NV","Đang làm"]}',
        'RP_LEAVE_BALANCE' => '{"columns":["Họ tên","Phòng ban","Tổng","Đã dùng","Còn lại","Gộp"]}',
    ];
}
