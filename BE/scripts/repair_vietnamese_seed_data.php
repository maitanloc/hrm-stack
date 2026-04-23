<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Core\TextEncoding;

$apply = in_array('--apply', $argv, true);
$repoRoot = dirname(__DIR__, 2);
$referencePath = __DIR__ . DIRECTORY_SEPARATOR . 'test_accounts.tsv';

$employeeReferences = loadEmployeeReference($referencePath);

$departmentReference = [
    'HCNS' => ['department_name' => 'Hành chính Nhân sự'],
    'KT' => ['department_name' => 'Kế toán'],
    'KD' => ['department_name' => 'Kinh doanh'],
    'IT' => ['department_name' => 'Công nghệ thông tin'],
    'MKT' => ['department_name' => 'Marketing'],
    'CSKH' => ['department_name' => 'Chăm sóc khách hàng'],
    'KHO' => ['department_name' => 'Quản lý kho'],
    'SX' => ['department_name' => 'Sản xuất'],
    'PHA' => ['department_name' => 'Pháp chế'],
    'QLDA' => ['department_name' => 'Quản lý dự án'],
    'KMD' => ['department_name' => 'Kinh doanh Miền Đông'],
    'KMT' => ['department_name' => 'Kinh doanh Miền Tây'],
    'KN' => ['department_name' => 'Kinh doanh Miền Bắc'],
    'HC1' => ['department_name' => 'Hành chính Hà Nội'],
    'HC2' => ['department_name' => 'Hành chính Đà Nẵng'],
    'DAISO' => ['department_name' => 'Đào tạo về ISO'],
];

$positionReference = [
    'GD' => ['position_name' => 'Giám đốc'],
    'PGD' => ['position_name' => 'Phó Giám đốc'],
    'TP' => ['position_name' => 'Trưởng phòng'],
    'PP' => ['position_name' => 'Phó phòng'],
    'CV' => ['position_name' => 'Chuyên viên'],
    'NV' => ['position_name' => 'Nhân viên'],
    'TV' => ['position_name' => 'Thực tập viên'],
    'KTV' => ['position_name' => 'Kiểm toán viên'],
    'KTVC' => ['position_name' => 'Kế toán trưởng'],
    'KTTH' => ['position_name' => 'Kế toán tổng hợp'],
    'NS' => ['position_name' => 'Nhân sự'],
    'HC' => ['position_name' => 'Hành chính'],
    'LD' => ['position_name' => 'Lập trình viên'],
    'TL' => ['position_name' => 'Thủ kho'],
    'BV' => ['position_name' => 'Bảo vệ'],
];

$roleReference = [
    'ADMIN' => [
        'role_name' => 'Quản trị viên',
        'description' => 'Toàn quyền hệ thống',
    ],
    'HR' => [
        'role_name' => 'Nhân sự',
        'description' => 'Quản lý nhân sự, tuyển dụng, đào tạo',
    ],
    'MANAGER' => [
        'role_name' => 'Quản lý',
        'description' => 'Quản lý phòng ban, duyệt đơn',
    ],
    'EMPLOYEE' => [
        'role_name' => 'Nhân viên',
        'description' => 'Quyền cơ bản',
    ],
    'ACCOUNTANT' => [
        'role_name' => 'Kế toán',
        'description' => 'Quản lý lương, thanh toán',
    ],
];

$requestTypeReference = [
    'NP' => [
        'request_type_name' => 'Đơn xin nghỉ phép',
        'description' => 'Đơn xin nghỉ phép năm',
        'category' => 'NGHỈ_PHÉP',
    ],
    'GP' => [
        'request_type_name' => 'Đơn xin gộp phép',
        'description' => 'Đơn xin gộp phép tồn sang năm sau',
        'category' => 'GỘP_PHÉP',
    ],
    'TC' => [
        'request_type_name' => 'Đơn đăng ký tăng ca',
        'description' => 'Đơn đăng ký làm thêm giờ',
        'category' => 'TĂNG_CA',
    ],
    'CT' => [
        'request_type_name' => 'Đơn đăng ký công tác',
        'description' => 'Đơn đăng ký đi công tác',
        'category' => 'CÔNG_TÁC',
    ],
    'TUL' => [
        'request_type_name' => 'Đơn tạm ứng lương',
        'description' => 'Đơn xin tạm ứng lương',
        'category' => 'TẠM_ỨNG_LƯƠNG',
    ],
];

$preRepairDdls = [
    "ALTER TABLE employees MODIFY status VARCHAR(64) NULL",
    "ALTER TABLE requests MODIFY status VARCHAR(64) NULL",
    "ALTER TABLE shift_assignments MODIFY status VARCHAR(64) NULL",
    "ALTER TABLE contracts MODIFY status VARCHAR(64) NULL",
    "ALTER TABLE leave_requests MODIFY from_session VARCHAR(32) NULL",
    "ALTER TABLE leave_requests MODIFY to_session VARCHAR(32) NULL",
    "ALTER TABLE request_types MODIFY category VARCHAR(64) NOT NULL",
    "ALTER TABLE overtime_requests MODIFY status VARCHAR(64) NULL",
];

$postRepairDdls = [
    "ALTER TABLE employees MODIFY status ENUM('ĐANG_LÀM_VIỆC', 'ĐÃ_NGHỈ_VIỆC', 'THỬ_VIỆC', 'NGHỈ_THAI_SẢN', 'TẠM_HOÃN_CÔNG_TÁC') DEFAULT 'ĐANG_LÀM_VIỆC'",
    "ALTER TABLE requests MODIFY status ENUM('NHÁP', 'CHỜ_DUYỆT', 'ĐANG_XỬ_LÝ', 'ĐÃ_DUYỆT', 'TỪ_CHỐI', 'ĐÃ_HỦY', 'HOÀN_THÀNH') DEFAULT 'NHÁP'",
    "ALTER TABLE shift_assignments MODIFY status ENUM('HIỆU_LỰC', 'HẾT_HIỆU_LỰC', 'CHỜ_DUYỆT') DEFAULT 'HIỆU_LỰC'",
    "ALTER TABLE contracts MODIFY status ENUM('CÓ_HIỆU_LỰC', 'HẾT_HẠN', 'ĐÃ_CHẤM_DỨT', 'CHỜ_HIỆU_LỰC') DEFAULT 'CÓ_HIỆU_LỰC'",
    "ALTER TABLE leave_requests MODIFY from_session ENUM('SÁNG', 'CHIỀU', 'CẢ_NGÀY') DEFAULT 'CẢ_NGÀY'",
    "ALTER TABLE leave_requests MODIFY to_session ENUM('SÁNG', 'CHIỀU', 'CẢ_NGÀY') DEFAULT 'CẢ_NGÀY'",
    "ALTER TABLE request_types MODIFY category ENUM('NGHỈ_PHÉP', 'GỘP_PHÉP', 'TĂNG_CA', 'ĐIỀU_CHỈNH_CÔNG', 'CÔNG_TÁC', 'TẠM_ỨNG_LƯƠNG', 'THANH_TOÁN', 'KỶ_LUẬT', 'ĐI_MUỘN_VỀ_SỚM', 'SUẤT_ĂN', 'KHÁC') NOT NULL",
    "ALTER TABLE overtime_requests MODIFY status ENUM('CHỜ_DUYỆT', 'ĐÃ_DUYỆT', 'TỪ_CHỐI') DEFAULT 'CHỜ_DUYỆT'",
];

$exactValueMappings = [
    ['table' => 'employees', 'column' => 'status', 'map' => [
        '?ANG_L?M_VI?C' => 'ĐANG_LÀM_VIỆC',
        '??_NGH?_VI?C' => 'ĐÃ_NGHỈ_VIỆC',
        'TH?_VI?C' => 'THỬ_VIỆC',
        'NGH?_THAI_S?N' => 'NGHỈ_THAI_SẢN',
        'T?M_HO?N_CÔNG_TÁC' => 'TẠM_HOÃN_CÔNG_TÁC',
    ]],
    ['table' => 'requests', 'column' => 'status', 'map' => [
        'NH?P' => 'NHÁP',
        'CH?_DUY?T' => 'CHỜ_DUYỆT',
        '?ANG_X?_L?' => 'ĐANG_XỬ_LÝ',
        '??_DUY?T' => 'ĐÃ_DUYỆT',
        'T?_CH?I' => 'TỪ_CHỐI',
        '??_H?Y' => 'ĐÃ_HỦY',
        'HO?N_TH?NH' => 'HOÀN_THÀNH',
    ]],
    ['table' => 'attendances', 'column' => 'check_in_method', 'map' => [
        'M?Y_QU?T' => 'MÁY_QUÉT',
    ]],
    ['table' => 'attendances', 'column' => 'check_out_method', 'map' => [
        'M?Y_QU?T' => 'MÁY_QUÉT',
    ]],
    ['table' => 'shift_assignments', 'column' => 'status', 'map' => [
        'HI?U_L?C' => 'HIỆU_LỰC',
        'H?T_HI?U_L?C' => 'HẾT_HIỆU_LỰC',
        'CH?_DUY?T' => 'CHỜ_DUYỆT',
    ]],
    ['table' => 'contracts', 'column' => 'status', 'map' => [
        'C?_HI?U_L?C' => 'CÓ_HIỆU_LỰC',
        'H?T_H?N' => 'HẾT_HẠN',
        '??_CH?M_D?T' => 'ĐÃ_CHẤM_DỨT',
        'CH?_HI?U_L?C' => 'CHỜ_HIỆU_LỰC',
    ]],
    ['table' => 'leave_requests', 'column' => 'from_session', 'map' => [
        'S?NG' => 'SÁNG',
        'CHI?U' => 'CHIỀU',
        'C?_NG?Y' => 'CẢ_NGÀY',
    ]],
    ['table' => 'leave_requests', 'column' => 'to_session', 'map' => [
        'S?NG' => 'SÁNG',
        'CHI?U' => 'CHIỀU',
        'C?_NG?Y' => 'CẢ_NGÀY',
    ]],
    ['table' => 'overtime_requests', 'column' => 'status', 'map' => [
        'CH?_DUY?T' => 'CHỜ_DUYỆT',
        '??_DUY?T' => 'ĐÃ_DUYỆT',
        'T?_CH?I' => 'TỪ_CHỐI',
    ]],
];

$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$operations = [];
$totalChanged = 0;

try {
    if ($apply) {
        $totalChanged += applyDdlStatements($pdo, $preRepairDdls, $operations, 'pre_schema');
    }

    $totalChanged += syncEmployees($pdo, $employeeReferences, $operations, $apply);
    $totalChanged += syncLookupByCode($pdo, 'departments', 'department_code', $departmentReference, $operations, $apply);
    $totalChanged += syncLookupByCode($pdo, 'positions', 'position_code', $positionReference, $operations, $apply);
    $totalChanged += syncLookupByCode($pdo, 'roles', 'role_code', $roleReference, $operations, $apply);
    $totalChanged += syncLookupByCode($pdo, 'request_types', 'request_type_code', $requestTypeReference, $operations, $apply);

    foreach ($exactValueMappings as $mapping) {
        $totalChanged += applyExactValueMap(
            $pdo,
            $mapping['table'],
            $mapping['column'],
            $mapping['map'],
            $operations,
            $apply
        );
    }

    if ($apply) {
        $totalChanged += applyDdlStatements($pdo, $postRepairDdls, $operations, 'post_schema');
    }
} catch (\Throwable $exception) {
    fwrite(STDERR, "Repair failed: " . $exception->getMessage() . PHP_EOL);
    exit(1);
}

echo "[repair_vietnamese_seed_data]\n";
echo "mode=" . ($apply ? 'apply' : 'preview') . PHP_EOL;
echo "reference_file=" . relativePath($repoRoot, $referencePath) . PHP_EOL;
echo "total_changes=" . $totalChanged . PHP_EOL;

if ($operations === []) {
    echo "No deterministic repairs found.\n";
    exit(0);
}

foreach ($operations as $operation) {
    echo sprintf(
        "%s.%s | key=%s | before=%s | after=%s\n",
        $operation['table'],
        $operation['column'],
        $operation['key'],
        $operation['before'],
        $operation['after']
    );
}

echo $apply
    ? "\nApplied deterministic repairs only. Rows with lost `?` and no reference truth still require manual correction.\n"
    : "\nPreview only. Re-run with --apply to persist deterministic repairs.\n";

function loadEmployeeReference(string $path): array
{
    if (!is_file($path)) {
        throw new \RuntimeException("Missing employee reference file: {$path}");
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new \RuntimeException("Cannot read employee reference file: {$path}");
    }

    if (!mb_check_encoding($raw, 'UTF-8')) {
        throw new \RuntimeException("Employee reference file is not valid UTF-8: {$path}");
    }

    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
    $lines = preg_split("/\r\n|\n|\r/", trim($raw));
    if (!is_array($lines) || count($lines) < 2) {
        throw new \RuntimeException("Employee reference file is empty: {$path}");
    }

    $headers = array_map('trim', str_getcsv((string) array_shift($lines), "\t"));
    $references = [];

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

        $employeeCode = trim((string) ($row['employee_code'] ?? ''));
        $fullName = trim((string) ($row['full_name'] ?? ''));
        $status = trim((string) ($row['employee_status'] ?? ''));
        if ($employeeCode === '' || $fullName === '') {
            continue;
        }

        $references[$employeeCode] = [
            'full_name' => $fullName,
            'status' => is_string(TextEncoding::fixMojibake($status)) ? (string) TextEncoding::fixMojibake($status) : $status,
        ];
    }

    return $references;
}

function syncEmployees(\PDO $pdo, array $references, array &$operations, bool $apply): int
{
    $stmt = $pdo->query("SELECT employee_code, full_name, status FROM employees");
    $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    $changed = 0;

    $update = $pdo->prepare(
        "UPDATE employees
         SET full_name = :full_name,
             status = :status
         WHERE employee_code = :employee_code"
    );

    foreach ($rows as $row) {
        $employeeCode = trim((string) ($row['employee_code'] ?? ''));
        if ($employeeCode === '' || !isset($references[$employeeCode])) {
            continue;
        }

        $reference = $references[$employeeCode];
        foreach (['full_name', 'status'] as $column) {
            $before = (string) ($row[$column] ?? '');
            $after = (string) ($reference[$column] ?? '');
            if ($after === '' || $before === $after) {
                continue;
            }

            $operations[] = [
                'table' => 'employees',
                'column' => $column,
                'key' => $employeeCode,
                'before' => $before,
                'after' => $after,
            ];
            $changed++;
        }

        if ($apply && (
            (string) ($row['full_name'] ?? '') !== (string) $reference['full_name']
            || (string) ($row['status'] ?? '') !== (string) $reference['status']
        )) {
            $update->execute([
                'employee_code' => $employeeCode,
                'full_name' => (string) $reference['full_name'],
                'status' => (string) $reference['status'],
            ]);
        }
    }

    return $changed;
}

function syncLookupByCode(
    \PDO $pdo,
    string $table,
    string $codeColumn,
    array $reference,
    array &$operations,
    bool $apply
): int {
    $stmt = $pdo->query(sprintf("SELECT * FROM `%s`", $table));
    $rows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    $changed = 0;

    foreach ($rows as $row) {
        $code = trim((string) ($row[$codeColumn] ?? ''));
        if ($code === '' || !isset($reference[$code])) {
            continue;
        }

        $changes = [];
        foreach ($reference[$code] as $column => $after) {
            $before = (string) ($row[$column] ?? '');
            if ($before === $after) {
                continue;
            }

            $changes[$column] = $after;
            $operations[] = [
                'table' => $table,
                'column' => $column,
                'key' => $code,
                'before' => $before,
                'after' => $after,
            ];
            $changed++;
        }

        if ($apply && $changes !== []) {
            $setParts = [];
            $params = ['code' => $code];
            foreach ($changes as $column => $value) {
                $setParts[] = sprintf("`%s` = :%s", $column, $column);
                $params[$column] = $value;
            }

            $sql = sprintf(
                "UPDATE `%s` SET %s WHERE `%s` = :code",
                $table,
                implode(', ', $setParts),
                $codeColumn
            );
            $pdo->prepare($sql)->execute($params);
        }
    }

    return $changed;
}

function applyExactValueMap(
    \PDO $pdo,
    string $table,
    string $column,
    array $map,
    array &$operations,
    bool $apply
): int {
    $stmt = $pdo->query(
        sprintf(
            "SELECT DISTINCT `%s`
             FROM `%s`
             WHERE `%s` IS NOT NULL",
            $column,
            $table,
            $column
        )
    );
    $values = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];
    $changed = 0;

    foreach ($values as $value) {
        if (!is_string($value) || $value === '' || !array_key_exists($value, $map)) {
            continue;
        }

        $after = $map[$value];
        if ($after === $value) {
            continue;
        }

        $countStmt = $pdo->prepare(
            sprintf(
                "SELECT COUNT(*) FROM `%s` WHERE `%s` = :value",
                $table,
                $column
            )
        );
        $countStmt->execute(['value' => $value]);
        $affected = (int) $countStmt->fetchColumn();

        if ($affected === 0) {
            continue;
        }

        $operations[] = [
            'table' => $table,
            'column' => $column,
            'key' => sprintf('%d rows', $affected),
            'before' => $value,
            'after' => $after,
        ];
        $changed += $affected;

        if ($apply) {
            $update = $pdo->prepare(
                sprintf(
                    "UPDATE `%s` SET `%s` = :after WHERE `%s` = :before",
                    $table,
                    $column,
                    $column
                )
            );
            $update->execute([
                'before' => $value,
                'after' => $after,
            ]);
        }
    }

    return $changed;
}

function applyDdlStatements(\PDO $pdo, array $statements, array &$operations, string $phase): int
{
    $applied = 0;

    foreach ($statements as $statement) {
        $pdo->exec($statement);
        $operations[] = [
            'table' => 'schema',
            'column' => $phase,
            'key' => (string) (++$applied),
            'before' => '-',
            'after' => $statement,
        ];
    }

    return $applied;
}

function relativePath(string $repoRoot, string $path): string
{
    if (str_starts_with($path, $repoRoot . DIRECTORY_SEPARATOR)) {
        return str_replace($repoRoot . DIRECTORY_SEPARATOR, '', $path);
    }

    return $path;
}
