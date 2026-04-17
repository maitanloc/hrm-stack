<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class AttendanceResult extends Model
{
    protected string $table = 'attendance_results';
    protected string $primaryKey = 'attendance_result_id';
    protected array $fillable = [
        'employee_id',
        'work_date',
        'resolved_shift_type_id',
        'primary_status_code',
        'late_minutes',
        'early_out_minutes',
        'overtime_minutes',
        'is_holiday',
        'holiday_id',
        'source_summary',
        'generated_at',
    ];

    public function upsertByEmployeeDate(int $employeeId, string $workDate, array $payload): void
    {
        $sql = "INSERT INTO attendance_results (
                    employee_id,
                    work_date,
                    resolved_shift_type_id,
                    primary_status_code,
                    late_minutes,
                    early_out_minutes,
                    overtime_minutes,
                    is_holiday,
                    holiday_id,
                    source_summary,
                    generated_at
                ) VALUES (
                    :employee_id,
                    :work_date,
                    :resolved_shift_type_id,
                    :primary_status_code,
                    :late_minutes,
                    :early_out_minutes,
                    :overtime_minutes,
                    :is_holiday,
                    :holiday_id,
                    :source_summary,
                    :generated_at
                )
                ON DUPLICATE KEY UPDATE
                    resolved_shift_type_id = VALUES(resolved_shift_type_id),
                    primary_status_code = VALUES(primary_status_code),
                    late_minutes = VALUES(late_minutes),
                    early_out_minutes = VALUES(early_out_minutes),
                    overtime_minutes = VALUES(overtime_minutes),
                    is_holiday = VALUES(is_holiday),
                    holiday_id = VALUES(holiday_id),
                    source_summary = VALUES(source_summary),
                    generated_at = VALUES(generated_at)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'resolved_shift_type_id' => $payload['resolved_shift_type_id'] ?? null,
            'primary_status_code' => $payload['primary_status_code'] ?? 'P',
            'late_minutes' => (int) ($payload['late_minutes'] ?? 0),
            'early_out_minutes' => (int) ($payload['early_out_minutes'] ?? 0),
            'overtime_minutes' => (int) ($payload['overtime_minutes'] ?? 0),
            'is_holiday' => !empty($payload['is_holiday']) ? 1 : 0,
            'holiday_id' => $payload['holiday_id'] ?? null,
            'source_summary' => $payload['source_summary'] ?? null,
            'generated_at' => $payload['generated_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function findByEmployeeDate(int $employeeId, string $workDate): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ar.*, st.shift_code, st.shift_name, h.holiday_name
             FROM attendance_results ar
             LEFT JOIN shift_types st ON st.shift_type_id = ar.resolved_shift_type_id
             LEFT JOIN holidays h ON h.holiday_id = ar.holiday_id
             WHERE ar.employee_id = :employee_id
               AND ar.work_date = :work_date
             LIMIT 1"
        );
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function paginateList(int $offset, int $limit, ?array $employeeIds = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = [];
        $params = [];

        if ($dateFrom !== null && $dateFrom !== '') {
            $where[] = 'ar.work_date >= :date_from';
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where[] = 'ar.work_date <= :date_to';
            $params['date_to'] = $dateTo;
        }
        if (is_array($employeeIds) && $employeeIds !== []) {
            $in = [];
            foreach (array_values($employeeIds) as $idx => $id) {
                $key = 'employee_id_' . $idx;
                $in[] = ':' . $key;
                $params[$key] = (int) $id;
            }
            $where[] = 'ar.employee_id IN (' . implode(', ', $in) . ')';
        }

        $whereSql = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT ar.*, e.employee_code, e.full_name, st.shift_code, st.shift_name, h.holiday_name
                FROM attendance_results ar
                JOIN employees e ON e.employee_id = ar.employee_id
                LEFT JOIN shift_types st ON st.shift_type_id = ar.resolved_shift_type_id
                LEFT JOIN holidays h ON h.holiday_id = ar.holiday_id
                $whereSql
                ORDER BY ar.work_date DESC, ar.attendance_result_id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll() ?: [];

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS total FROM attendance_results ar $whereSql");
        foreach ($params as $key => $value) {
            $countStmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->fetch()['total'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }
}
