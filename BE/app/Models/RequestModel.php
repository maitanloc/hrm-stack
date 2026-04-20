<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class RequestModel extends Model
{
    protected string $table = 'requests';
    protected string $primaryKey = 'request_id';
    /** @var array<int, string>|null */
    private ?array $statusEnumCache = null;
    protected array $fillable = [
        'request_code',
        'request_type_id',
        'requester_id',
        'request_date',
        'from_date',
        'to_date',
        'duration',
        'reason',
        'status',
        'current_step_id',
        'is_urgent',
        'attachments',
        'notes',
        'completed_date',
        'created_by',
        'updated_by',
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
        ?string $search = null,
        ?string $status = null,
        ?array $requesterIds = null,
        ?int $requestTypeId = null
    ): array {
        $where = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $where[] = '(r.request_code LIKE :search OR r.reason LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }
        if ($status !== null && $status !== '') {
            $where[] = 'r.status = :status';
            $params['status'] = $status;
        }
        if (is_array($requesterIds) && $requesterIds !== []) {
            $in = [];
            foreach (array_values($requesterIds) as $idx => $id) {
                $key = 'requester_id_' . $idx;
                $in[] = ':' . $key;
                $params[$key] = (int) $id;
            }
            $where[] = 'r.requester_id IN (' . implode(', ', $in) . ')';
        }
        if ($requestTypeId !== null) {
            $where[] = 'r.request_type_id = :request_type_id';
            $params['request_type_id'] = $requestTypeId;
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT r.*,
                       rt.request_type_code,
                       rt.request_type_name,
                       e.full_name AS requester_name
                FROM requests r
                JOIN request_types rt ON rt.request_type_id = r.request_type_id
                JOIN employees e ON e.employee_id = r.requester_id
                $whereSql
                ORDER BY r.request_id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $countSql = "SELECT COUNT(*) AS total
                     FROM requests r
                     JOIN request_types rt ON rt.request_type_id = r.request_type_id
                     JOIN employees e ON e.employee_id = r.requester_id
                     $whereSql";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function findDetail(int $id): ?array
    {
        $sql = "SELECT r.*,
                       rt.request_type_code,
                       rt.request_type_name,
                       e.full_name AS requester_name
                FROM requests r
                JOIN request_types rt ON rt.request_type_id = r.request_type_id
                JOIN employees e ON e.employee_id = r.requester_id
                WHERE r.request_id = :id
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
        $default = $enumValues[0] ?? 'NHÁP';
        $token = $this->normalizeToken((string) $value);
        $index = match ($token) {
            'NHAP', 'DRAFT' => 0,
            'CHO_DUYET', 'PENDING', 'SUBMITTED' => 1,
            'DANG_XU_LY', 'PENDING_APPROVAL', 'IN_PROGRESS' => 2,
            'DA_DUYET', 'APPROVED' => 3,
            'TU_CHOI', 'REJECTED' => 4,
            'DA_HUY', 'CANCELLED', 'CANCELED' => 5,
            'HOAN_THANH', 'COMPLETED', 'DONE' => 6,
            default => 1,
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

        $fallback = ['NHÁP', 'CHỜ_DUYỆT', 'ĐANG_XỬ_LÝ', 'ĐÃ_DUYỆT', 'TỪ_CHỐI', 'ĐÃ_HỦY', 'HOÀN_THÀNH'];
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
