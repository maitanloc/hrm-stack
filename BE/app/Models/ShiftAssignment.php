<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ShiftAssignment extends Model
{
    protected string $table = 'shift_assignments';
    protected string $primaryKey = 'assignment_id';
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

    public function findApplicableByDate(int $employeeId, string $workDate): ?array
    {
        $sql = "SELECT sa.*, st.shift_code, st.shift_name, st.start_time, st.end_time, st.is_night_shift,
                       st.allow_overtime, st.working_hours, st.color_code
                FROM shift_assignments sa
                JOIN shift_types st ON st.shift_type_id = sa.shift_type_id
                WHERE sa.employee_id = :employee_id
                  AND sa.status = 'HIỆU_LỰC'
                  AND sa.effective_date <= :work_date
                  AND (sa.expiry_date IS NULL OR sa.expiry_date >= :work_date)
                ORDER BY sa.effective_date DESC, sa.assignment_id DESC
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findByEmployeeEffectiveDate(int $employeeId, string $effectiveDate): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM shift_assignments WHERE employee_id = :employee_id AND effective_date = :effective_date LIMIT 1'
        );
        $stmt->execute([
            'employee_id' => $employeeId,
            'effective_date' => $effectiveDate,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function upsertForEmployeeDate(int $employeeId, string $effectiveDate, array $payload): int
    {
        $existing = $this->findByEmployeeEffectiveDate($employeeId, $effectiveDate);
        if ($existing !== null) {
            $this->updateById((int) $existing['assignment_id'], $payload);
            return (int) $existing['assignment_id'];
        }

        return $this->create($payload);
    }
}
