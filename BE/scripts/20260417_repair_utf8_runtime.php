<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Core\TextEncoding;
use PDO;

/** @var PDO $pdo */
$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "[utf8-repair] Start\n";

$ddlStatements = [
    "ALTER TABLE requests
        MODIFY status ENUM('NHÁP', 'CHỜ_DUYỆT', 'ĐANG_XỬ_LÝ', 'ĐÃ_DUYỆT', 'TỪ_CHỐI', 'ĐÃ_HỦY', 'HOÀN_THÀNH')
        DEFAULT 'NHÁP'",

    "ALTER TABLE attendances
        MODIFY status ENUM('CHỜ_DUYỆT', 'ĐÃ_DUYỆT', 'TỪ_CHỐI', 'NHẬP_THỦ_CÔNG')
        DEFAULT 'CHỜ_DUYỆT'",

    "ALTER TABLE attendances
        MODIFY check_in_method ENUM('MÁY_QUÉT', 'MOBILE', 'MANUAL') NULL,
        MODIFY check_out_method ENUM('MÁY_QUÉT', 'MOBILE', 'MANUAL') NULL",

    "ALTER TABLE overtime_requests
        MODIFY status ENUM('CHỜ_DUYỆT', 'ĐÃ_DUYỆT', 'TỪ_CHỐI')
        DEFAULT 'CHỜ_DUYỆT'",

    "ALTER TABLE shift_assignments
        MODIFY status ENUM('HIỆU_LỰC', 'HẾT_HIỆU_LỰC', 'CHỜ_DUYỆT')
        DEFAULT 'HIỆU_LỰC'",

    "ALTER TABLE contracts
        MODIFY status ENUM('CÓ_HIỆU_LỰC', 'HẾT_HẠN', 'ĐÃ_CHẤM_DỨT', 'CHỜ_HIỆU_LỰC')
        DEFAULT 'CÓ_HIỆU_LỰC'",

    "ALTER TABLE employees
        MODIFY gender ENUM('NAM', 'NỮ', 'KHÁC') NULL,
        MODIFY marital_status ENUM('ĐỘC_THÂN', 'ĐÃ_KẾT_HÔN', 'LY_HÔN', 'GÓA') NULL,
        MODIFY status ENUM('ĐANG_LÀM_VIỆC', 'ĐÃ_NGHỈ_VIỆC', 'THỬ_VIỆC', 'NGHỈ_THAI_SẢN', 'TẠM_HOÃN_CÔNG_TÁC')
        DEFAULT 'ĐANG_LÀM_VIỆC'",

    "ALTER TABLE leave_requests
        MODIFY from_session ENUM('SÁNG', 'CHIỀU', 'CẢ_NGÀY') DEFAULT 'CẢ_NGÀY',
        MODIFY to_session ENUM('SÁNG', 'CHIỀU', 'CẢ_NGÀY') DEFAULT 'CẢ_NGÀY'",

    "ALTER TABLE request_types
        MODIFY category ENUM(
            'NGHỈ_PHÉP',
            'GỘP_PHÉP',
            'TĂNG_CA',
            'ĐIỀU_CHỈNH_CÔNG',
            'CÔNG_TÁC',
            'TẠM_ỨNG_LƯƠNG',
            'THANH_TOÁN',
            'KỶ_LUẬT',
            'ĐI_MUỘN_VỀ_SỚM',
            'SUẤT_ĂN',
            'KHÁC'
        ) NOT NULL",
];

foreach ($ddlStatements as $ddl) {
    try {
        $pdo->exec($ddl);
    } catch (\Throwable $e) {
        echo "[utf8-repair] DDL skip: " . $e->getMessage() . PHP_EOL;
    }
}

$textTargets = [
    ['table' => 'employees', 'pk' => 'employee_id', 'columns' => ['full_name']],
    ['table' => 'departments', 'pk' => 'department_id', 'columns' => ['department_name']],
    ['table' => 'positions', 'pk' => 'position_id', 'columns' => ['position_name']],
    ['table' => 'roles', 'pk' => 'role_id', 'columns' => ['role_name']],
    ['table' => 'leave_types', 'pk' => 'leave_type_id', 'columns' => ['leave_type_name', 'description']],
    ['table' => 'request_types', 'pk' => 'request_type_id', 'columns' => ['request_type_name', 'description']],
];

$totalUpdated = 0;

foreach ($textTargets as $target) {
    $table = $target['table'];
    $pk = $target['pk'];
    $columns = $target['columns'];
    $selectCols = implode(', ', array_merge([$pk], $columns));
    $rows = $pdo->query("SELECT {$selectCols} FROM {$table}")->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($rows as $row) {
        $updates = [];
        $params = ['pk' => $row[$pk]];

        foreach ($columns as $column) {
            $raw = $row[$column] ?? null;
            if (!is_string($raw) || $raw === '') {
                continue;
            }
            $fixed = TextEncoding::fixMojibake($raw);
            if (!is_string($fixed) || $fixed === $raw) {
                continue;
            }
            $updates[] = "{$column} = :{$column}";
            $params[$column] = $fixed;
        }

        if ($updates === []) {
            continue;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $updates) . " WHERE {$pk} = :pk";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $totalUpdated += $stmt->rowCount();
    }
}

echo "[utf8-repair] Updated rows: {$totalUpdated}\n";
echo "[utf8-repair] Done\n";
