<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Core\TextEncoding;

$apply = in_array('--apply', $argv, true);

$targets = [
    'employees' => ['full_name', 'current_address', 'permanent_address', 'notes'],
    'departments' => ['department_name', 'description'],
    'positions' => ['position_name', 'description'],
    'notifications' => ['title', 'message'],
    'requests' => ['reason', 'notes'],
];

$pdo = Database::connection();
$updated = [];

foreach ($targets as $table => $columns) {
    $pk = resolvePrimaryKey($pdo, $table);
    if ($pk === null) {
        continue;
    }

    $selectColumns = array_merge([$pk], $columns);
    $sql = sprintf('SELECT %s FROM %s', implode(', ', $selectColumns), $table);
    $stmt = $pdo->query($sql);
    $rows = $stmt ? $stmt->fetchAll() : [];

    foreach ($rows as $row) {
        $changes = [];
        foreach ($columns as $column) {
            $original = $row[$column] ?? null;
            if (!is_string($original) || trim($original) === '') {
                continue;
            }

            $fixed = TextEncoding::fixMojibake($original);
            if ($fixed !== $original) {
                $changes[$column] = [
                    'before' => $original,
                    'after' => $fixed,
                ];
            }
        }

        if ($changes === []) {
            continue;
        }

        $id = $row[$pk];
        $updated[] = [
            'table' => $table,
            'id' => $id,
            'changes' => $changes,
        ];

        if ($apply) {
            applyChanges($pdo, $table, $pk, $id, $changes);
        }
    }
}

if ($updated === []) {
    echo "No mojibake candidates found.\n";
    exit(0);
}

foreach ($updated as $item) {
    echo sprintf("[%s] %s=%s\n", $item['table'], detectIdLabel($item['table']), (string) $item['id']);
    foreach ($item['changes'] as $column => $change) {
        echo sprintf("  - %s\n    before: %s\n    after : %s\n", $column, $change['before'], $change['after']);
    }
}

echo $apply
    ? "\nApplied fixes above. Run on staging/test first before production.\n"
    : "\nPreview only. Re-run with --apply to write repaired values.\n";

function resolvePrimaryKey(PDO $pdo, string $table): ?string
{
    $stmt = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table
           AND COLUMN_KEY = 'PRI'
         ORDER BY ORDINAL_POSITION
         LIMIT 1"
    );
    $stmt->execute(['table' => $table]);
    $column = $stmt->fetchColumn();
    return is_string($column) && $column !== '' ? $column : null;
}

function applyChanges(PDO $pdo, string $table, string $pk, mixed $id, array $changes): void
{
    $setParts = [];
    $params = ['pk' => $id];
    foreach ($changes as $column => $change) {
        $setParts[] = sprintf('%s = :%s', $column, $column);
        $params[$column] = $change['after'];
    }

    $sql = sprintf('UPDATE %s SET %s WHERE %s = :pk', $table, implode(', ', $setParts), $pk);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function detectIdLabel(string $table): string
{
    return match ($table) {
        'employees' => 'employee_id',
        'departments' => 'department_id',
        'positions' => 'position_id',
        'notifications' => 'notification_id',
        'requests' => 'request_id',
        default => 'id',
    };
}
