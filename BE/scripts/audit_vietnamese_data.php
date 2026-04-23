<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\VietnameseRecovery;

$options = parseOptions($argv);
$archivePath = (string) ($options['source-archive'] ?? VietnameseRecovery::defaultArchivePath());
$outputDir = resolveOutputDir((string) ($options['output-dir'] ?? 'scratch/vietnamese-audit'));

VietnameseRecovery::ensureDirectory($outputDir);

$connection = VietnameseRecovery::connectPdoWithFallback();
$pdo = $connection['pdo'];

$runtime = $pdo->query(
    "SELECT
        DATABASE() AS database_name,
        @@character_set_server AS character_set_server,
        @@collation_server AS collation_server,
        @@character_set_database AS character_set_database,
        @@collation_database AS collation_database,
        @@character_set_connection AS character_set_connection,
        @@character_set_client AS character_set_client,
        @@character_set_results AS character_set_results"
)->fetch(PDO::FETCH_ASSOC) ?: [];

$decoderFixtures = VietnameseRecovery::runDecoderFixtures();
$sourceFiles = inspectSources($archivePath);
$schemaIssues = inspectSchema($pdo);

$datasets = monitoredDatasets();
$affectedRows = [];
$datasetSummary = [];

foreach ($datasets as $datasetKey => $dataset) {
    $rows = fetchDatasetRows($pdo, $dataset);
    $affectedCount = 0;
    $samples = [];

    foreach ($rows as $row) {
        $value = $row[$dataset['column']] ?? null;
        $corruptionKind = detectDatasetCorruption($dataset, $row, $value);
        if ($corruptionKind === null) {
            continue;
        }

        $affectedCount++;
        $record = buildAffectedRecord($datasetKey, $dataset, $row, $value, $corruptionKind, $sourceFiles);
        $affectedRows[] = $record;
        if (count($samples) < 3) {
            $samples[] = [
                'primary_key' => $record['primary_key_value'],
                'value' => $record['current_value'],
            ];
        }
    }

    $datasetSummary[] = [
        'dataset' => $datasetKey,
        'table' => $dataset['table'],
        'column' => $dataset['column'],
        'affected_rows' => $affectedCount,
        'recoverability' => classifyDatasetRecoverability($datasetKey, $sourceFiles),
        'samples' => $samples,
    ];
}

$summary = [
    'generated_at' => date(DATE_ATOM),
    'archive_path' => $archivePath,
    'output_dir' => $outputDir,
    'connection' => $connection['connection'],
    'runtime' => $runtime,
    'decoder_fixtures' => $decoderFixtures,
    'source_files' => $sourceFiles,
    'schema_issues' => $schemaIssues,
    'datasets' => $datasetSummary,
    'totals' => [
        'affected_records' => count($affectedRows),
        'datasets_with_issues' => count(array_filter(
            $datasetSummary,
            static fn (array $item): bool => (int) ($item['affected_rows'] ?? 0) > 0
        )),
    ],
];

$recoverability = [
    'generated_at' => date(DATE_ATOM),
    'datasets' => array_map(
        static fn (array $dataset): array => [
            'dataset' => $dataset['dataset'],
            'table' => $dataset['table'],
            'column' => $dataset['column'],
            'recoverability' => $dataset['recoverability'],
            'affected_rows' => $dataset['affected_rows'],
        ],
        $datasetSummary
    ),
];

VietnameseRecovery::writeJson($outputDir . DIRECTORY_SEPARATOR . 'summary.json', $summary);
VietnameseRecovery::writeJson($outputDir . DIRECTORY_SEPARATOR . 'affected_rows.json', $affectedRows);
VietnameseRecovery::writeJson($outputDir . DIRECTORY_SEPARATOR . 'recoverability.json', $recoverability);

$markdown = buildMarkdownReport($summary, $affectedRows);
if (file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'audit_report.md', $markdown) === false) {
    throw new RuntimeException('Cannot write audit_report.md');
}

echo "[audit_vietnamese_data]\n";
echo "archive_path={$archivePath}\n";
echo "output_dir={$outputDir}\n";
echo "affected_records=" . count($affectedRows) . "\n";
foreach ($datasetSummary as $dataset) {
    if ((int) $dataset['affected_rows'] === 0) {
        continue;
    }
    echo sprintf(
        "%s | affected_rows=%d | recoverability=%s\n",
        (string) $dataset['dataset'],
        (int) $dataset['affected_rows'],
        (string) $dataset['recoverability']['class']
    );
}

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
 * @return array<string, array<string, mixed>>
 */
function inspectSources(string $archivePath): array
{
    $sources = [];

    $repoSources = [
        'repo:SQL_hackathon v4.sql' => VietnameseRecovery::repoRoot() . DIRECTORY_SEPARATOR . 'SQL_hackathon v4.sql',
        'repo:data.sql' => VietnameseRecovery::repoRoot() . DIRECTORY_SEPARATOR . 'data.sql',
        'repo:BE/scripts/test_accounts.tsv' => VietnameseRecovery::beRoot() . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'test_accounts.tsv',
        'repo:BE/scripts/test_accounts_by_role.md' => VietnameseRecovery::beRoot() . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'test_accounts_by_role.md',
    ];

    foreach ($repoSources as $label => $path) {
        if (!is_file($path)) {
            $sources[$label] = [
                'location' => $path,
                'status' => 'missing',
            ];
            continue;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            $sources[$label] = [
                'location' => $path,
                'status' => 'unsafe_for_reseed',
                'reason' => 'cannot_read',
            ];
            continue;
        }

        $inspection = VietnameseRecovery::inspectText($raw);
        $sources[$label] = array_merge(
            ['location' => $path],
            $inspection
        );
    }

    $archiveSources = [
        'archive:SQL_hackathon v4.sql' => ['./SQL_hackathon v4.sql', './BE/SQL_hackathon v4.sql', 'SQL_hackathon v4.sql', 'BE/SQL_hackathon v4.sql'],
        'archive:data.sql' => ['./data.sql', './BE/data.sql', 'data.sql', 'BE/data.sql'],
        'archive:BE/scripts/test_accounts.tsv' => ['./BE/scripts/test_accounts.tsv', 'BE/scripts/test_accounts.tsv'],
        'archive:BE/scripts/test_accounts_by_role.md' => ['./BE/scripts/test_accounts_by_role.md', 'BE/scripts/test_accounts_by_role.md'],
    ];

    foreach ($archiveSources as $label => $candidates) {
        try {
            $entry = VietnameseRecovery::readArchiveEntry($archivePath, $candidates);
            $inspection = VietnameseRecovery::inspectText($entry['raw']);
            if ($label === 'archive:data.sql' && $inspection['status'] === 'utf8_but_text_corrupted') {
                $inspection['status'] = 'unsafe_for_reseed';
            }
            $sources[$label] = array_merge(
                ['location' => $entry['path']],
                $inspection
            );
        } catch (Throwable $exception) {
            $sources[$label] = [
                'location' => $archivePath,
                'status' => 'missing',
                'reason' => $exception->getMessage(),
            ];
        }
    }

    return $sources;
}

/**
 * @return array<int, array<string, mixed>>
 */
function inspectSchema(PDO $pdo): array
{
    $targets = [
        ['table' => 'news', 'column' => 'status'],
        ['table' => 'news', 'column' => 'priority'],
        ['table' => 'notifications', 'column' => 'priority'],
        ['table' => 'employees', 'column' => 'gender'],
        ['table' => 'employees', 'column' => 'marital_status'],
    ];

    $issues = [];
    foreach ($targets as $target) {
        $stmt = $pdo->prepare(
            "SELECT COLUMN_TYPE, COLUMN_DEFAULT
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_NAME = :column"
        );
        $stmt->execute($target);
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($column)) {
            continue;
        }

        $columnType = (string) ($column['COLUMN_TYPE'] ?? '');
        $defaultValue = $column['COLUMN_DEFAULT'] === null ? null : (string) $column['COLUMN_DEFAULT'];
        $kind = VietnameseRecovery::detectCorruptionKind($columnType)
            ?? VietnameseRecovery::detectCorruptionKind($defaultValue);

        if ($kind === null) {
            continue;
        }

        $issues[] = [
            'table' => $target['table'],
            'column' => $target['column'],
            'column_type' => $columnType,
            'default' => $defaultValue,
            'corruption_kind' => $kind,
        ];
    }

    return $issues;
}

/**
 * @return array<string, array<string, mixed>>
 */
function monitoredDatasets(): array
{
    return [
        'employees.full_name' => [
            'table' => 'employees',
            'column' => 'full_name',
            'primary_key' => 'employee_id',
            'select' => ['employee_code'],
        ],
        'shift_types.shift_name' => [
            'table' => 'shift_types',
            'column' => 'shift_name',
            'primary_key' => 'shift_type_id',
            'select' => ['shift_code'],
        ],
        'leave_types.leave_type_name' => [
            'table' => 'leave_types',
            'column' => 'leave_type_name',
            'primary_key' => 'leave_type_id',
            'select' => ['leave_type_code'],
        ],
        'news.title' => [
            'table' => 'news',
            'column' => 'title',
            'primary_key' => 'news_id',
            'select' => ['news_code'],
        ],
        'news.summary' => [
            'table' => 'news',
            'column' => 'summary',
            'primary_key' => 'news_id',
            'select' => ['news_code'],
        ],
        'news.content' => [
            'table' => 'news',
            'column' => 'content',
            'primary_key' => 'news_id',
            'select' => ['news_code'],
        ],
        'news.status' => [
            'table' => 'news',
            'column' => 'status',
            'primary_key' => 'news_id',
            'select' => ['news_code'],
        ],
        'news.priority' => [
            'table' => 'news',
            'column' => 'priority',
            'primary_key' => 'news_id',
            'select' => ['news_code'],
        ],
        'notifications.title' => [
            'table' => 'notifications',
            'column' => 'title',
            'primary_key' => 'notification_id',
            'select' => ['notification_type'],
        ],
        'notifications.content' => [
            'table' => 'notifications',
            'column' => 'content',
            'primary_key' => 'notification_id',
            'select' => ['notification_type'],
        ],
        'notifications.priority' => [
            'table' => 'notifications',
            'column' => 'priority',
            'primary_key' => 'notification_id',
            'select' => ['notification_type'],
        ],
        'report_templates.template_name' => [
            'table' => 'report_templates',
            'column' => 'template_name',
            'primary_key' => 'template_id',
            'select' => ['template_code'],
        ],
        'report_templates.columns_config' => [
            'table' => 'report_templates',
            'column' => 'columns_config',
            'primary_key' => 'template_id',
            'select' => ['template_code'],
        ],
        'report_templates.sql_query' => [
            'table' => 'report_templates',
            'column' => 'sql_query',
            'primary_key' => 'template_id',
            'select' => ['template_code'],
        ],
        'contracts.work_location' => [
            'table' => 'contracts',
            'column' => 'work_location',
            'primary_key' => 'contract_id',
            'select' => ['contract_code'],
        ],
        'contracts.job_title' => [
            'table' => 'contracts',
            'column' => 'job_title',
            'primary_key' => 'contract_id',
            'select' => ['contract_code'],
        ],
    ];
}

/**
 * @param array<string, mixed> $dataset
 * @return array<int, array<string, mixed>>
 */
function fetchDatasetRows(PDO $pdo, array $dataset): array
{
    $columns = array_merge([$dataset['primary_key'], $dataset['column']], $dataset['select'] ?? []);
    $sql = sprintf(
        'SELECT %s FROM %s',
        implode(', ', array_map([VietnameseRecovery::class, 'quoteIdentifier'], $columns)),
        VietnameseRecovery::quoteIdentifier((string) $dataset['table'])
    );

    $stmt = $pdo->query($sql);
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * @param array<string, mixed> $dataset
 * @param array<string, mixed> $row
 */
function detectDatasetCorruption(array $dataset, array $row, mixed $value): ?string
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }

    $datasetKey = $dataset['table'] . '.' . $dataset['column'];
    if ($datasetKey === 'report_templates.sql_query') {
        $templateCode = (string) ($row['template_code'] ?? '');
        if ($templateCode !== 'RP_DEPARTMENT') {
            return null;
        }

        if (str_contains($value, '?ANG_L?M_VI?C')) {
            return 'literal_question_mark';
        }

        return null;
    }

    return VietnameseRecovery::detectCorruptionKind($value);
}

/**
 * @param array<string, mixed> $dataset
 * @param array<string, mixed> $row
 * @param array<string, array<string, mixed>> $sourceFiles
 * @return array<string, mixed>
 */
function buildAffectedRecord(
    string $datasetKey,
    array $dataset,
    array $row,
    mixed $value,
    string $corruptionKind,
    array $sourceFiles
): array {
    $recoverability = classifyRecordRecoverability($datasetKey, $row, $sourceFiles);

    return [
        'dataset' => $datasetKey,
        'table' => $dataset['table'],
        'column' => $dataset['column'],
        'primary_key' => $dataset['primary_key'],
        'primary_key_value' => $row[$dataset['primary_key']] ?? null,
        'current_value' => $value,
        'hex_value' => is_string($value) ? strtoupper(bin2hex($value)) : null,
        'corruption_kind' => $corruptionKind,
        'recoverability_class' => $recoverability['class'],
        'source_used' => $recoverability['source_used'],
        'reason' => $recoverability['reason'],
        'match_context' => array_intersect_key($row, array_flip($dataset['select'] ?? [])),
    ];
}

/**
 * @param array<string, mixed> $row
 * @param array<string, array<string, mixed>> $sourceFiles
 * @return array{class: string, source_used: ?string, reason: string}
 */
function classifyRecordRecoverability(string $datasetKey, array $row, array $sourceFiles): array
{
    return match ($datasetKey) {
        'employees.full_name' => classifyEmployeeRecoverability($row, $sourceFiles),
        'shift_types.shift_name' => [
            'class' => 'recoverable_from_deterministic_mapping',
            'source_used' => 'deterministic_mapping:shift_types',
            'reason' => 'safe_shift_code_mapping',
        ],
        'leave_types.leave_type_name' => [
            'class' => 'recoverable_from_deterministic_mapping',
            'source_used' => 'deterministic_mapping:leave_types',
            'reason' => 'safe_leave_type_code_mapping',
        ],
        'news.title', 'news.summary', 'news.content', 'news.status', 'news.priority' => [
            'class' => archiveSourceAvailable($sourceFiles, 'archive:data.sql')
                ? 'recoverable_from_mojibake_source'
                : 'unrecoverable_exactly',
            'source_used' => archiveSourceAvailable($sourceFiles, 'archive:data.sql') ? 'archive:data.sql' : null,
            'reason' => archiveSourceAvailable($sourceFiles, 'archive:data.sql')
                ? 'archived_insert_rows_can_be_decoded'
                : 'archive_data_sql_not_available',
        ],
        'notifications.title', 'notifications.content', 'notifications.priority' => [
            'class' => archiveSourceAvailable($sourceFiles, 'archive:data.sql')
                ? 'recoverable_from_mojibake_source'
                : 'unrecoverable_exactly',
            'source_used' => archiveSourceAvailable($sourceFiles, 'archive:data.sql') ? 'archive:data.sql' : null,
            'reason' => archiveSourceAvailable($sourceFiles, 'archive:data.sql')
                ? 'archived_insert_rows_can_be_decoded'
                : 'archive_data_sql_not_available',
        ],
        'report_templates.template_name', 'report_templates.columns_config', 'report_templates.sql_query' => [
            'class' => 'recoverable_from_deterministic_mapping',
            'source_used' => 'deterministic_mapping:report_templates',
            'reason' => 'safe_template_code_mapping',
        ],
        'contracts.work_location', 'contracts.job_title' => [
            'class' => 'unrecoverable_exactly',
            'source_used' => null,
            'reason' => 'original_vietnamese_lost_without_trusted_source',
        ],
        default => [
            'class' => 'unrecoverable_exactly',
            'source_used' => null,
            'reason' => 'no_recovery_policy',
        ],
    };
}

/**
 * @param array<string, mixed> $row
 * @param array<string, array<string, mixed>> $sourceFiles
 * @return array{class: string, source_used: ?string, reason: string}
 */
function classifyEmployeeRecoverability(array $row, array $sourceFiles): array
{
    $employeeCode = (string) ($row['employee_code'] ?? '');
    if ($employeeCode === '') {
        return [
            'class' => 'unrecoverable_exactly',
            'source_used' => null,
            'reason' => 'missing_employee_code',
        ];
    }

    $cleanReferenceAvailable = in_array(
        $employeeCode,
        array_merge(
            ['E999'],
            array_map(static fn (int $number): string => sprintf('NV%04d', $number), range(1, 20))
        ),
        true
    ) || str_starts_with($employeeCode, 'SEED');

    if ($cleanReferenceAvailable && ($sourceFiles['repo:BE/scripts/test_accounts.tsv']['status'] ?? null) !== 'missing') {
        return [
            'class' => 'recoverable_from_clean_source',
            'source_used' => 'repo:BE/scripts/test_accounts.tsv',
            'reason' => 'trusted_employee_reference_file',
        ];
    }

    if (preg_match('/^NV\d{4}$/', $employeeCode) === 1 && archiveSourceAvailable($sourceFiles, 'archive:data.sql')) {
        return [
            'class' => 'recoverable_from_mojibake_source',
            'source_used' => 'archive:data.sql',
            'reason' => 'archived_employee_row_can_be_decoded',
        ];
    }

    return [
        'class' => 'unrecoverable_exactly',
        'source_used' => null,
        'reason' => 'missing_trusted_employee_source',
    ];
}

/**
 * @param array<string, array<string, mixed>> $sourceFiles
 * @return array{class: string, source_used: ?string, reason: string}
 */
function classifyDatasetRecoverability(string $datasetKey, array $sourceFiles): array
{
    return match ($datasetKey) {
        'employees.full_name' => [
            'class' => archiveSourceAvailable($sourceFiles, 'archive:data.sql')
                ? 'mixed_clean_and_mojibake_sources'
                : 'partial_clean_source_only',
            'source_used' => 'repo:BE/scripts/test_accounts.tsv + archive:data.sql',
            'reason' => 'employee_recovery_depends_on_code_range',
        ],
        'shift_types.shift_name' => [
            'class' => 'recoverable_from_deterministic_mapping',
            'source_used' => 'deterministic_mapping:shift_types',
            'reason' => 'safe_shift_code_mapping',
        ],
        'leave_types.leave_type_name' => [
            'class' => 'recoverable_from_deterministic_mapping',
            'source_used' => 'deterministic_mapping:leave_types',
            'reason' => 'safe_leave_type_code_mapping',
        ],
        'news.title', 'news.summary', 'news.content', 'news.status', 'news.priority',
        'notifications.title', 'notifications.content', 'notifications.priority' => [
            'class' => archiveSourceAvailable($sourceFiles, 'archive:data.sql')
                ? 'recoverable_from_mojibake_source'
                : 'unrecoverable_exactly',
            'source_used' => archiveSourceAvailable($sourceFiles, 'archive:data.sql') ? 'archive:data.sql' : null,
            'reason' => archiveSourceAvailable($sourceFiles, 'archive:data.sql')
                ? 'archived_rows_available'
                : 'archive_data_sql_not_available',
        ],
        'report_templates.template_name', 'report_templates.columns_config', 'report_templates.sql_query' => [
            'class' => 'recoverable_from_deterministic_mapping',
            'source_used' => 'deterministic_mapping:report_templates',
            'reason' => 'template_codes_define_safe_replacements',
        ],
        'contracts.work_location', 'contracts.job_title' => [
            'class' => 'unrecoverable_exactly',
            'source_used' => null,
            'reason' => 'free_text_fields_have_no_trusted_source',
        ],
        default => [
            'class' => 'unknown',
            'source_used' => null,
            'reason' => 'no_dataset_policy',
        ],
    };
}

/**
 * @param array<string, array<string, mixed>> $sourceFiles
 */
function archiveSourceAvailable(array $sourceFiles, string $label): bool
{
    return ($sourceFiles[$label]['status'] ?? null) !== 'missing';
}

/**
 * @param array<string, mixed> $summary
 * @param array<int, array<string, mixed>> $affectedRows
 */
function buildMarkdownReport(array $summary, array $affectedRows): string
{
    $lines = [
        '# Vietnamese Data Audit',
        '',
        '## Runtime',
    ];

    foreach (($summary['runtime'] ?? []) as $key => $value) {
        $lines[] = sprintf('- `%s`: `%s`', (string) $key, (string) $value);
    }

    $lines[] = '';
    $lines[] = '## Decoder Fixtures';
    foreach (($summary['decoder_fixtures']['results'] ?? []) as $fixture) {
        $lines[] = sprintf(
            '- `%s` => `%s` [%s]',
            (string) ($fixture['input'] ?? ''),
            (string) ($fixture['actual'] ?? ''),
            (($fixture['ok'] ?? false) ? 'ok' : 'failed')
        );
    }

    $lines[] = '';
    $lines[] = '## Source Files';
    foreach (($summary['source_files'] ?? []) as $label => $info) {
        $lines[] = sprintf(
            '- `%s`: `%s`',
            (string) $label,
            (string) ($info['status'] ?? 'unknown')
        );
    }

    $lines[] = '';
    $lines[] = '## Dataset Summary';
    foreach (($summary['datasets'] ?? []) as $dataset) {
        $lines[] = sprintf(
            '- `%s`: `%d` affected, recoverability `%s`',
            (string) ($dataset['dataset'] ?? ''),
            (int) ($dataset['affected_rows'] ?? 0),
            (string) (($dataset['recoverability']['class'] ?? 'unknown'))
        );
    }

    $lines[] = '';
    $lines[] = '## Affected Records';
    foreach ($affectedRows as $record) {
        $lines[] = sprintf(
            '- `%s` `%s=%s` `%s`: `%s` -> `%s`',
            (string) ($record['table'] ?? ''),
            (string) ($record['primary_key'] ?? ''),
            (string) ($record['primary_key_value'] ?? ''),
            (string) ($record['column'] ?? ''),
            (string) ($record['corruption_kind'] ?? ''),
            (string) ($record['recoverability_class'] ?? '')
        );
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
}
