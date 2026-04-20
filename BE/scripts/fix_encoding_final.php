<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
use App\Core\Database;

$pdo = Database::connection();

echo "=== HRM DATA INTEGRITY REPAIR START ===\n";

// 1. Force UTF8MB4 for all core tables
$tables = ['employees', 'departments', 'positions', 'shift_types', 'shifts', 'contracts', 'requests', 'notifications', 'holidays'];
foreach ($tables as $table) {
    try {
        $pdo->exec("ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Table `$table` -> utf8mb4\n";
    } catch (Exception $e) {
        echo "❌ Skip `$table`: " . $e->getMessage() . "\n";
    }
}

// 2. Map Corrupted ENUMs and Names
// This is a heuristic repair for common Vietnamese patterns lost as '?'
$repairs = [
    'employees' => [
        'full_name' => [
            'Nguy?n' => 'Nguyễn',
            'Tr??n' => 'Trần',
            'Lê' => 'Lê', // Already ok
            'Hoàng' => 'Hoàng',
            'Ph?m' => 'Phạm',
            'V?' => 'Vũ',
            'Đặng' => 'Đặng',
            'Bùi' => 'Bùi',
            'Đỗ' => 'Đỗ',
            'Hồ' => 'Hồ',
            'Ngô' => 'Ngô'
        ],
        'status' => [
            '??NG L?M VI?C' => 'ĐANG_LÀM_VIỆC',
            'TH? VI?C' => 'THỬ_VIỆC'
        ]
    ],
    'shift_assignments' => [
        'status' => [
            'HI?U L?C' => 'HIỆU_LỰC'
        ]
    ],
    'requests' => [
        'status' => [
            '?? DUY?T' => 'ĐÃ_DUYỆT',
            'CH? DUY?T' => 'CHỜ_DUYỆT'
        ]
    ]
];

foreach ($repairs as $table => $config) {
    foreach ($config as $column => $mapping) {
        foreach ($mapping as $bad => $good) {
            $stmt = $pdo->prepare("UPDATE `$table` SET `$column` = REPLACE(`$column`, :bad, :good) WHERE `$column` LIKE :like");
            $stmt->execute([
                'bad' => $bad,
                'good' => $good,
                'like' => '%' . str_replace('?', '_', $bad) . '%'
            ]);
            if ($stmt->rowCount() > 0) {
                echo "🪄 Repaired $table.$column: $bad -> $good (" . $stmt->rowCount() . " rows)\n";
            }
        }
    }
}

echo "=== REPAIR COMPLETED ===\n";
