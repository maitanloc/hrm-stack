<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ShiftAssignmentOverride extends Model
{
    protected string $table = 'shift_assignment_overrides';
    protected string $primaryKey = 'override_id';
    protected array $fillable = [
        'employee_id',
        'work_date',
        'shift_type_id',
        'reason',
        'changed_by',
        'changed_at',
        'notes',
    ];

    public function findByEmployeeDate(int $employeeId, string $workDate): ?array
    {
        $sql = "SELECT sao.*, st.shift_code, st.shift_name, st.start_time, st.end_time, st.is_night_shift,
                       st.allow_overtime, st.working_hours, st.color_code
                FROM shift_assignment_overrides sao
                LEFT JOIN shift_types st ON st.shift_type_id = sao.shift_type_id
                WHERE sao.employee_id = :employee_id
                  AND sao.work_date = :work_date
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function upsertForEmployeeDate(int $employeeId, string $workDate, array $payload): int
    {
        $existing = $this->findByEmployeeDate($employeeId, $workDate);
        if ($existing !== null) {
            $this->updateById((int) $existing['override_id'], $payload);
            return (int) $existing['override_id'];
        }

        return $this->create($payload);
    }
}
