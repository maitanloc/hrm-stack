<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class RiskAlert extends Model
{
    protected string $table = 'risk_alerts';
    protected string $primaryKey = 'risk_alert_id';
    protected array $fillable = [
        'employee_id',
        'attendance_id',
        'attendance_type',
        'risk_level',
        'reason_code',
        'distance_m',
        'happened_at',
        'status',
        'details_json',
        'resolved_by',
        'resolved_at',
    ];

    public function paginateList(
        int $offset,
        int $limit,
        ?array $employeeIds = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $riskLevel = null,
        ?string $status = null
    ): array {
        $where = [];
        $params = [];

        if ($dateFrom !== null && $dateFrom !== '') {
            $where[] = 'ra.happened_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where[] = 'ra.happened_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }
        if ($riskLevel !== null && $riskLevel !== '') {
            $where[] = 'ra.risk_level = :risk_level';
            $params['risk_level'] = $riskLevel;
        }
        if ($status !== null && $status !== '') {
            $where[] = 'ra.status = :status';
            $params['status'] = $status;
        }
        if (is_array($employeeIds) && $employeeIds !== []) {
            $in = [];
            foreach (array_values($employeeIds) as $idx => $id) {
                $key = 'employee_id_' . $idx;
                $in[] = ':' . $key;
                $params[$key] = (int) $id;
            }
            $where[] = 'ra.employee_id IN (' . implode(', ', $in) . ')';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT ra.risk_alert_id AS id,
                       ra.employee_id,
                       e.full_name AS employee_name,
                       ra.attendance_type,
                       ra.risk_level,
                       ra.reason_code,
                       ra.happened_at,
                       ra.distance_m,
                       ra.status
                FROM risk_alerts ra
                JOIN employees e ON e.employee_id = ra.employee_id
                $whereSql
                ORDER BY ra.happened_at DESC, ra.risk_alert_id DESC
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
                     FROM risk_alerts ra
                     JOIN employees e ON e.employee_id = ra.employee_id
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

    public function resolveOpenAlertsForEmployee(int $employeeId, int $resolvedBy): void
    {
        $sql = "UPDATE risk_alerts
                SET status = 'RESOLVED',
                    resolved_by = :resolved_by,
                    resolved_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE employee_id = :employee_id
                  AND status = 'OPEN'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'resolved_by' => $resolvedBy,
        ]);
    }
}
