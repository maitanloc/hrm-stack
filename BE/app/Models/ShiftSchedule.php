<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ShiftSchedule extends Model
{
    protected string $table = 'shift_schedules';
    protected string $primaryKey = 'schedule_id';
    protected array $fillable = [
        'schedule_code',
        'schedule_name',
        'department_id',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    public function findDepartmentShiftForDate(int $departmentId, string $workDate): ?array
    {
        $phpDayOfWeek = (int) date('N', strtotime($workDate));
        $dayKeys = [
            (string) $phpDayOfWeek,
            strtoupper((string) date('D', strtotime($workDate))),
            strtoupper((string) date('l', strtotime($workDate))),
            match ($phpDayOfWeek) {
                1 => 'MONDAY',
                2 => 'TUESDAY',
                3 => 'WEDNESDAY',
                4 => 'THURSDAY',
                5 => 'FRIDAY',
                6 => 'SATURDAY',
                default => 'SUNDAY',
            },
        ];

        $sql = "SELECT ss.schedule_id, ss.schedule_code, ss.schedule_name, ss.department_id,
                       ssd.day_of_week, ssd.is_holiday, st.shift_type_id, st.shift_code, st.shift_name,
                       st.start_time, st.end_time, st.is_night_shift, st.allow_overtime, st.working_hours, st.color_code
                FROM shift_schedules ss
                JOIN shift_schedule_details ssd ON ssd.schedule_id = ss.schedule_id
                LEFT JOIN shift_types st ON st.shift_type_id = ssd.shift_type_id
                WHERE ss.department_id = :department_id
                  AND ss.is_active = 1
                  AND ss.effective_from <= :work_date_from
                  AND (ss.effective_to IS NULL OR ss.effective_to >= :work_date_to)
                ORDER BY ss.effective_from DESC, ss.schedule_id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'department_id' => $departmentId,
            'work_date_from' => $workDate,
            'work_date_to' => $workDate,
        ]);

        foreach ($stmt->fetchAll() ?: [] as $row) {
            $normalized = strtoupper(trim((string) ($row['day_of_week'] ?? '')));
            if (in_array($normalized, $dayKeys, true)) {
                return $row;
            }
        }

        return null;
    }
}
