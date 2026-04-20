<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ShiftAssignment extends Model
{
    protected string $table = 'shift_assignments';
    protected string $primaryKey = 'assignment_id';
    /** @var array<int, string>|null */
    private ?array $statusEnumCache = null;
    protected array $fillable = [
        'employee_id',
        'shift_type_id',
        'effective_date',
        'expiry_date',
        'is_permanent',
        'assigned_by',
        'notes',
        'status',
    ];

    public function create(array $data): int
    {
        return parent::create($this->normalizePersistencePayload($data));
    }

    public function updateById(int $id, array $data): bool
    {
        return parent::updateById($id, $this->normalizePersistencePayload($data));
    }

    public function findApplicableByDate(int $employeeId, string $workDate): ?array
    {
        $activeStatus = $this->activeStatusValue();
        // Reset indices using array_values to match sequential placeholders
        $statusCandidates = array_values(array_unique([$activeStatus, 'HIỆU_LỰC', 'HIEU_LUC', 'HI?U L?C', 'HIỆU LỰC']));
        $placeholders = implode(', ', array_map(fn($i) => ":status_$i", range(0, count($statusCandidates) - 1)));

        $sql = "SELECT sa.*, st.shift_code, st.shift_name, st.start_time, st.end_time, st.is_night_shift,
                       st.allow_overtime, st.working_hours, st.color_code
                FROM shift_assignments sa
                JOIN shift_types st ON st.shift_type_id = sa.shift_type_id
                WHERE sa.employee_id = :employee_id
                  AND (sa.status IN ($placeholders) OR sa.status LIKE 'HI%U L%C')
                  AND sa.effective_date <= :work_date_from
                  AND (sa.expiry_date IS NULL OR sa.expiry_date >= :work_date_to)
                ORDER BY sa.effective_date DESC, sa.assignment_id DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $params = [
            'employee_id' => $employeeId,
            'work_date_from' => $workDate,
            'work_date_to' => $workDate,
        ];
        foreach ($statusCandidates as $i => $val) {
            $params["status_$i"] = $val;
        }

        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findByEmployeeEffectiveDate(int $employeeId, string $effectiveDate): ?array
    {
        $activeStatus = $this->activeStatusValue();
        $statusCandidates = array_values(array_unique([$activeStatus, 'HIỆU_LỰC', 'HIEU_LUC', 'HI?U L?C', 'HIỆU LỰC']));
        $placeholders = implode(', ', array_map(fn($i) => ":status_$i", range(0, count($statusCandidates) - 1)));

        $sql = "SELECT * FROM shift_assignments 
                WHERE employee_id = :employee_id 
                  AND effective_date = :effective_date 
                  AND (status IN ($placeholders) OR status LIKE 'HI%U L%C')
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $params = [
            'employee_id' => $employeeId,
            'effective_date' => $effectiveDate,
        ];
        foreach ($statusCandidates as $i => $val) {
            $params["status_$i"] = $val;
        }

        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function upsertForEmployeeDate(int $employeeId, string $effectiveDate, array $payload): int
    {
        // Try to find existing record for this date regardless of status first to avoid duplicate unique key issues if any
        $stmt = $this->db->prepare(
            'SELECT assignment_id FROM shift_assignments WHERE employee_id = :employee_id AND effective_date = :effective_date LIMIT 1'
        );
        $stmt->execute([
            'employee_id' => $employeeId,
            'effective_date' => $effectiveDate,
        ]);
        $existing = $stmt->fetch();

        if ($existing !== false) {
            $this->updateById((int) $existing['assignment_id'], $payload);
            return (int) $existing['assignment_id'];
        }

        return $this->create($payload);
    }

    private function normalizePersistencePayload(array $data): array
    {
        if (array_key_exists('status', $data)) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }

        return $data;
    }

    private function activeStatusValue(): string
    {
        $enumValues = $this->statusEnumValues();
        // Prioritize finding a value that looks like "HIỆU_LỰC"
        foreach ($enumValues as $val) {
            if ($this->normalizeToken($val) === 'HIEU_LUC') {
                return $val;
            }
        }
        return $enumValues[0] ?? 'HIỆU_LỰC';
    }

    private function normalizeStatus(mixed $value): string
    {
        $enumValues = $this->statusEnumValues();
        $default = $enumValues[0] ?? 'HIỆU_LỰC';
        $token = $this->normalizeToken((string) $value);
        $index = match ($token) {
            'HIEU_LUC' => 0,
            'HET_HIEU_LUC' => 1,
            'CHO_DUYET', 'PENDING' => 2,
            default => 0,
        };

        return $enumValues[$index] ?? $default;
    }

    /**
     * @return array<int, string>
     */
    private function statusEnumValues(): array
    {
        if ($this->statusEnumCache !== null) {
            return $this->statusEnumCache;
        }

        $fallback = ['HIỆU_LỰC', 'HẾT_HIỆU_LỰC', 'CHỜ_DUYỆT'];
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
        }

        $this->statusEnumCache = $fallback;
        return $this->statusEnumCache;
    }

    private function normalizeToken(string $value): string
    {
        $upper = mb_strtoupper(trim($value), 'UTF-8');
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
}
