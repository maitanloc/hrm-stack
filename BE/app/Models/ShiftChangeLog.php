<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ShiftChangeLog extends Model
{
    protected string $table = 'shift_change_logs';
    protected string $primaryKey = 'change_log_id';
    protected array $fillable = [
        'employee_id',
        'work_date',
        'old_shift_type_id',
        'new_shift_type_id',
        'change_type',
        'changed_by',
        'changed_at',
        'reason',
    ];

    public function findLatestByEmployeeDate(int $employeeId, string $workDate): ?array
    {
        $sql = "SELECT scl.*, changer.full_name AS changed_by_name
                FROM shift_change_logs scl
                LEFT JOIN employees changer ON changer.employee_id = scl.changed_by
                WHERE scl.employee_id = :employee_id
                  AND scl.work_date = :work_date
                ORDER BY scl.changed_at DESC, scl.change_log_id DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
        ]);

        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findLatestAfterTimestamp(int $employeeId, string $workDate, string $changedAfter, array $excludedTypes = ['PUBLISH']): ?array
    {
        $params = [
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'changed_after' => $changedAfter,
        ];

        $sql = "SELECT scl.*, changer.full_name AS changed_by_name
                FROM shift_change_logs scl
                LEFT JOIN employees changer ON changer.employee_id = scl.changed_by
                WHERE scl.employee_id = :employee_id
                  AND scl.work_date = :work_date
                  AND scl.changed_at > :changed_after";

        if ($excludedTypes !== []) {
            $placeholders = [];
            foreach (array_values($excludedTypes) as $index => $type) {
                $key = 'excluded_type_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $type;
            }
            $sql .= " AND scl.change_type NOT IN (" . implode(', ', $placeholders) . ")";
        }

        $sql .= " ORDER BY scl.changed_at DESC, scl.change_log_id DESC
                  LIMIT 1";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'excluded_type_')) {
                $stmt->bindValue(':' . $key, (string) $value);
                continue;
            }

            $stmt->bindValue(':' . $key, $key === 'employee_id' ? (int) $value : (string) $value, $key === 'employee_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
