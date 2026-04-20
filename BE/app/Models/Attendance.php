<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class Attendance extends Model
{
    protected string $table = 'attendances';
    protected string $primaryKey = 'attendance_id';
    /** @var array<int, string>|null */
    private ?array $statusEnumCache = null;
    /** @var array<int, string>|null */
    private ?array $checkInMethodEnumCache = null;
    /** @var array<int, string>|null */
    private ?array $checkOutMethodEnumCache = null;
    protected array $fillable = [
        'employee_id',
        'attendance_date',
        'shift_type_id',
        'check_in_time',
        'check_out_time',
        'check_in_time_2',
        'check_out_time_2',
        'check_in_method',
        'check_out_method',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'work_type',
        'actual_working_hours',
        'overtime_hours',
        'late_minutes',
        'early_leave_minutes',
        'is_holiday',
        'is_overtime',
        'status',
        'notes',
        'approved_by',
        'approved_date',
    ];

    public function create(array $data): int
    {
        return parent::create($this->normalizePersistencePayload($data));
    }

    public function updateById(int $id, array $data): bool
    {
        return parent::updateById($id, $this->normalizePersistencePayload($data));
    }

    public function paginateList(
        int $offset,
        int $limit,
        ?array $employeeIds = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $status = null
    ): array {
        $where = [];
        $params = [];

        if ($dateFrom !== null && $dateFrom !== '') {
            $where[] = 'a.attendance_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where[] = 'a.attendance_date <= :date_to';
            $params['date_to'] = $dateTo;
        }
        if ($status !== null && $status !== '') {
            $statusCandidates = array_unique([$status, 'ĐÃ_DUYỆT', 'DA_DUYET', 'CHỜ_DUYỆT', 'CHO_DUYET', 'TỪ_CHỐI', 'TU_CHOI']);
            $placeholders = [];
            foreach ($statusCandidates as $i => $val) {
                $key = 'status_cand_' . $i;
                $placeholders[] = ':' . $key;
                $params[$key] = $val;
            }
            $where[] = 'a.status IN (' . implode(', ', $placeholders) . ')';
        }
        if (is_array($employeeIds) && $employeeIds !== []) {
            $in = [];
            foreach (array_values($employeeIds) as $idx => $id) {
                $key = 'employee_id_' . $idx;
                $in[] = ':' . $key;
                $params[$key] = (int) $id;
            }
            $where[] = 'a.employee_id IN (' . implode(', ', $in) . ')';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT a.*, e.employee_code, e.full_name, st.shift_code, st.shift_name
                FROM attendances a
                JOIN employees e ON e.employee_id = a.employee_id
                LEFT JOIN shift_types st ON st.shift_type_id = a.shift_type_id
                $whereSql
                ORDER BY a.attendance_date DESC, a.attendance_id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, (string) $value, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        // Count only on attendances table to avoid unnecessary join cost.
        $countSql = "SELECT COUNT(*) AS total
                     FROM attendances a
                     $whereSql";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $countStmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $countStmt->bindValue(':' . $key, (string) $value, PDO::PARAM_STR);
            }
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function findDetail(int $id): ?array
    {
        $sql = "SELECT a.*, e.employee_code, e.full_name, st.shift_code, st.shift_name
                FROM attendances a
                JOIN employees e ON e.employee_id = a.employee_id
                LEFT JOIN shift_types st ON st.shift_type_id = a.shift_type_id
                WHERE a.attendance_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findByEmployeeDate(int $employeeId, string $attendanceDate): ?array
    {
        $sql = "SELECT *
                FROM attendances
                WHERE employee_id = :employee_id
                  AND attendance_date = :attendance_date
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'attendance_date' => $attendanceDate,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function normalizePersistencePayload(array $data): array
    {
        if (array_key_exists('status', $data)) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }
        if (array_key_exists('check_in_method', $data)) {
            $data['check_in_method'] = $this->normalizeCheckMethod($data['check_in_method'], 'check_in_method');
        }
        if (array_key_exists('check_out_method', $data)) {
            $data['check_out_method'] = $this->normalizeCheckMethod($data['check_out_method'], 'check_out_method');
        }
        return $data;
    }

    private function normalizeCheckMethod(mixed $value, string $column): ?string
    {
        if ($value === null) {
            return null;
        }

        $enumValues = $this->methodEnumValues($column);
        $default = $enumValues[0] ?? 'MÁY_QUÉT';
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }
        if (in_array($raw, $enumValues, true)) {
            return $raw;
        }

        $token = $this->normalizeStatusToken($raw);
        $target = 'MACHINE';
        if (str_contains($token, 'MOBILE') || str_contains($token, 'GPS') || str_contains($token, 'PHONE')) {
            $target = 'MOBILE';
        } elseif (str_contains($token, 'MANUAL') || str_contains($token, 'NHAP') || str_contains($token, 'THU_CONG') || str_contains($token, 'ADMIN')) {
            $target = 'MANUAL';
        }

        $indexMap = [
            'MACHINE' => 0,
            'MOBILE' => 1,
            'MANUAL' => 2,
        ];

        $targetIndex = $indexMap[$target] ?? 0;
        if (isset($enumValues[$targetIndex])) {
            return $enumValues[$targetIndex];
        }

        return $default;
    }

    private function normalizeStatus(mixed $value): string
    {
        $enumValues = $this->statusEnumValues();
        $defaultStatus = $enumValues[0] ?? 'CHỜ_DUYỆT';

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $index = (int) $value;
            if ($index >= 1 && $index <= count($enumValues)) {
                return $enumValues[$index - 1];
            }
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return $defaultStatus;
        }
        if (in_array($raw, $enumValues, true)) {
            return $raw;
        }

        $token = $this->normalizeStatusToken($raw);
        $target = match ($token) {
            'DA_DUYET', 'APPROVED', 'CHECKIN_OK', 'CHECKOUT_OK', 'CHECKIN_OK_FLAGGED', 'CHECKOUT_OK_FLAGGED', 'VERIFIED', 'SUCCESS' => 'ĐÃ_DUYỆT',
            'TU_CHOI', 'REJECTED', 'ABSENT', 'BLOCKED', 'FAILED', 'ERROR' => 'TỪ_CHỐI',
            'NHAP_THU_CONG', 'MANUAL' => 'NHẬP_THỦ_CÔNG',
            'CHO_DUYET', 'PENDING', 'DRAFT', 'OPEN', 'NEW', 'PROCESSING' => 'CHỜ_DUYỆT',
            default => $defaultStatus,
        };

        if (in_array($target, $enumValues, true)) {
            return $target;
        }

        foreach ($enumValues as $enumValue) {
            if ($this->normalizeStatusToken($enumValue) === $token) {
                return $enumValue;
            }
        }

        return $defaultStatus;
    }

    /**
     * @return array<int, string>
     */
    private function statusEnumValues(): array
    {
        if ($this->statusEnumCache !== null) {
            return $this->statusEnumCache;
        }

        $fallback = ['CHỜ_DUYỆT', 'ĐÃ_DUYỆT', 'TỪ_CHỐI', 'NHẬP_THỦ_CÔNG'];

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'status'");
            $column = $stmt ? ($stmt->fetch() ?: null) : null;
            $type = (string) ($column['Type'] ?? $column['type'] ?? '');
            if (
                $type !== ''
                && preg_match('/^enum\\((.*)\\)$/i', $type, $matches) === 1
                && preg_match_all("/'((?:\\\\'|[^'])*)'/", $matches[1], $enumMatches) > 0
            ) {
                $values = array_map(
                    static fn(string $item): string => str_replace("\\'", "'", $item),
                    $enumMatches[1]
                );
                if ($values !== []) {
                    $this->statusEnumCache = array_values(array_unique($values));
                    return $this->statusEnumCache;
                }
            }
        } catch (\Throwable) {
            // Fall back to built-in statuses when schema lookup is unavailable.
        }

        $this->statusEnumCache = $fallback;
        return $this->statusEnumCache;
    }

    private function normalizeStatusToken(string $value): string
    {
        $upper = mb_strtoupper($value, 'UTF-8');
        $ascii = strtr($upper, [
            'À' => 'A', 'Á' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
            'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
            'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
            'Đ' => 'D',
            'È' => 'E', 'É' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
            'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
            'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
            'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
            'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
            'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',
        ]);

        $token = preg_replace('/[^A-Z0-9]+/', '_', $ascii) ?? '';
        return trim($token, '_');
    }

    /**
     * @return array<int, string>
     */
    private function methodEnumValues(string $column): array
    {
        $cacheName = $column === 'check_out_method' ? 'checkOutMethodEnumCache' : 'checkInMethodEnumCache';
        if ($this->{$cacheName} !== null) {
            return $this->{$cacheName};
        }

        $fallback = ['MÁY_QUÉT', 'MOBILE', 'MANUAL'];

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE '{$column}'");
            $columnMeta = $stmt ? ($stmt->fetch() ?: null) : null;
            $type = (string) ($columnMeta['Type'] ?? $columnMeta['type'] ?? '');
            if (
                $type !== ''
                && preg_match('/^enum\\((.*)\\)$/i', $type, $matches) === 1
                && preg_match_all("/'((?:\\\\'|[^'])*)'/", $matches[1], $enumMatches) > 0
            ) {
                $values = array_map(
                    static fn(string $item): string => str_replace("\\'", "'", $item),
                    $enumMatches[1]
                );
                if ($values !== []) {
                    $this->{$cacheName} = array_values(array_unique($values));
                    return $this->{$cacheName};
                }
            }
        } catch (\Throwable) {
            // Fall back to defaults when schema lookup is unavailable.
        }

        $this->{$cacheName} = $fallback;
        return $this->{$cacheName};
    }
}
