<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Core\Request;

class DebugConfigController extends Controller
{
    public function index(Request $request): array
    {
        $config = require base_path('config/database.php');
        
        // Sanitize password for safety
        if (isset($config['password'])) {
            $config['password'] = $config['password'] ? '********' : '(empty)';
        }

        return $this->ok([
            'database_config' => $config,
            'env_db_host' => env('DB_HOST'),
            'getenv_db_host' => getenv('DB_HOST'),
            'server_db_host' => $_SERVER['DB_HOST'] ?? 'NOT SET',
            'env_db_database' => env('DB_DATABASE'),
            'base_path' => base_path(),
            'env_file_exists' => is_file(base_path('.env')),
        ], 'Runtime configuration debug');
    }

    public function repairDatabase(Request $request): array
    {
        $pdo = \App\Core\Database::connection();
        $dbName = env('DB_DATABASE', 'hrm_db');
        
        $results = [];

        // 1. Force Database Charset
        try {
            $pdo->exec("ALTER DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $results[] = "Database '{$dbName}' charset set to utf8mb4";
        } catch (\Exception $e) { $results[] = "DB charset error: " . $e->getMessage(); }

        // 2. Fix Table Charsets and ENUMs
        $ddl = [
            "ALTER TABLE shift_assignments CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            "ALTER TABLE shift_assignments MODIFY status ENUM('HIỆU_LỰC', 'HẾT_HIỆU_LỰC', 'CHỜ_DUYỆT') DEFAULT 'HIỆU_LỰC'",
            "UPDATE shift_assignments SET status = 'HIỆU_LỰC' WHERE status LIKE 'HI?U L?C' OR status LIKE 'HIEU_LUC'",
            
            "ALTER TABLE employees MODIFY status ENUM('ĐANG_LÀM_VIỆC', 'ĐÃ_NGHỈ_VIỆC', 'THỬ_VIỆC', 'NGHỈ_THAI_SẢN', 'TẠM_HOÃN_CÔNG_TÁC') DEFAULT 'ĐANG_LÀM_VIỆC'",
            "UPDATE employees SET status = 'ĐANG_LÀM_VIỆC' WHERE status LIKE 'DANG_LAM_VIEC' OR status LIKE '??NG L?M VI?C'",
            
            "ALTER TABLE requests MODIFY status ENUM('NHÁP', 'CHỜ_DUYỆT', 'ĐANG_XỬ_LÝ', 'ĐÃ_DUYỆT', 'TỪ_CHỐI', 'ĐÃ_HỦY', 'HOÀN_THÀNH') DEFAULT 'NHÁP'",
            "UPDATE requests SET status = 'ĐÃ_DUYỆT' WHERE status LIKE 'DA_DUYET' OR status LIKE '?? DUY?T'",
            "UPDATE requests SET status = 'CHỜ_DUYỆT' WHERE status LIKE 'CHO_DUYET' OR status LIKE 'CH? DUY?T'",

            "ALTER TABLE attendances CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            "ALTER TABLE attendances MODIFY status ENUM('CHỜ_DUYỆT', 'ĐÃ_DUYỆT', 'TỪ_CHỐI', 'NHẬP_THỦ_CÔNG') DEFAULT 'CHỜ_DUYỆT'",
            "ALTER TABLE attendances MODIFY check_in_method ENUM('MÁY_QUÉT', 'MOBILE', 'MANUAL') NULL",
            "ALTER TABLE attendances MODIFY check_out_method ENUM('MÁY_QUÉT', 'MOBILE', 'MANUAL') NULL",

            "ALTER TABLE request_types CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            "ALTER TABLE request_types MODIFY category ENUM('NGHỈ_PHÉP', 'GỘP_PHÉP', 'TĂNG_CA', 'ĐIỀU_CHỈNH_CÔNG', 'CÔNG_TÁC', 'TẠM_ỨNG_LƯƠNG', 'THANH_TOÁN', 'KỶ_LUẬT', 'ĐI_MUỘN_VỀ_SỚM', 'SUẤT_ĂN', 'KHÁC') NOT NULL",

            "ALTER TABLE leave_requests CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            "ALTER TABLE leave_requests MODIFY from_session ENUM('SÁNG', 'CHIỀU', 'CẢ_NGÀY') DEFAULT 'CẢ_NGÀY'",
            "ALTER TABLE leave_requests MODIFY to_session ENUM('SÁNG', 'CHIỀU', 'CẢ_NGÀY') DEFAULT 'CẢ_NGÀY'",
            
            "ALTER TABLE departments CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            "ALTER TABLE positions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        foreach ($ddl as $sql) {
            try {
                $pdo->exec($sql);
                $results[] = "DDL Success: " . substr($sql, 0, 50) . "...";
            } catch (\Exception $e) { $results[] = "DDL Error: " . $e->getMessage(); }
        }

        // 3. Fix Mojibake in Data
        $textTargets = [
            ['table' => 'employees', 'pk' => 'employee_id', 'columns' => ['full_name', 'current_address', 'permanent_address']],
            ['table' => 'departments', 'pk' => 'department_id', 'columns' => ['department_name', 'description']],
            ['table' => 'positions', 'pk' => 'position_id', 'columns' => ['position_name']],
            ['table' => 'leave_types', 'pk' => 'leave_type_id', 'columns' => ['leave_type_name']],
            ['table' => 'request_types', 'pk' => 'request_type_id', 'columns' => ['request_type_name']],
            ['table' => 'shifts', 'pk' => 'shift_type_id', 'columns' => ['shift_name']],
        ];

        $totalFixed = 0;
        foreach ($textTargets as $target) {
            $table = $target['table'];
            $pk = $target['pk'];
            $cols = $target['columns'];
            try {
                $stmt = $pdo->query("SELECT {$pk}, " . implode(', ', $cols) . " FROM {$table}");
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    $updates = [];
                    $params = ['pk' => $row[$pk]];
                    foreach ($cols as $col) {
                        $fixed = \App\Core\TextEncoding::fixMojibake($row[$col]);
                        if ($fixed !== $row[$col]) {
                            $updates[] = "{$col} = :{$col}";
                            $params[$col] = $fixed;
                        }
                    }
                    if ($updates !== []) {
                        $upSql = "UPDATE {$table} SET " . implode(', ', $updates) . " WHERE {$pk} = :pk";
                        $pdo->prepare($upSql)->execute($params);
                        $totalFixed += 1;
                    }
                }
            } catch (\Exception $e) { $results[] = "Fix Data Error ({$table}): " . $e->getMessage(); }
        }
        $results[] = "Total rows fixed for Mojibake: {$totalFixed}";

        return $this->ok($results, 'Database repair process completed');
    }
}
