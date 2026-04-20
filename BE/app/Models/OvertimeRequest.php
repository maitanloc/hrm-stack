<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class OvertimeRequest extends Model
{
    protected string $table = 'overtime_requests';
    protected string $primaryKey = 'overtime_id';
    /** @var array<int, string>|null */
    private ?array $statusEnumCache = null;
    protected array $fillable = [
        'request_id',
        'employee_id',
        'overtime_date',
        'start_time',
        'end_time',
        'break_time',
        'reason',
        'approved_by',
        'approved_date',
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
            $where[] = 'ot.overtime_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where[] = 'ot.overtime_date <= :date_to';
            $params['date_to'] = $dateTo;
        }
        if ($status !== null && $status !== '') {
            $statusCandidates = array_unique([$status, 'ĐÃ_DUYỆT', 'DA_DUYET', 'CHỜ_DUYỆT', 'CHO_DUYET', 'TỪ_CHỐI', 'TU_CHOI']);
            $p = [];
            foreach ($statusCandidates as $i => $val) {
                $key = 'ot_st_cand_' . $i;
                $p[] = ':' . $key;
                $params[$key] = $val;
            }
            $where[] = 'ot.status IN (' . implode(', ', $p) . ')';
        }
        if (is_array($employeeIds) && $employeeIds !== []) {
            $in = [];
            foreach (array_values($employeeIds) as $idx => $id) {
                $key = 'employee_id_' . $idx;
                $in[] = ':' . $key;
                $params[$key] = (int) $id;
            }
            $where[] = 'ot.employee_id IN (' . implode(', ', $in) . ')';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT ot.*, e.employee_code, e.full_name, r.request_code
                FROM overtime_requests ot
                JOIN employees e ON e.employee_id = ot.employee_id
                JOIN requests r ON r.request_id = ot.request_id
                $whereSql
                ORDER BY ot.overtime_date DESC, ot.overtime_id DESC
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

        $countSql = "SELECT COUNT(*) AS total
                     FROM overtime_requests ot
                     JOIN employees e ON e.employee_id = ot.employee_id
                     JOIN requests r ON r.request_id = ot.request_id
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
        $sql = "SELECT ot.*, e.employee_code, e.full_name, r.request_code
                FROM overtime_requests ot
                JOIN employees e ON e.employee_id = ot.employee_id
                JOIN requests r ON r.request_id = ot.request_id
                WHERE ot.overtime_id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function normalizePersistencePayload(array $data): array
    {
        if (array_key_exists('status', $data)) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }

        return $data;
    }

    private function normalizeStatus(mixed $value): string
    {
        $enumValues = $this->statusEnumValues();
        $default = $enumValues[0] ?? 'CHỜ_DUYỆT';
        $token = $this->normalizeToken((string) $value);
        $index = match ($token) {
            'CHO_DUYET', 'PENDING' => 0,
            'DA_DUYET', 'APPROVED' => 1,
            'TU_CHOI', 'REJECTED' => 2,
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

        $fallback = ['CHỜ_DUYỆT', 'ĐÃ_DUYỆT', 'TỪ_CHỐI'];
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
