<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\ShiftAssignment;
use App\Models\ShiftAssignmentOverride;
use App\Models\ShiftSchedule;

class WorkScheduleResolverService
{
    public function __construct(
        private readonly ShiftAssignmentOverride $overrides = new ShiftAssignmentOverride(),
        private readonly ShiftAssignment $assignments = new ShiftAssignment(),
        private readonly ShiftSchedule $schedules = new ShiftSchedule(),
        private readonly Employee $employees = new Employee(),
    ) {
    }

    public function resolve(int $employeeId, string $workDate): ?array
    {
        $override = $this->overrides->findByEmployeeDate($employeeId, $workDate);
        if ($override !== null) {
            return $this->formatResolvedShift('OVERRIDE', $override, [
                'override_id' => $override['override_id'] ?? null,
                'reason' => $override['reason'] ?? null,
            ]);
        }

        $employeeAssignment = $this->assignments->findApplicableByDate($employeeId, $workDate);
        if ($employeeAssignment !== null) {
            return $this->formatResolvedShift('EMPLOYEE_DEFAULT', $employeeAssignment, [
                'assignment_id' => $employeeAssignment['assignment_id'] ?? null,
                'effective_date' => $employeeAssignment['effective_date'] ?? null,
                'expiry_date' => $employeeAssignment['expiry_date'] ?? null,
            ]);
        }

        $employment = $this->employees->findCurrentEmployment($employeeId);
        $departmentId = (int) ($employment['department_id'] ?? 0);
        if ($departmentId > 0) {
            $departmentSchedule = $this->schedules->findDepartmentShiftForDate($departmentId, $workDate);
            if ($departmentSchedule !== null) {
                return $this->formatResolvedShift('DEPARTMENT_SCHEDULE', $departmentSchedule, [
                    'schedule_id' => $departmentSchedule['schedule_id'] ?? null,
                    'schedule_name' => $departmentSchedule['schedule_name'] ?? null,
                    'day_of_week' => $departmentSchedule['day_of_week'] ?? null,
                ]);
            }
        }

        return null;
    }

    private function formatResolvedShift(string $source, array $row, array $meta = []): ?array
    {
        $shiftTypeId = isset($row['shift_type_id']) ? (int) $row['shift_type_id'] : 0;
        if ($shiftTypeId <= 0) {
            return null;
        }

        return [
            'source' => $source,
            'shift_type_id' => $shiftTypeId,
            'shift_code' => (string) ($row['shift_code'] ?? ''),
            'shift_name' => (string) ($row['shift_name'] ?? ''),
            'start_time' => $row['start_time'] ?? null,
            'end_time' => $row['end_time'] ?? null,
            'working_hours' => isset($row['working_hours']) ? (float) $row['working_hours'] : null,
            'is_night_shift' => !empty($row['is_night_shift']),
            'allow_overtime' => !empty($row['allow_overtime']),
            'color_code' => $row['color_code'] ?? null,
            'meta' => $meta,
        ];
    }
}
