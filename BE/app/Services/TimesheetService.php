<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class TimesheetService
{
    public function __construct(
        private readonly PlanningContextService $planningContext = new PlanningContextService(),
        private readonly AttendanceResultService $attendanceResultService = new AttendanceResultService(),
    ) {
    }

    // -------------------------------------------------------------------------
    // Daily Timesheet Entry
    // -------------------------------------------------------------------------

    /**
     * Build a rich daily timesheet entry for one employee on one date.
     * Combines: shift, leave, OT request, attendance log, attendance_result.
     */
    public function buildDailyEntry(int $employeeId, string $workDate): array
    {
        $context   = $this->planningContext->build($employeeId, $workDate);
        $attendance = $this->findAttendance($employeeId, $workDate);
        $policy    = $this->loadPolicy();

        $shift        = $context['shift'];
        $holiday      = $context['holiday'];
        $leave        = $context['leave'];
        $businessTrip = $context['business_trip'];
        $remote       = $context['remote'];
        $overtime     = $context['overtime'];

        $gracePeriodMinutes = (int) ($policy['grace_period_minutes'] ?? 5);
        $halfDayThreshold   = (int) ($policy['half_day_threshold_minutes'] ?? 240);
        $fullDayMinutes     = (int) ($policy['full_day_minutes'] ?? 480);
        $minimumOtAfterShiftMinutes = (int) ($policy['overtime_minimum_after_shift_minutes'] ?? 30);

        $flags    = [];
        $status   = 'AB';
        $workType = 'NORMAL';
        $lateMinutes     = 0;
        $earlyOutMinutes = 0;
        $undertimeMinutes = 0;
        $workedMinutes   = 0;
        $overtimeMinutes = 0;
        $workingCoeff    = 0.0; // 0 = absent, 0.5 = half, 1.0 = full

        // --- Holiday ---
        if ($holiday !== null) {
            $workType = 'HOLIDAY';
            if ($attendance !== null) {
                $status = 'OT';
                $overtimeMinutes = $this->computeWorkedMinutes($attendance);
                $flags[] = 'WORKED_ON_HOLIDAY';
                $workingCoeff = 1.0;
            } else {
                $status = 'H';
                $workingCoeff = 1.0; // paid holiday
            }
        }
        // --- Approved Leave ---
        elseif ($leave !== null) {
            $status = $this->mapLeaveStatus($leave);
            $workType = 'LEAVE';
            $workingCoeff = $this->leaveWorkingCoeff($leave);
            if ($attendance !== null) {
                $flags[] = 'ATTENDED_WHILE_ON_LEAVE';
            }
        }
        // --- Business Trip ---
        elseif ($businessTrip !== null) {
            $status = 'CT';
            $workType = 'BUSINESS_TRIP';
            $workingCoeff = 1.0;
        }
        // --- Remote ---
        elseif ($remote !== null) {
            $status = 'REMOTE';
            $workType = 'REMOTE';
            $workingCoeff = 1.0;
            if ($attendance !== null) {
                $workedMinutes = $this->computeWorkedMinutes($attendance);
            }
        }
        // --- Has shift ---
        elseif ($shift !== null) {
            $isNightShift = !empty($shift['is_night_shift']);

            if ($attendance !== null) {
                $checkIn  = $attendance['check_in_time'] ?? null;
                $checkOut = $attendance['check_out_time'] ?? null;
                [$shiftStartTs, $shiftEndTs] = $this->shiftTimestamps($workDate, $shift);

                $workedMinutes = $this->computeWorkedMinutes($attendance, $shiftEndTs);

                // Late check-in
                if ($checkIn !== null && $shiftStartTs !== null) {
                    $rawLate = (int) floor((strtotime((string) $checkIn) - $shiftStartTs) / 60);
                    if ($rawLate > $gracePeriodMinutes) {
                        $lateMinutes = $rawLate;
                        $flags[] = 'LATE_CHECKIN';
                    }
                }

                // Early check-out
                if ($checkOut !== null && $shiftEndTs !== null) {
                    $rawEarly = (int) floor(($shiftEndTs - strtotime((string) $checkOut)) / 60);
                    if ($rawEarly > $gracePeriodMinutes) {
                        $earlyOutMinutes = $rawEarly;
                        $flags[] = 'EARLY_CHECKOUT';
                    }
                }

                // Missing checkout
                if ($checkIn !== null && $checkOut === null) {
                    $flags[] = 'MISSING_CHECKOUT';
                }

                // Working coefficient
                $undertimeMinutes = max(0, $fullDayMinutes - $workedMinutes + $lateMinutes + $earlyOutMinutes);
                if ($workedMinutes <= 0) {
                    $workingCoeff = 0.0;
                    $status = 'AB';
                } elseif ($workedMinutes < $halfDayThreshold || ($lateMinutes + $earlyOutMinutes) >= ($fullDayMinutes / 2)) {
                    $workingCoeff = 0.5;
                    $status = 'HD'; // Half Day
                } else {
                    $workingCoeff = 1.0;
                    $status = $isNightShift ? 'NS' : 'P';
                }

                // Override with LATE if late > threshold but not half-day
                if ($lateMinutes > 0 && $workingCoeff >= 1.0) {
                    $status = 'L';
                }
                if ($earlyOutMinutes > 0 && $workingCoeff >= 1.0 && $status === 'P') {
                    $status = 'EO';
                }
                if ($earlyOutMinutes > 0 && $lateMinutes > 0 && $workingCoeff >= 1.0) {
                    $status = 'L';
                    $flags[] = 'LATE_AND_EARLY';
                }
            } else {
                // No attendance log => absent
                $status = 'AB';
                $workingCoeff = 0.0;
                $flags[] = 'NO_ATTENDANCE_LOG';
            }

            // --- Approved Overtime ---
            if ($overtime !== null) {
                $otMinutes = $this->computeEligibleOtMinutes(
                    $attendance,
                    $overtime,
                    $shiftEndTs,
                    $minimumOtAfterShiftMinutes
                );
                if ($otMinutes > 0) {
                    $overtimeMinutes = $otMinutes;
                    $flags[] = 'OT_VALID';
                } elseif ($attendance === null) {
                    $flags[] = 'OT_NO_LOG';
                }
            }

            $workType = $isNightShift ? 'NIGHT_SHIFT' : 'NORMAL';
        } else {
            // No shift assigned
            $status = 'UNASSIGNED';
            $flags[] = 'NO_SHIFT';
        }

        // Persist result
        $resultPayload = [
            'employee_id'           => $employeeId,
            'work_date'             => $workDate,
            'resolved_shift_type_id'=> $shift['shift_type_id'] ?? null,
            'primary_status_code'   => $status,
            'late_minutes'          => $lateMinutes,
            'early_out_minutes'     => $earlyOutMinutes,
            'overtime_minutes'      => $overtimeMinutes,
            'is_holiday'            => $holiday !== null,
            'holiday_id'            => $holiday['holiday_id'] ?? null,
            'source_summary'        => json_encode([
                'shift_source'  => $shift['source'] ?? null,
                'holiday'       => $holiday,
                'leave_id'      => $leave['leave_request_id'] ?? null,
                'business_trip' => $businessTrip['request_id'] ?? null,
                'remote'        => $remote['request_id'] ?? null,
                'overtime_id'   => $overtime['overtime_id'] ?? null,
                'attendance_id' => $attendance['attendance_id'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
        $this->attendanceResultService->evaluateAndPersist($employeeId, $workDate);

        $exception = $this->hasBlockingFlags($flags);

        return [
            'employee_id'      => $employeeId,
            'work_date'        => $workDate,
            'shift'            => $shift,
            'holiday'          => $holiday,
            'leave'            => $leave,
            'business_trip'    => $businessTrip,
            'remote'           => $remote,
            'overtime'         => $overtime,
            'attendance'       => $attendance,
            'status'           => $status,
            'work_type'        => $workType,
            'working_coeff'    => $workingCoeff,
            'worked_minutes'   => $workedMinutes,
            'late_minutes'     => $lateMinutes,
            'early_out_minutes'=> $earlyOutMinutes,
            'undertime_minutes'=> $undertimeMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'flags'            => $flags,
            'exception'        => $exception,
        ];
    }

    // -------------------------------------------------------------------------
    // Period Timesheet (multiple employees, date range)
    // -------------------------------------------------------------------------

    /**
     * Build timesheet summary per employee for a period.
     */
    public function buildPeriodSummary(array $employeeIds, string $fromDate, string $toDate): array
    {
        $results = [];
        foreach ($employeeIds as $employeeId) {
            $employeeId = (int) $employeeId;
            $dailyRows  = [];
            $totals     = [
                'total_days'        => 0,
                'working_days'      => 0,
                'present_days'      => 0.0,
                'absent_days'       => 0,
                'leave_days'        => 0.0,
                'holiday_days'      => 0,
                'late_count'        => 0,
                'early_out_count'   => 0,
                'ot_minutes'        => 0,
                'late_minutes'      => 0,
                'undertime_minutes' => 0,
                'exception_count'   => 0,
            ];

            foreach ($this->iterateDates($fromDate, $toDate) as $workDate) {
                $entry = $this->buildDailyEntry($employeeId, $workDate);
                $dailyRows[] = $entry;

                $totals['total_days']++;

                switch ($entry['status']) {
                    case 'H':
                        $totals['holiday_days']++;
                        break;
                    case 'AL': case 'SL': case 'UNP':
                        $totals['leave_days'] += $entry['working_coeff'];
                        break;
                    case 'AB': case 'UNASSIGNED':
                        if ($entry['status'] === 'AB') {
                            $totals['absent_days']++;
                        }
                        break;
                    default:
                        $totals['working_days']++;
                        $totals['present_days'] += $entry['working_coeff'];
                }

                if ($entry['late_minutes'] > 0) {
                    $totals['late_count']++;
                    $totals['late_minutes'] += $entry['late_minutes'];
                }
                if ($entry['early_out_minutes'] > 0) {
                    $totals['early_out_count']++;
                }
                if ($entry['overtime_minutes'] > 0) {
                    $totals['ot_minutes'] += $entry['overtime_minutes'];
                }
                if ($entry['exception']) {
                    $totals['exception_count']++;
                }
                $totals['undertime_minutes'] += $entry['undertime_minutes'];
            }

            $results[] = [
                'employee_id' => $employeeId,
                'from_date'   => $fromDate,
                'to_date'     => $toDate,
                'totals'      => $totals,
                'daily'       => $dailyRows,
            ];
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // Exception Flags
    // -------------------------------------------------------------------------

    /**
     * Return all employees/days with exception flags in a date range.
     */
    public function buildExceptionList(array $employeeIds, string $fromDate, string $toDate): array
    {
        $exceptions = [];
        foreach ($employeeIds as $employeeId) {
            $employeeId = (int) $employeeId;
            foreach ($this->iterateDates($fromDate, $toDate) as $workDate) {
                $entry = $this->buildDailyEntry($employeeId, $workDate);
                if ($entry['exception']) {
                    $exceptions[] = [
                        'employee_id' => $employeeId,
                        'work_date'   => $workDate,
                        'status'      => $entry['status'],
                        'flags'       => $entry['flags'],
                        'late_minutes'=> $entry['late_minutes'],
                        'early_out_minutes' => $entry['early_out_minutes'],
                        'overtime_minutes'  => $entry['overtime_minutes'],
                    ];
                }
            }
        }
        return $exceptions;
    }

    // -------------------------------------------------------------------------
    // Payroll Export
    // -------------------------------------------------------------------------

    /**
     * Build payroll-ready export data: per employee, per period.
     */
    public function buildPayrollExport(array $employeeIds, string $fromDate, string $toDate): array
    {
        $rows = [];
        foreach ($employeeIds as $employeeId) {
            $employeeId  = (int) $employeeId;
            $emp = $this->findEmployee($employeeId);
            $summaries   = $this->buildPeriodSummary([$employeeId], $fromDate, $toDate);
            $summary     = $summaries[0] ?? null;
            if ($summary === null) {
                continue;
            }

            $totals = $summary['totals'];
            $rows[] = [
                'employee_id'       => $employeeId,
                'employee_code'     => $emp['employee_code'] ?? null,
                'full_name'         => $emp['full_name'] ?? null,
                'department_name'   => $emp['department_name'] ?? null,
                'position_name'     => $emp['position_name'] ?? null,
                'from_date'         => $fromDate,
                'to_date'           => $toDate,
                'working_days'      => $totals['working_days'],
                'present_days'      => $totals['present_days'],
                'absent_days'       => $totals['absent_days'],
                'leave_days'        => $totals['leave_days'],
                'holiday_days'      => $totals['holiday_days'],
                'late_count'        => $totals['late_count'],
                'late_minutes'      => $totals['late_minutes'],
                'early_out_count'   => $totals['early_out_count'],
                'ot_minutes'        => $totals['ot_minutes'],
                'ot_hours'          => round($totals['ot_minutes'] / 60, 2),
                'undertime_minutes' => $totals['undertime_minutes'],
                'exception_count'   => $totals['exception_count'],
                'ready_for_payroll' => $totals['exception_count'] === 0,
            ];
        }
        return $rows;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function shiftTimestamps(string $workDate, array $shift): array
    {
        $startTime = $shift['start_time'] ?? null;
        $endTime   = $shift['end_time']   ?? null;
        if ($startTime === null) {
            return [null, null];
        }

        $startTs = strtotime($workDate . ' ' . (string)$startTime);
        $endTs   = $endTime !== null ? strtotime($workDate . ' ' . (string)$endTime) : null;

        // Night shift end on next day
        if ($endTs !== null && !empty($shift['is_night_shift'])) {
            if ($endTs <= $startTs) {
                $endTs = strtotime($workDate . ' ' . (string)$endTime . ' +1 day');
            }
        }

        return [$startTs, $endTs];
    }

    private function computeWorkedMinutes(array $attendance, ?int $shiftEndTs = null): int
    {
        $checkIn  = $attendance['check_in_time']  ?? null;
        $checkOut = $attendance['check_out_time'] ?? null;
        if ($checkIn === null) {
            return 0;
        }

        $inTs  = strtotime((string) $checkIn);
        $outTs = $checkOut !== null
            ? strtotime((string) $checkOut)
            : ($shiftEndTs ?? time());

        return max(0, (int) floor(($outTs - $inTs) / 60));
    }

    private function computeOtMinutes(array $overtime): int
    {
        $start  = $overtime['start_time'] ?? null;
        $end    = $overtime['end_time']   ?? null;
        $breakM = isset($overtime['break_time']) ? (int) $overtime['break_time'] : 0;
        if ($start === null || $end === null) {
            return 0;
        }
        return max(0, (int) floor((strtotime((string)$end) - strtotime((string)$start)) / 60) - $breakM);
    }

    private function computeEligibleOtMinutes(
        ?array $attendance,
        array $overtime,
        ?int $shiftEndTs,
        int $minimumOtAfterShiftMinutes
    ): int {
        if ($attendance === null || $shiftEndTs === null) {
            return 0;
        }

        $checkOut = $attendance['check_out_time'] ?? null;
        if ($checkOut === null) {
            return 0;
        }

        $checkoutTs = strtotime((string) $checkOut);
        if ($checkoutTs === false || $checkoutTs <= $shiftEndTs) {
            return 0;
        }

        $actualOtMinutes = (int) floor(($checkoutTs - $shiftEndTs) / 60);
        if ($actualOtMinutes < $minimumOtAfterShiftMinutes) {
            return 0;
        }

        $approvedOtMinutes = $this->computeOtMinutes($overtime);
        if ($approvedOtMinutes <= 0) {
            return 0;
        }

        return min($approvedOtMinutes, $actualOtMinutes);
    }

    private function mapLeaveStatus(array $leave): string
    {
        $code = mb_strtoupper(trim((string)($leave['leave_type_code'] ?? '') . ' ' . (string)($leave['leave_type_name'] ?? '')), 'UTF-8');
        return match (true) {
            str_contains($code, 'SICK'), str_contains($code, 'ỐM')           => 'SL',
            str_contains($code, 'UNPAID'), str_contains($code, 'KHÔNG LƯƠNG') => 'UNP',
            default                                                            => 'AL',
        };
    }

    private function leaveWorkingCoeff(array $leave): float
    {
        $halfDay = !empty($leave['is_half_day']) || !empty($leave['half_day']);
        return $halfDay ? 0.5 : 1.0;
    }

    private function hasBlockingFlags(array $flags): bool
    {
        foreach ($flags as $flag) {
            if (in_array((string) $flag, ['OT_VALID', 'WORKED_ON_HOLIDAY'], true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function findAttendance(int $employeeId, string $workDate): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM attendances
             WHERE employee_id = :eid AND DATE(check_in_time) = :date
             ORDER BY attendance_id DESC LIMIT 1"
        );
        $stmt->execute(['eid' => $employeeId, 'date' => $workDate]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function loadPolicy(): array
    {
        $stmt = Database::connection()->query(
            "SELECT config_key, config_value FROM system_configs
             WHERE config_key IN ('grace_period_minutes','half_day_threshold_minutes','full_day_minutes','overtime_minimum_after_shift_minutes')"
        );
        $rows = $stmt->fetchAll();
        $policy = [];
        foreach ($rows as $row) {
            $policy[$row['config_key']] = $row['config_value'];
        }
        return $policy;
    }

    private function findEmployee(int $employeeId): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT e.employee_id, e.employee_code, e.full_name,
                    d.department_name, p.position_name
             FROM employees e
             LEFT JOIN employment_histories eh
                    ON eh.employee_id = e.employee_id
                   AND eh.is_current = 1
             LEFT JOIN departments d ON d.department_id = eh.department_id
             LEFT JOIN positions p ON p.position_id = eh.position_id
             WHERE e.employee_id = :eid LIMIT 1"
        );
        $stmt->execute(['eid' => $employeeId]);
        return $stmt->fetch() ?: [];
    }

    /** @return string[] */
    private function iterateDates(string $fromDate, string $toDate): array
    {
        $dates = [];
        $cur   = strtotime($fromDate);
        $end   = strtotime($toDate);
        while ($cur <= $end) {
            $dates[] = date('Y-m-d', $cur);
            $cur = strtotime('+1 day', $cur);
        }
        return $dates;
    }
}
