<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceResult;

class AttendanceResultService
{
    public function __construct(
        private readonly PlanningContextService $planningContextService = new PlanningContextService(),
        private readonly Attendance $attendanceModel = new Attendance(),
        private readonly AttendanceResult $attendanceResultModel = new AttendanceResult(),
    ) {
    }

    public function evaluateAndPersist(int $employeeId, string $workDate): array
    {
        $context = $this->planningContextService->build($employeeId, $workDate);
        $attendance = $this->attendanceModel->findByEmployeeDate($employeeId, $workDate);
        $policy = $this->loadPolicy();

        $resolvedShift = $context['shift'];
        $holiday = $context['holiday'];
        $leave = $context['leave'];
        $businessTrip = $context['business_trip'];
        $remote = $context['remote'];
        $overtime = $context['overtime'];
        $gracePeriodMinutes = (int) ($policy['grace_period_minutes'] ?? 5);
        $minimumOtAfterShiftMinutes = (int) ($policy['overtime_minimum_after_shift_minutes'] ?? 30);

        $status = 'AB';
        $lateMinutes = 0;
        $earlyOutMinutes = 0;
        $overtimeMinutes = 0;

        if ($holiday !== null) {
            $status = $attendance !== null ? 'OT' : 'H';
        } elseif ($leave !== null) {
            $status = $this->mapLeaveStatus((string) ($leave['leave_type_code'] ?? ''), (string) ($leave['leave_type_name'] ?? ''));
        } elseif ($businessTrip !== null) {
            $status = 'CT';
        } elseif ($remote !== null) {
            $status = 'REMOTE';
        } elseif ($resolvedShift !== null && $attendance !== null) {
            $checkIn = $attendance['check_in_time'] ?? null;
            $checkOut = $attendance['check_out_time'] ?? null;
            $shiftStart = $resolvedShift['start_time'] ?? null;
            $shiftEnd = $resolvedShift['end_time'] ?? null;
            $shiftEndTimestamp = null;

            $status = !empty($resolvedShift['is_night_shift']) ? 'NS' : 'P';

            if ($checkIn !== null && $shiftStart !== null) {
                $rawLateMinutes = max(0, (int) floor((strtotime((string) $checkIn) - strtotime($workDate . ' ' . (string) $shiftStart)) / 60));
                if ($this->shouldUseRawLateMinutes($attendance)) {
                    if ($rawLateMinutes > 0) {
                        $lateMinutes = $rawLateMinutes;
                        $status = 'L';
                    }
                } elseif ($rawLateMinutes > $gracePeriodMinutes) {
                    $lateMinutes = $rawLateMinutes;
                    $status = 'L';
                }
            }

            if ($checkOut !== null && $shiftEnd !== null) {
                $shiftEndTimestamp = strtotime($workDate . ' ' . (string) $shiftEnd);
                if (!empty($resolvedShift['is_night_shift']) || ((string) $shiftEnd < (string) ($resolvedShift['start_time'] ?? '00:00:00'))) {
                    $shiftEndTimestamp = strtotime($workDate . ' ' . (string) $shiftEnd . ' +1 day');
                }
                $rawEarlyOutMinutes = max(0, (int) floor(($shiftEndTimestamp - strtotime((string) $checkOut)) / 60));
                if ($rawEarlyOutMinutes > $gracePeriodMinutes) {
                    $earlyOutMinutes = $rawEarlyOutMinutes;
                    $status = 'EO';
                }
            }

            if ($overtime !== null && ($resolvedShift['allow_overtime'] ?? false)) {
                $overtimeMinutes = $this->calculateEligibleOvertimeMinutes(
                    $attendance,
                    $overtime,
                    $shiftEndTimestamp,
                    $minimumOtAfterShiftMinutes
                );
                if ($overtimeMinutes > 0) {
                    $status = 'OT';
                }
            }
        } elseif ($resolvedShift === null) {
            $status = 'UNASSIGNED';
        }

        $result = [
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'resolved_shift_type_id' => $resolvedShift['shift_type_id'] ?? null,
            'primary_status_code' => $status,
            'late_minutes' => $lateMinutes,
            'early_out_minutes' => $earlyOutMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'is_holiday' => $holiday !== null,
            'holiday_id' => $holiday['holiday_id'] ?? null,
            'source_summary' => json_encode([
                'shift_source' => $resolvedShift['source'] ?? null,
                'holiday' => $holiday,
                'leave' => $leave ? [
                    'leave_request_id' => $leave['leave_request_id'] ?? null,
                    'leave_type_code' => $leave['leave_type_code'] ?? null,
                    'leave_type_name' => $leave['leave_type_name'] ?? null,
                ] : null,
                'business_trip' => $businessTrip ? [
                    'request_id' => $businessTrip['request_id'] ?? null,
                    'request_type_code' => $businessTrip['request_type_code'] ?? null,
                ] : null,
                'remote' => $remote ? [
                    'request_id' => $remote['request_id'] ?? null,
                    'request_type_code' => $remote['request_type_code'] ?? null,
                ] : null,
                'overtime' => $overtime ? [
                    'overtime_id' => $overtime['overtime_id'] ?? null,
                    'request_code' => $overtime['request_code'] ?? null,
                ] : null,
                'attendance_id' => $attendance['attendance_id'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        $this->attendanceResultModel->upsertByEmployeeDate($employeeId, $workDate, $result);
        return $result;
    }

    private function calculateOvertimeMinutes(array $overtime): int
    {
        $start = $overtime['start_time'] ?? null;
        $end = $overtime['end_time'] ?? null;
        $break = isset($overtime['break_time']) ? (int) $overtime['break_time'] : 0;
        if ($start === null || $end === null) {
            return 0;
        }

        $minutes = (int) floor((strtotime((string) $end) - strtotime((string) $start)) / 60) - $break;
        return max(0, $minutes);
    }

    private function calculateEligibleOvertimeMinutes(
        array $attendance,
        array $overtime,
        ?int $shiftEndTimestamp,
        int $minimumOtAfterShiftMinutes
    ): int {
        if ($shiftEndTimestamp === null) {
            return 0;
        }

        $checkOut = $attendance['check_out_time'] ?? null;
        if ($checkOut === null) {
            return 0;
        }

        $actualCheckoutTimestamp = strtotime((string) $checkOut);
        if ($actualCheckoutTimestamp === false || $actualCheckoutTimestamp <= $shiftEndTimestamp) {
            return 0;
        }

        $actualOtMinutes = (int) floor(($actualCheckoutTimestamp - $shiftEndTimestamp) / 60);
        if ($actualOtMinutes < $minimumOtAfterShiftMinutes) {
            return 0;
        }

        $approvedOtMinutes = $this->calculateOvertimeMinutes($overtime);
        if ($approvedOtMinutes <= 0) {
            return 0;
        }

        return min($approvedOtMinutes, $actualOtMinutes);
    }

    private function loadPolicy(): array
    {
        $stmt = \App\Core\Database::connection()->query(
            "SELECT config_key, config_value
             FROM system_configs
             WHERE config_key IN ('grace_period_minutes','overtime_minimum_after_shift_minutes')"
        );
        $rows = $stmt->fetchAll() ?: [];
        $policy = [];
        foreach ($rows as $row) {
            $policy[$row['config_key']] = $row['config_value'];
        }

        return $policy;
    }

    private function mapLeaveStatus(string $leaveTypeCode, string $leaveTypeName): string
    {
        $haystack = mb_strtoupper(trim($leaveTypeCode . ' ' . $leaveTypeName), 'UTF-8');
        return match (true) {
            str_contains($haystack, 'SICK'),
            str_contains($haystack, 'ỐM') => 'SL',
            str_contains($haystack, 'UNPAID'),
            str_contains($haystack, 'KHÔNG LƯƠNG') => 'UNP',
            default => 'AL',
        };
    }

    private function shouldUseRawLateMinutes(array $attendance): bool
    {
        $method = mb_strtoupper(trim((string) ($attendance['check_in_method'] ?? '')), 'UTF-8');
        return str_contains($method, 'FACE') || str_contains($method, 'KIOSK');
    }
}
