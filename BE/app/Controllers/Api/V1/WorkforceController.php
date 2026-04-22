<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Validator;
use App\Models\AttendanceResult;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Notification;
use App\Models\SchedulePublishLog;
use App\Models\ShiftAssignment;
use App\Models\ShiftAssignmentOverride;
use App\Models\ShiftChangeLog;
use App\Models\ShiftType;
use App\Services\AttendanceResultService;
use App\Services\PayrollLockService;
use App\Services\PlanningContextService;
use App\Services\WorkflowAuditService;
use App\Services\WorkflowRuleEngineService;

class WorkforceController extends Controller
{
    private PlanningContextService $planningContextService;
    private AttendanceResultService $attendanceResultService;
    private AttendanceResult $attendanceResults;
    private Holiday $holidays;
    private Employee $employees;
    private Notification $notifications;
    private ShiftType $shiftTypes;
    private ShiftAssignment $shiftAssignments;
    private ShiftAssignmentOverride $shiftOverrides;
    private SchedulePublishLog $schedulePublishLogs;
    private ShiftChangeLog $shiftChangeLogs;
    private WorkflowRuleEngineService $workflowRuleEngine;
    private PayrollLockService $payrollLockService;
    private WorkflowAuditService $workflowAuditService;

    public function __construct()
    {
        $this->planningContextService = new PlanningContextService();
        $this->attendanceResultService = new AttendanceResultService();
        $this->attendanceResults = new AttendanceResult();
        $this->holidays = new Holiday();
        $this->employees = new Employee();
        $this->notifications = new Notification();
        $this->shiftTypes = new ShiftType();
        $this->shiftAssignments = new ShiftAssignment();
        $this->shiftOverrides = new ShiftAssignmentOverride();
        $this->schedulePublishLogs = new SchedulePublishLog();
        $this->shiftChangeLogs = new ShiftChangeLog();
        $this->workflowRuleEngine = new WorkflowRuleEngineService();
        $this->payrollLockService = new PayrollLockService();
        $this->workflowAuditService = new WorkflowAuditService();
    }

    public function myShiftToday(Request $request): array
    {
        $authUser = $this->authUser($request);
        $date = $this->normalizeDate((string) ($request->query('date', date('Y-m-d')) ?? date('Y-m-d')));
        $context = $this->planningContextService->build((int) $authUser['employee_id'], $date, true);

        return $this->ok([
            'employee_id' => (int) $authUser['employee_id'],
            'work_date' => $date,
            'shift' => $context['shift'],
            'holiday' => $context['holiday'],
            'leave' => $context['leave'],
            'business_trip' => $context['business_trip'],
            'remote' => $context['remote'],
            'overtime' => $context['overtime'],
        ], 'Today shift resolved');
    }

    public function mySchedule(Request $request): array
    {
        $authUser = $this->authUser($request);
        [$fromDate, $toDate] = $this->dateRange($request);

        $items = [];
        foreach ($this->iterateDates($fromDate, $toDate) as $workDate) {
            $context = $this->planningContextService->build((int) $authUser['employee_id'], $workDate, true);
            $items[] = [
                'employee_id' => (int) $authUser['employee_id'],
                'work_date' => $workDate,
                'shift' => $context['shift'],
                'holiday' => $context['holiday'],
                'leave' => $context['leave'],
                'business_trip' => $context['business_trip'],
                'remote' => $context['remote'],
                'overtime' => $context['overtime'],
            ];
        }

        return $this->ok($items, 'My work schedule', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ]);
    }

    public function teamSchedule(Request $request): array
    {
        $authUser = $this->authUser($request);
        [$fromDate, $toDate] = $this->dateRange($request);
        $departmentFilter = $request->query('department_id');
        $departmentId = is_numeric((string) $departmentFilter) ? (int) $departmentFilter : null;
        $items = $this->buildTeamScheduleItems($authUser, $fromDate, $toDate, $departmentId, true, true);

        return $this->ok($items, 'Team schedule', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'row_mode' => 'FULL_MATRIX',
            'record_count' => count($items),
        ]);
    }

    public function attendanceResultIndex(Request $request): array
    {
        $authUser = $this->authUser($request);
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(200, (int) $request->query('per_page', 50)));
        $offset = ($page - 1) * $limit;
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $employeeIds = Auth::isPrivileged($authUser)
            ? null
            : array_values(array_unique(array_map('intval', array_merge(
                [(int) $authUser['employee_id']],
                $authUser['hierarchy_employee_ids'] ?? []
            ))));

        $resultPage = $this->attendanceResults->paginateList(
            $offset,
            $limit,
            $employeeIds,
            is_string($dateFrom) ? $dateFrom : null,
            is_string($dateTo) ? $dateTo : null
        );

        return $this->ok($resultPage['items'], 'Attendance result list', [
            'total' => $resultPage['total'],
            'page' => $page,
            'per_page' => $limit,
        ]);
    }

    public function holidayIndex(Request $request): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(200, (int) $request->query('per_page', 50)));
        $offset = ($page - 1) * $perPage;
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $data = $this->holidays->paginateList($offset, $perPage, is_string($dateFrom) ? $dateFrom : null, is_string($dateTo) ? $dateTo : null);
        return $this->ok($data['items'], 'Holiday list', [
            'total' => $data['total'],
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function holidayShow(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $holiday = $this->holidays->find($id);
        if ($holiday === null) {
            throw new HttpException('Holiday not found', 404, 'not_found');
        }

        return $this->ok($holiday, 'Holiday detail');
    }

    public function holidayStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'holiday_name' => ['required', 'string'],
            'holiday_date' => ['required', 'date'],
            'holiday_type' => ['required', 'string'],
            'is_recurring' => ['boolean'],
            'year' => ['integer'],
            'paid_holiday' => ['boolean'],
            'salary_multiplier' => ['numeric'],
            'allowance_amount' => ['numeric'],
            'description' => ['string'],
        ]);
        $holidayType = $this->normalizeHolidayType((string) $payload['holiday_type']);

        $id = $this->holidays->create([
            'holiday_name' => $payload['holiday_name'],
            'holiday_date' => $payload['holiday_date'],
            'holiday_type' => $holidayType,
            'is_recurring' => !empty($payload['is_recurring']) ? 1 : 0,
            'year' => $payload['year'] ?? (int) substr((string) $payload['holiday_date'], 0, 4),
            'paid_holiday' => array_key_exists('paid_holiday', $payload) ? (!empty($payload['paid_holiday']) ? 1 : 0) : 1,
            'salary_multiplier' => $payload['salary_multiplier'] ?? 1,
            'allowance_amount' => $payload['allowance_amount'] ?? 0,
            'description' => $payload['description'] ?? null,
        ]);

        $holiday = $this->holidays->find($id);
        if ($holiday !== null) {
            $this->broadcastHolidayNotification($holiday, 'created', (int) ($request->attribute('auth_user')['employee_id'] ?? 0));
        }

        return $this->created($holiday, 'Holiday created');
    }

    public function holidayUpdate(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->holidays->find($id) === null) {
            throw new HttpException('Holiday not found', 404, 'not_found');
        }

        $payload = Validator::validate($request->all(), [
            'holiday_name' => ['string'],
            'holiday_date' => ['date'],
            'holiday_type' => ['string'],
            'is_recurring' => ['boolean'],
            'year' => ['integer'],
            'paid_holiday' => ['boolean'],
            'salary_multiplier' => ['numeric'],
            'allowance_amount' => ['numeric'],
            'description' => ['string'],
        ]);

        if ($payload === []) {
            throw new HttpException('No valid fields to update', 422, 'validation_error');
        }

        if (array_key_exists('is_recurring', $payload)) {
            $payload['is_recurring'] = !empty($payload['is_recurring']) ? 1 : 0;
        }
        if (array_key_exists('paid_holiday', $payload)) {
            $payload['paid_holiday'] = !empty($payload['paid_holiday']) ? 1 : 0;
        }
        if (array_key_exists('holiday_type', $payload)) {
            $payload['holiday_type'] = $this->normalizeHolidayType((string) $payload['holiday_type']);
        }

        $this->holidays->updateById($id, $payload);
        $holiday = $this->holidays->find($id);
        if ($holiday !== null) {
            $this->broadcastHolidayNotification($holiday, 'updated', (int) ($request->attribute('auth_user')['employee_id'] ?? 0));
        }

        return $this->ok($holiday, 'Holiday updated');
    }

    public function holidayDelete(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->holidays->find($id);
        if ($existing === null) {
            throw new HttpException('Holiday not found', 404, 'not_found');
        }

        $this->holidays->deleteById($id);
        $this->broadcastHolidayNotification($existing, 'deleted', (int) ($request->attribute('auth_user')['employee_id'] ?? 0));
        return $this->noContent('Holiday deleted');
    }

    public function shiftCatalog(Request $request): array
    {
        return $this->ok($this->shiftTypes->listActive(), 'Shift catalog');
    }

    public function assignShift(Request $request): array
    {
        $authUser = $this->authUser($request);
        $payload = Validator::validate($request->all(), [
            'employee_id' => ['required', 'integer'],
            'shift_type_id' => ['required', 'integer'],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['date'],
            'is_permanent' => ['boolean'],
            'notes' => ['string'],
        ]);

        $employeeId = (int) $payload['employee_id'];
        $shiftTypeId = (int) $payload['shift_type_id'];
        $effectiveDate = $this->normalizeDate((string) $payload['effective_date']);
        // Fix: Default expiry_date to effective_date to prevent shift from carrying forward indefinitely in single assignment
        $expiryDate = isset($payload['expiry_date']) ? $this->normalizeDate((string) $payload['expiry_date']) : $effectiveDate;
        $this->assertDateRangeNotLocked($effectiveDate, $expiryDate ?? $effectiveDate, 'assign shift');

        $this->assertCanManageEmployee($authUser, $employeeId);
        $employee = $this->employees->findWithDepartment($employeeId);
        if ($employee === null) {
            throw new HttpException('Employee not found', 404, 'not_found');
        }
        $shift = $this->shiftTypes->find($shiftTypeId);
        if ($shift === null) {
            throw new HttpException('Shift not found', 404, 'not_found');
        }

        $existing = $this->shiftAssignments->findByEmployeeEffectiveDate($employeeId, $effectiveDate);
        $assignmentId = $this->shiftAssignments->upsertForEmployeeDate($employeeId, $effectiveDate, [
            'employee_id' => $employeeId,
            'shift_type_id' => $shiftTypeId,
            'effective_date' => $effectiveDate,
            'expiry_date' => $expiryDate,
            'is_permanent' => array_key_exists('is_permanent', $payload) ? (!empty($payload['is_permanent']) ? 1 : 0) : ($expiryDate === null ? 1 : 0),
            'assigned_by' => (int) $authUser['employee_id'],
            'notes' => $payload['notes'] ?? null,
            'status' => 'HIỆU_LỰC',
        ]);

        $this->shiftChangeLogs->create([
            'employee_id' => $employeeId,
            'work_date' => $effectiveDate,
            'old_shift_type_id' => $existing['shift_type_id'] ?? null,
            'new_shift_type_id' => $shiftTypeId,
            'change_type' => 'ASSIGN',
            'changed_by' => (int) $authUser['employee_id'],
            'changed_at' => date('Y-m-d H:i:s'),
            'reason' => 'Manager assigned shift',
        ]);

        $result = $this->attendanceResultService->evaluateAndPersist($employeeId, $effectiveDate);
        $this->workflowAuditService->recordEvent(
            'SCHEDULE_ASSIGNMENT',
            (string) $assignmentId,
            'ASSIGN_SHIFT',
            (int) $authUser['employee_id'],
            [
                'employee_id' => $employeeId,
                'shift_type_id' => $shiftTypeId,
                'effective_date' => $effectiveDate,
                'expiry_date' => $expiryDate,
            ]
        );

        return $this->ok([
            'assignment_id' => $assignmentId,
            'employee' => [
                'employee_id' => $employeeId,
                'employee_code' => $employee['employee_code'] ?? null,
                'full_name' => $employee['full_name'] ?? null,
            ],
            'shift' => [
                'shift_type_id' => $shiftTypeId,
                'shift_code' => $shift['shift_code'] ?? null,
                'shift_name' => $shift['shift_name'] ?? null,
            ],
            'effective_date' => $effectiveDate,
            'expiry_date' => $expiryDate,
            'attendance_result' => $result,
        ], 'Shift assigned');
    }

    public function overrideShift(Request $request): array
    {
        $authUser = $this->authUser($request);
        $payload = Validator::validate($request->all(), [
            'employee_id' => ['required', 'integer'],
            'work_date' => ['required', 'date'],
            'shift_type_id' => ['integer'],
            'reason' => ['string'],
            'notes' => ['string'],
        ]);

        $employeeId = (int) $payload['employee_id'];
        $workDate = $this->normalizeDate((string) $payload['work_date']);
        $shiftTypeId = isset($payload['shift_type_id']) && $payload['shift_type_id'] !== '' ? (int) $payload['shift_type_id'] : null;
        $this->assertDateRangeNotLocked($workDate, $workDate, 'override shift');

        $this->assertCanManageEmployee($authUser, $employeeId);
        $employee = $this->employees->findWithDepartment($employeeId);
        if ($employee === null) {
            throw new HttpException('Employee not found', 404, 'not_found');
        }
        if ($shiftTypeId !== null && $this->shiftTypes->find($shiftTypeId) === null) {
            throw new HttpException('Shift not found', 404, 'not_found');
        }

        $existing = $this->shiftOverrides->findByEmployeeDate($employeeId, $workDate);
        $overrideId = $this->shiftOverrides->upsertForEmployeeDate($employeeId, $workDate, [
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'shift_type_id' => $shiftTypeId,
            'reason' => $payload['reason'] ?? null,
            'changed_by' => (int) $authUser['employee_id'],
            'changed_at' => date('Y-m-d H:i:s'),
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->shiftChangeLogs->create([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'old_shift_type_id' => $existing['shift_type_id'] ?? null,
            'new_shift_type_id' => $shiftTypeId,
            'change_type' => $shiftTypeId === null ? 'CLEAR' : 'OVERRIDE',
            'changed_by' => (int) $authUser['employee_id'],
            'changed_at' => date('Y-m-d H:i:s'),
            'reason' => $payload['reason'] ?? 'Manager override shift',
        ]);

        $result = $this->attendanceResultService->evaluateAndPersist($employeeId, $workDate);
        $this->workflowAuditService->recordEvent(
            'SCHEDULE_OVERRIDE',
            (string) $overrideId,
            'OVERRIDE_SHIFT',
            (int) $authUser['employee_id'],
            [
                'employee_id' => $employeeId,
                'work_date' => $workDate,
                'shift_type_id' => $shiftTypeId,
            ]
        );

        return $this->ok([
            'override_id' => $overrideId,
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'shift_type_id' => $shiftTypeId,
            'attendance_result' => $result,
        ], 'Shift override saved');
    }

    public function publishLogs(Request $request): array
    {
        $authUser = $this->authUser($request);
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(100, (int) $request->query('per_page', 20)));
        $offset = ($page - 1) * $limit;
        
        $departmentId = $request->query('department_id');
        
        // Basic filtering logic
        $sql = "SELECT spl.*, e.full_name as published_by_name 
                FROM schedule_publish_logs spl
                LEFT JOIN employees e ON e.employee_id = spl.published_by";
        $where = [];
        $params = [];
        
        if ($departmentId) {
            $where[] = "spl.scope_id = :dept_id AND spl.scope_type = 'DEPARTMENT'";
            $params['dept_id'] = (int) $departmentId;
        }
        
        if ($where !== []) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY spl.published_at DESC LIMIT $offset, $limit";
        
        $pdo = \App\Core\Database::connection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        
        return $this->ok($items, 'Publish logs retrieved');
    }

    public function publishSchedule(Request $request): array
    {
        $authUser = $this->authUser($request);
        $payload = Validator::validate($request->all(), [
            'scope_type' => ['required', 'string', 'in:DEPARTMENT,EMPLOYEE'],
            'scope_id' => ['required', 'integer'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'notes' => ['string'],
            'strict_mode' => ['boolean'],
        ]);

        $scopeType = (string) $payload['scope_type'];
        $scopeId = (int) $payload['scope_id'];
        $fromDate = $this->normalizeDate((string) $payload['from_date']);
        $toDate = $this->normalizeDate((string) $payload['to_date']);
        if ($toDate < $fromDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }
        $this->assertDateRangeNotLocked($fromDate, $toDate, 'publish schedule');

        $employeeIds = $this->resolveScopedEmployeeIds($authUser, $scopeType, $scopeId);
        if ($employeeIds === []) {
            throw new HttpException('No employees found in the selected schedule scope.', 422, 'validation_error');
        }

        $strictMode = array_key_exists('strict_mode', $payload) ? !empty($payload['strict_mode']) : false;
        $publishFacts = $this->collectScheduleFacts($employeeIds, $fromDate, $toDate);
        $publishReadiness = $this->workflowRuleEngine->evaluateSchedulePublishReadiness($publishFacts, $strictMode);
        if (!$publishReadiness['ready']) {
            $firstBlocker = $publishReadiness['blockers'][0] ?? null;
            $firstWarning = $publishReadiness['warnings'][0] ?? null;
            $firstIssue = is_array($firstBlocker) ? $firstBlocker : (is_array($firstWarning) ? $firstWarning : null);
            $message = is_array($firstIssue)
                ? (string) ($firstIssue['message'] ?? 'Schedule cannot be published due to workflow rules.')
                : 'Schedule cannot be published due to workflow rules.';

            throw new HttpException($message, 422, 'validation_error');
        }

        $publishLogId = $this->schedulePublishLogs->create([
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'published_by' => (int) $authUser['employee_id'],
            'published_at' => date('Y-m-d H:i:s'),
            'notes' => $payload['notes'] ?? null,
        ]);

        $generatedCount = 0;
        foreach ($employeeIds as $employeeId) {
            foreach ($this->iterateDates($fromDate, $toDate) as $workDate) {
                $current = $this->planningContextService->build($employeeId, $workDate);
                $this->shiftChangeLogs->create([
                    'employee_id' => $employeeId,
                    'work_date' => $workDate,
                    'old_shift_type_id' => $current['shift']['shift_type_id'] ?? null,
                    'new_shift_type_id' => $current['shift']['shift_type_id'] ?? null,
                    'change_type' => 'PUBLISH',
                    'changed_by' => (int) $authUser['employee_id'],
                    'changed_at' => date('Y-m-d H:i:s'),
                    'reason' => 'Schedule published',
                ]);
                $this->attendanceResultService->evaluateAndPersist($employeeId, $workDate);
                $generatedCount++;
            }
        }

        $this->workflowAuditService->recordTransition(
            'SCHEDULE_SCOPE',
            sprintf('%s:%d:%s:%s', $scopeType, $scopeId, $fromDate, $toDate),
            'DRAFT',
            'PUBLISHED',
            (int) $authUser['employee_id'],
            [
                'publish_log_id' => $publishLogId,
                'strict_mode' => $strictMode,
                'employee_count' => count($employeeIds),
                'generated_result_count' => $generatedCount,
                'publish_readiness' => $publishReadiness,
            ]
        );

        return $this->ok([
            'publish_log_id' => $publishLogId,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'employee_count' => count($employeeIds),
            'generated_result_count' => $generatedCount,
            'publish_readiness' => $publishReadiness,
        ], 'Schedule published');
    }

    public function bulkAssignShift(Request $request): array
    {
        $authUser = $this->authUser($request);
        $payload = Validator::validate($request->all(), [
            'shift_type_id' => ['required', 'integer'],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['date'],
            'is_permanent' => ['boolean'],
            'notes' => ['string'],
        ]);
        $employeeIdsRaw = $request->input('employee_ids');
        if (!is_array($employeeIdsRaw) || $employeeIdsRaw === []) {
            throw new HttpException('employee_ids is required', 422, 'validation_error');
        }

        $employeeIds = [];
        foreach ($employeeIdsRaw as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $employeeIds[] = $id;
            }
        }
        $employeeIds = array_values(array_unique($employeeIds));
        if ($employeeIds === []) {
            throw new HttpException('employee_ids is invalid', 422, 'validation_error');
        }

        $shiftTypeId = (int) $payload['shift_type_id'];
        $effectiveDate = $this->normalizeDate((string) $payload['effective_date']);
        $expiryDate = isset($payload['expiry_date']) ? $this->normalizeDate((string) $payload['expiry_date']) : null;
        $this->assertDateRangeNotLocked($effectiveDate, $expiryDate ?? $effectiveDate, 'bulk assign shift');
        $shift = $this->shiftTypes->find($shiftTypeId);
        if ($shift === null) {
            throw new HttpException('Shift not found', 404, 'not_found');
        }

        $saved = 0;
        foreach ($employeeIds as $employeeId) {
            $this->assertCanManageEmployee($authUser, $employeeId);
            $employee = $this->employees->findWithDepartment($employeeId);
            if ($employee === null) {
                continue;
            }

            // Requirements: Bulk assign = 1 Week logic
            for ($i = 0; $i < 7; $i++) {
                $workDate = date('Y-m-d', strtotime("$effectiveDate +$i days"));
                
                // Get existing for log
                $existing = $this->shiftAssignments->findByEmployeeEffectiveDate($employeeId, $workDate);
                
                $this->shiftAssignments->upsertForEmployeeDate($employeeId, $workDate, [
                    'employee_id' => $employeeId,
                    'shift_type_id' => $shiftTypeId,
                    'effective_date' => $workDate,
                    // Fix: Set expiry_date to workDate for each record in a week to prevent it from carrying forward
                    'expiry_date' => $workDate,
                    'is_permanent' => 0, // Weekly bulk assignments are usually tactical/draft
                    'assigned_by' => (int) $authUser['employee_id'],
                    'notes' => $payload['notes'] ?? 'Bulk weekly assignment',
                    'status' => 'HIỆU_LỰC',
                ]);

                $this->shiftChangeLogs->create([
                    'employee_id' => $employeeId,
                    'work_date' => $workDate,
                    'old_shift_type_id' => $existing['shift_type_id'] ?? null,
                    'new_shift_type_id' => $shiftTypeId,
                    'change_type' => 'ASSIGN',
                    'changed_by' => (int) $authUser['employee_id'],
                    'changed_at' => date('Y-m-d H:i:s'),
                    'reason' => 'Bulk weekly assignment',
                ]);

                $this->attendanceResultService->evaluateAndPersist($employeeId, $workDate);
                $saved++;
            }
        }

        return $this->ok(['processed_days' => $saved, 'employee_count' => count($employeeIds)], "Đã gán ca cho " . count($employeeIds) . " nhân sự trong 1 tuần (bắt đầu từ $effectiveDate)");
    }

    public function copyScheduleWeek(Request $request): array
    {
        $authUser = $this->authUser($request);
        $payload = Validator::validate($request->all(), [
            'scope_type' => ['required', 'string', 'in:DEPARTMENT,EMPLOYEE'],
            'scope_id' => ['required', 'integer'],
            'source_from_date' => ['required', 'date'],
            'target_from_date' => ['required', 'date'],
            'notes' => ['string'],
        ]);

        $scopeType = (string) $payload['scope_type'];
        $scopeId = (int) $payload['scope_id'];
        $sourceFromDate = $this->normalizeDate((string) $payload['source_from_date']);
        $targetFromDate = $this->normalizeDate((string) $payload['target_from_date']);
        $targetToDate = date('Y-m-d', strtotime($targetFromDate . ' +6 day'));
        $this->assertDateRangeNotLocked($targetFromDate, $targetToDate, 'copy schedule week');
        $employeeIds = $this->resolveScopedEmployeeIds($authUser, $scopeType, $scopeId);

        $copiedCount = 0;
        foreach ($employeeIds as $employeeId) {
            for ($offset = 0; $offset < 7; $offset++) {
                $sourceDate = date('Y-m-d', strtotime($sourceFromDate . " +{$offset} day"));
                $targetDate = date('Y-m-d', strtotime($targetFromDate . " +{$offset} day"));
                $context = $this->planningContextService->build($employeeId, $sourceDate);
                $shiftTypeId = isset($context['shift']['shift_type_id']) ? (int) $context['shift']['shift_type_id'] : 0;
                if ($shiftTypeId <= 0) {
                    continue;
                }
                $existing = $this->shiftOverrides->findByEmployeeDate($employeeId, $targetDate);
                $this->shiftOverrides->upsertForEmployeeDate($employeeId, $targetDate, [
                    'employee_id' => $employeeId,
                    'work_date' => $targetDate,
                    'shift_type_id' => $shiftTypeId,
                    'reason' => 'Copy tuần trước',
                    'changed_by' => (int) $authUser['employee_id'],
                    'changed_at' => date('Y-m-d H:i:s'),
                    'notes' => $payload['notes'] ?? null,
                ]);
                $this->shiftChangeLogs->create([
                    'employee_id' => $employeeId,
                    'work_date' => $targetDate,
                    'old_shift_type_id' => $existing['shift_type_id'] ?? null,
                    'new_shift_type_id' => $shiftTypeId,
                    'change_type' => 'COPY_WEEK',
                    'changed_by' => (int) $authUser['employee_id'],
                    'changed_at' => date('Y-m-d H:i:s'),
                    'reason' => sprintf('Copy từ %s', $sourceDate),
                ]);
                $this->attendanceResultService->evaluateAndPersist($employeeId, $targetDate);
                $copiedCount++;
            }
        }

        return $this->ok([
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'employee_count' => count($employeeIds),
            'copied_count' => $copiedCount,
            'source_from_date' => $sourceFromDate,
            'target_from_date' => $targetFromDate,
        ], 'Weekly schedule copied');
    }

    public function teamScheduleSuggestions(Request $request): array
    {
        $authUser = $this->authUser($request);
        [$fromDate, $toDate] = $this->dateRange($request);
        $departmentFilter = $request->query('department_id');
        $departmentId = is_numeric((string) $departmentFilter) ? (int) $departmentFilter : null;

        $items = $this->buildTeamScheduleItems($authUser, $fromDate, $toDate, $departmentId, true, false);
        $activeShifts = $this->shiftTypes->listActive();
        $defaultShift = $activeShifts[0] ?? null;
        foreach ($activeShifts as $shift) {
            if (empty($shift['is_night_shift'])) {
                $defaultShift = $shift;
                break;
            }
        }

        $suggestions = [];
        foreach ($items as $row) {
            $hasShift = !empty($row['shift']['shift_type_id']);
            if ($hasShift || $row['holiday'] !== null || $row['leave'] !== null || $row['business_trip'] !== null || $row['remote'] !== null) {
                continue;
            }
            $employeeId = (int) ($row['employee']['employee_id'] ?? 0);
            $workDate = (string) ($row['work_date'] ?? '');
            $recommended = null;
            $reason = 'Theo ca mặc định phòng ban';
            $prevDate = date('Y-m-d', strtotime($workDate . ' -1 day'));
            $prevContext = $this->planningContextService->build($employeeId, $prevDate);
            if (!empty($prevContext['shift']['shift_type_id'])) {
                $recommended = $prevContext['shift'];
                $reason = 'Theo lịch ngày liền trước để giữ tính ổn định';
            } elseif (is_array($defaultShift)) {
                $recommended = [
                    'shift_type_id' => (int) ($defaultShift['shift_type_id'] ?? 0),
                    'shift_name' => $defaultShift['shift_name'] ?? '',
                    'start_time' => $defaultShift['start_time'] ?? null,
                    'end_time' => $defaultShift['end_time'] ?? null,
                ];
            }
            if (empty($recommended['shift_type_id'])) {
                continue;
            }

            $suggestions[] = [
                'employee' => $row['employee'],
                'work_date' => $workDate,
                'recommended_shift' => $recommended,
                'reason' => $reason,
            ];
        }

        return $this->ok($suggestions, 'Team schedule suggestions', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'department_id' => $departmentId,
            'suggestion_count' => count($suggestions),
        ]);
    }

    public function teamScheduleWarnings(Request $request): array
    {
        $authUser = $this->authUser($request);
        [$fromDate, $toDate] = $this->dateRange($request);
        $departmentFilter = $request->query('department_id');
        $departmentId = is_numeric((string) $departmentFilter) ? (int) $departmentFilter : null;

        $items = $this->buildTeamScheduleItems($authUser, $fromDate, $toDate, $departmentId, true, false);
        $warnings = [
            'unassigned' => [],
            'leave_conflicts' => [],
            'late_risk' => [],
            'overtime_risk' => [],
        ];

        foreach ($items as $row) {
            $base = [
                'employee' => $row['employee'],
                'work_date' => $row['work_date'],
                'shift' => $row['shift'],
            ];
            if (empty($row['shift']) && $row['holiday'] === null && $row['leave'] === null && $row['business_trip'] === null && $row['remote'] === null) {
                $warnings['unassigned'][] = $base;
            }
            if (!empty($row['shift']) && $row['leave'] !== null) {
                $warnings['leave_conflicts'][] = $base + ['leave' => $row['leave']];
            }
            $lateMinutes = (int) ($row['attendance_result']['late_minutes'] ?? 0);
            if ($lateMinutes >= 15) {
                $warnings['late_risk'][] = $base + ['late_minutes' => $lateMinutes];
            }
            $overtimeMinutes = (int) ($row['attendance_result']['overtime_minutes'] ?? 0);
            if ($overtimeMinutes >= 240) {
                $warnings['overtime_risk'][] = $base + ['overtime_minutes' => $overtimeMinutes];
            }
        }

        return $this->ok($warnings, 'Team schedule warnings', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'department_id' => $departmentId,
            'summary' => [
                'unassigned' => count($warnings['unassigned']),
                'leave_conflicts' => count($warnings['leave_conflicts']),
                'late_risk' => count($warnings['late_risk']),
                'overtime_risk' => count($warnings['overtime_risk']),
            ],
        ]);
    }

    private function authUser(Request $request): array
    {
        $authUser = $request->attribute('auth_user');
        if (!is_array($authUser)) {
            throw new HttpException('Unauthorized', 401, 'unauthorized');
        }

        return $authUser;
    }

    private function assertCanManageEmployee(array $authUser, int $employeeId): void
    {
        if (Auth::isPrivileged($authUser)) {
            return;
        }

        $allowed = array_values(array_unique(array_map('intval', $authUser['hierarchy_employee_ids'] ?? [])));
        if (!in_array($employeeId, $allowed, true)) {
            throw new HttpException('Bạn không có quyền thao tác nhân sự này.', 403, 'forbidden');
        }
    }

    private function assertCanManageDepartment(array $authUser, int $departmentId): void
    {
        if (Auth::isPrivileged($authUser)) {
            return;
        }

        $allowed = array_values(array_unique(array_map('intval', $authUser['managed_department_ids'] ?? [])));
        if (!in_array($departmentId, $allowed, true)) {
            throw new HttpException('Bạn không có quyền thao tác phòng ban này.', 403, 'forbidden');
        }
    }

    private function resolveScopedEmployeeIds(array $authUser, string $scopeType, int $scopeId): array
    {
        $employeeIds = [];
        if ($scopeType === 'EMPLOYEE') {
            $this->assertCanManageEmployee($authUser, $scopeId);
            $employeeIds[] = $scopeId;
            return $employeeIds;
        }

        $this->assertCanManageDepartment($authUser, $scopeId);
        $page = $this->employees->paginateList(0, 1000, null, null, $scopeId, null);
        foreach ($page['items'] ?? [] as $employee) {
            $id = (int) ($employee['employee_id'] ?? 0);
            if ($id > 0) {
                $employeeIds[] = $id;
            }
        }
        return array_values(array_unique($employeeIds));
    }

    private function buildTeamScheduleItems(
        array $authUser,
        string $fromDate,
        string $toDate,
        ?int $departmentId = null,
        bool $includePlaceholders = true,
        bool $includeWorkflow = false
    ): array
    {
        $employeeIds = null;
        if (!Auth::isPrivileged($authUser)) {
            $hierarchyIdsRaw = $authUser['hierarchy_employee_ids'] ?? [];
            $employeeIds = array_values(array_unique(array_map('intval', is_array($hierarchyIdsRaw) ? $hierarchyIdsRaw : [])));
        }
        if ($employeeIds === [] && !Auth::isPrivileged($authUser)) {
            throw new HttpException('Bạn chưa có phạm vi quản lý nhân sự để xem lịch phòng ban.', 403, 'forbidden');
        }

        $employeePage = $this->employees->paginateList(0, 1000, null, null, $departmentId, $employeeIds === [] ? null : $employeeIds);
        $employees = $employeePage['items'] ?? [];
        $items = [];
        foreach ($employees as $employee) {
            $employeeId = (int) ($employee['employee_id'] ?? 0);
            foreach ($this->iterateDates($fromDate, $toDate) as $workDate) {
                $context = $this->planningContextService->build($employeeId, $workDate);
                $attendanceResult = $this->attendanceResults->findByEmployeeDate($employeeId, $workDate);

                if (!$includePlaceholders) {
                    $shiftSource = strtoupper((string) ($context['shift']['source'] ?? ''));
                    $isPersistedShiftRecord = $shiftSource === 'OVERRIDE'
                        || ($shiftSource === 'EMPLOYEE_DEFAULT' && (string) ($context['shift']['meta']['effective_date'] ?? '') === $workDate);

                    $hasConcreteRecord = $isPersistedShiftRecord
                        || $context['holiday'] !== null
                        || $context['leave'] !== null
                        || $context['business_trip'] !== null
                        || $context['remote'] !== null
                        || $attendanceResult !== null;

                    if (!$hasConcreteRecord) {
                        continue;
                    }
                }

                $items[] = [
                    'employee' => [
                        'employee_id' => $employeeId,
                        'employee_code' => $employee['employee_code'] ?? null,
                        'full_name' => $employee['full_name'] ?? null,
                        'department_id' => isset($employee['department_id']) ? (int) $employee['department_id'] : null,
                        'department_name' => $employee['department_name'] ?? null,
                    ],
                    'work_date' => $workDate,
                    'shift' => $context['shift'],
                    'holiday' => $context['holiday'],
                    'leave' => $context['leave'],
                    'business_trip' => $context['business_trip'],
                    'remote' => $context['remote'],
                    'overtime' => $context['overtime'],
                    'attendance_result' => $attendanceResult,
                    'workflow' => $includeWorkflow ? $this->resolveWorkflowState($employee, $workDate, $context) : null,
                ];
            }
        }

        return $items;
    }

    private function dateRange(Request $request): array
    {
        $fromDate = $this->normalizeDate((string) ($request->query('from_date', date('Y-m-d')) ?? date('Y-m-d')));
        $toDate = $this->normalizeDate((string) ($request->query('to_date', $fromDate) ?? $fromDate));
        if ($toDate < $fromDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $days = (int) floor((strtotime($toDate) - strtotime($fromDate)) / 86400);
        if ($days > 62) {
            throw new HttpException('Khoảng thời gian xem lịch tối đa là 62 ngày.', 422, 'validation_error');
        }

        return [$fromDate, $toDate];
    }

    private function iterateDates(string $fromDate, string $toDate): array
    {
        $items = [];
        $cursor = strtotime($fromDate);
        $end = strtotime($toDate);
        while ($cursor <= $end) {
            $items[] = date('Y-m-d', $cursor);
            $cursor = strtotime('+1 day', $cursor);
        }

        return $items;
    }

    private function normalizeDate(string $value): string
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new HttpException('Invalid date format', 422, 'validation_error');
        }

        return date('Y-m-d', $timestamp);
    }

    private function assertDateRangeNotLocked(string $fromDate, string $toDate, string $operation): void
    {
        $this->payrollLockService->assertRangeUnlocked($fromDate, $toDate, $operation);
    }

    private function normalizeHolidayType(string $value): string
    {
        $raw = strtoupper(trim($value));
        $normalized = str_replace([' ', '-'], '_', $raw);

        $map = [
            'NEW_YEAR' => 'NEW_YEAR',
            'TET_DUONG_LICH' => 'NEW_YEAR',
            'LUNAR_NEW_YEAR' => 'LUNAR_NEW_YEAR',
            'TET_AM_LICH' => 'LUNAR_NEW_YEAR',
            'TET_NGUYEN_DAN' => 'LUNAR_NEW_YEAR',
            'HUNG_KINGS' => 'HUNG_KINGS',
            'GIO_TO_HUNG_VUONG' => 'HUNG_KINGS',
            'LIBERATION_DAY' => 'LIBERATION_DAY',
            'GIAI_PHONG_MIEN_NAM' => 'LIBERATION_DAY',
            'LABOR_DAY' => 'LABOR_DAY',
            'QUOC_TE_LAO_DONG' => 'LABOR_DAY',
            'NATIONAL_DAY' => 'NATIONAL_DAY',
            'QUOC_KHANH' => 'NATIONAL_DAY',
            'OTHER' => 'OTHER',
            'LE_TOAN_CONG_TY' => 'OTHER',
            'NGHI_BU' => 'OTHER',
            'NGHI_SU_KIEN_NOI_BO' => 'OTHER',
            'NGAY_NGHI_DAC_BIET' => 'OTHER',
        ];

        return $map[$normalized] ?? 'OTHER';
    }

    private function resolveWorkflowState(array $employee, string $workDate, array $context): array
    {
        $employeeId = (int) ($employee['employee_id'] ?? 0);
        $departmentId = isset($employee['department_id']) ? (int) $employee['department_id'] : null;
        $publishLog = $this->schedulePublishLogs->findLatestCoveringDate($employeeId, $departmentId, $workDate);
        $lastChange = $this->shiftChangeLogs->findLatestByEmployeeDate($employeeId, $workDate);
        $adjustmentAfterPublish = null;

        if ($publishLog !== null && !empty($publishLog['published_at'])) {
            $adjustmentAfterPublish = $this->shiftChangeLogs->findLatestAfterTimestamp(
                $employeeId,
                $workDate,
                (string) $publishLog['published_at'],
                ['PUBLISH']
            );
        }

        $hasPlannedState = $context['shift'] !== null
            || $context['holiday'] !== null
            || $context['leave'] !== null
            || $context['business_trip'] !== null
            || $context['remote'] !== null;

        $statusCode = 'UNASSIGNED';
        $statusLabel = 'Unassigned';
        if ($hasPlannedState) {
            $statusCode = 'DRAFT';
            $statusLabel = 'Draft';
            if ($publishLog !== null) {
                $statusCode = 'PUBLISHED';
                $statusLabel = 'Published';
                if ($adjustmentAfterPublish !== null) {
                    $statusCode = 'ADJUSTED_AFTER_PUBLISH';
                    $statusLabel = 'Adjusted After Publish';
                }
            }
        }

        return [
            'code' => $statusCode,
            'label' => $statusLabel,
            'publish_log' => $publishLog === null ? null : [
                'publish_log_id' => (int) ($publishLog['publish_log_id'] ?? 0),
                'scope_type' => $publishLog['scope_type'] ?? null,
                'scope_id' => isset($publishLog['scope_id']) ? (int) $publishLog['scope_id'] : null,
                'from_date' => $publishLog['from_date'] ?? null,
                'to_date' => $publishLog['to_date'] ?? null,
                'published_at' => $publishLog['published_at'] ?? null,
                'published_by' => isset($publishLog['published_by']) ? (int) $publishLog['published_by'] : null,
                'published_by_name' => $publishLog['published_by_name'] ?? null,
                'notes' => $publishLog['notes'] ?? null,
            ],
            'last_change' => $lastChange === null ? null : [
                'change_log_id' => (int) ($lastChange['change_log_id'] ?? 0),
                'change_type' => $lastChange['change_type'] ?? null,
                'changed_at' => $lastChange['changed_at'] ?? null,
                'changed_by' => isset($lastChange['changed_by']) ? (int) $lastChange['changed_by'] : null,
                'changed_by_name' => $lastChange['changed_by_name'] ?? null,
                'reason' => $lastChange['reason'] ?? null,
            ],
            'adjustment_after_publish' => $adjustmentAfterPublish === null ? null : [
                'change_log_id' => (int) ($adjustmentAfterPublish['change_log_id'] ?? 0),
                'change_type' => $adjustmentAfterPublish['change_type'] ?? null,
                'changed_at' => $adjustmentAfterPublish['changed_at'] ?? null,
                'changed_by' => isset($adjustmentAfterPublish['changed_by']) ? (int) $adjustmentAfterPublish['changed_by'] : null,
                'changed_by_name' => $adjustmentAfterPublish['changed_by_name'] ?? null,
                'reason' => $adjustmentAfterPublish['reason'] ?? null,
            ],
        ];
    }

    private function collectPublishReadiness(array $employeeIds, string $fromDate, string $toDate): array
    {
        $unassignedExamples = [];
        $leaveConflictCount = 0;

        foreach ($employeeIds as $employeeId) {
            $employee = $this->employees->findWithDepartment($employeeId);
            if ($employee === null) {
                continue;
            }

            foreach ($this->iterateDates($fromDate, $toDate) as $workDate) {
                $context = $this->planningContextService->build($employeeId, $workDate);
                $hasShift = $context['shift'] !== null;
                $hasApprovedOffReason = $context['holiday'] !== null
                    || $context['leave'] !== null
                    || $context['business_trip'] !== null
                    || $context['remote'] !== null;

                if (!$hasShift && !$hasApprovedOffReason) {
                    $unassignedExamples[] = sprintf(
                        '%s %s',
                        (string) ($employee['employee_code'] ?? ('#' . $employeeId)),
                        $workDate
                    );
                }

                if ($hasShift && $context['leave'] !== null) {
                    $leaveConflictCount++;
                }
            }
        }

        return [
            'unassigned_count' => count($unassignedExamples),
            'unassigned_examples' => $unassignedExamples,
            'leave_conflict_count' => $leaveConflictCount,
        ];
    }

    private function collectScheduleFacts(array $employeeIds, string $fromDate, string $toDate): array
    {
        $facts = [];
        foreach ($employeeIds as $employeeId) {
            $employee = $this->employees->findWithDepartment($employeeId);
            if ($employee === null) {
                continue;
            }

            foreach ($this->iterateDates($fromDate, $toDate) as $workDate) {
                $context = $this->planningContextService->build($employeeId, $workDate);
                $hasShift = $context['shift'] !== null;
                $hasApprovedOffReason = $context['holiday'] !== null
                    || $context['leave'] !== null
                    || $context['business_trip'] !== null
                    || $context['remote'] !== null;

                $facts[] = [
                    'employee_id' => $employeeId,
                    'employee_code' => $employee['employee_code'] ?? ('#' . $employeeId),
                    'work_date' => $workDate,
                    'has_shift' => $hasShift,
                    'has_approved_off_reason' => $hasApprovedOffReason,
                    'has_leave_conflict' => $hasShift && $context['leave'] !== null,
                    'has_overtime_without_shift' => !$hasShift && $context['overtime'] !== null,
                ];
            }
        }

        return $facts;
    }

    private function broadcastHolidayNotification(array $holiday, string $eventType, int $senderId): void
    {
        $holidayId = (int) ($holiday['holiday_id'] ?? 0);
        $holidayName = trim((string) ($holiday['holiday_name'] ?? 'Ngày nghỉ hệ thống'));
        $holidayDate = $this->normalizeDate((string) ($holiday['holiday_date'] ?? date('Y-m-d')));
        $dateLabel = date('d/m/Y', strtotime($holidayDate));
        $scopeLabel = !empty($holiday['is_recurring']) ? 'lặp hàng năm' : 'theo năm cấu hình';

        [$title, $content] = match ($eventType) {
            'updated' => [
                'Cập nhật ngày nghỉ hệ thống',
                sprintf('%s được cập nhật vào ngày %s. Toàn bộ nhân sự cần theo dõi lịch nghỉ mới (%s).', $holidayName, $dateLabel, $scopeLabel),
            ],
            'deleted' => [
                'Điều chỉnh lịch nghỉ hệ thống',
                sprintf('Ngày nghỉ %s vào %s đã được gỡ khỏi lịch hệ thống. Vui lòng kiểm tra lại kế hoạch làm việc.', $holidayName, $dateLabel),
            ],
            default => [
                'Thiết lập ngày nghỉ hệ thống mới',
                sprintf('%s đã được thiết lập cho ngày %s. Nhân sự, trưởng phòng và HR vui lòng chủ động theo dõi lịch nghỉ.', $holidayName, $dateLabel),
            ],
        };

        try {
            $employees = $this->employees->paginateList(0, 5000, null, null, null, null);
            foreach (($employees['items'] ?? []) as $employee) {
                $receiverId = (int) ($employee['employee_id'] ?? 0);
                if ($receiverId <= 0) {
                    continue;
                }

                $departmentId = isset($employee['department_id']) ? (int) $employee['department_id'] : null;
                $this->notifications->create([
                    'notification_type' => 'HOLIDAY_CALENDAR',
                    'title' => $title,
                    'content' => $content,
                    'sender_id' => $senderId > 0 ? $senderId : null,
                    'receiver_id' => $receiverId,
                    'department_id' => $departmentId > 0 ? $departmentId : null,
                    'is_read' => 0,
                    'priority' => 'TRUNG_BÌNH',
                    'reference_type' => 'HOLIDAY',
                    'reference_id' => $holidayId > 0 ? $holidayId : null,
                ]);
            }
        } catch (\Throwable) {
            // Notification broadcasting is best-effort and should not block holiday setup.
        }
    }
}
