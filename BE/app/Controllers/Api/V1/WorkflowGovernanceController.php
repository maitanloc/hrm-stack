<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Employee;
use App\Models\WorkflowAuditLog;
use App\Services\PlanningContextService;
use App\Services\WorkflowCatalogService;
use App\Services\WorkflowRuleEngineService;

class WorkflowGovernanceController extends Controller
{
    private WorkflowCatalogService $catalogService;
    private WorkflowRuleEngineService $ruleEngineService;
    private PlanningContextService $planningContextService;
    private Employee $employees;
    private WorkflowAuditLog $workflowAuditLogs;

    public function __construct()
    {
        $this->catalogService = new WorkflowCatalogService();
        $this->ruleEngineService = new WorkflowRuleEngineService($this->catalogService);
        $this->planningContextService = new PlanningContextService();
        $this->employees = new Employee();
        $this->workflowAuditLogs = new WorkflowAuditLog();
    }

    public function overview(Request $request): array
    {
        return $this->ok($this->catalogService->overview(), 'Workflow overview');
    }

    public function catalog(Request $request): array
    {
        $section = $request->query('section');
        if (is_string($section) && trim($section) !== '') {
            return $this->ok(
                $this->catalogService->section($section),
                'Workflow catalog section',
                ['section' => strtolower(trim($section))]
            );
        }

        return $this->ok($this->catalogService->fullCatalog(), 'Workflow full catalog');
    }

    public function transitions(Request $request): array
    {
        $entity = $request->query('entity');
        if (!is_string($entity) || trim($entity) === '') {
            throw new HttpException('entity is required', 422, 'validation_error');
        }

        return $this->ok(
            $this->catalogService->transitions($entity),
            'Workflow transitions'
        );
    }

    public function validateTransition(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'entity' => ['required', 'string'],
            'current_state' => ['required', 'string'],
            'target_state' => ['required', 'string'],
        ]);

        $context = $request->input('context', []);
        if (!is_array($context)) {
            $context = [];
        }

        $result = $this->ruleEngineService->validateTransition(
            (string) $payload['entity'],
            (string) $payload['current_state'],
            (string) $payload['target_state'],
            $context
        );

        return $this->ok($result, 'Workflow transition validation');
    }

    public function validateSchedulePublish(Request $request): array
    {
        $authUser = $this->authUser($request);
        $payload = Validator::validate($request->all(), [
            'scope_type' => ['required', 'string', 'in:DEPARTMENT,EMPLOYEE'],
            'scope_id' => ['required', 'integer'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'strict_mode' => ['boolean'],
        ]);

        $scopeType = strtoupper((string) $payload['scope_type']);
        $scopeId = (int) $payload['scope_id'];
        $fromDate = $this->normalizeDate((string) $payload['from_date']);
        $toDate = $this->normalizeDate((string) $payload['to_date']);
        if ($toDate < $fromDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $strictMode = array_key_exists('strict_mode', $payload) ? !empty($payload['strict_mode']) : false;
        $employeeIds = $this->resolveScopedEmployeeIds($authUser, $scopeType, $scopeId);
        $facts = $this->collectScheduleFacts($employeeIds, $fromDate, $toDate);
        $readiness = $this->ruleEngineService->evaluateSchedulePublishReadiness($facts, $strictMode);

        return $this->ok($readiness, 'Schedule publish validation', [
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'employee_count' => count($employeeIds),
        ]);
    }

    public function auditLogs(Request $request): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, min(200, (int) $request->query('per_page', 50)));
        $offset = ($page - 1) * $limit;
        $entityType = $request->query('entity_type');
        $entityRef = $request->query('entity_ref');
        $actorId = $request->query('actor_id');

        $result = $this->workflowAuditLogs->paginateList(
            $offset,
            $limit,
            is_string($entityType) ? $entityType : null,
            is_string($entityRef) ? $entityRef : null,
            is_numeric((string) $actorId) ? (int) $actorId : null,
        );

        return $this->ok($result['items'], 'Workflow audit logs', [
            'total' => $result['total'],
            'page' => $page,
            'per_page' => $limit,
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
            throw new HttpException('Ban khong co quyen thao tac nhan su nay.', 403, 'forbidden');
        }
    }

    private function assertCanManageDepartment(array $authUser, int $departmentId): void
    {
        if (Auth::isPrivileged($authUser)) {
            return;
        }

        $allowed = array_values(array_unique(array_map('intval', $authUser['managed_department_ids'] ?? [])));
        if (!in_array($departmentId, $allowed, true)) {
            throw new HttpException('Ban khong co quyen thao tac phong ban nay.', 403, 'forbidden');
        }
    }

    private function resolveScopedEmployeeIds(array $authUser, string $scopeType, int $scopeId): array
    {
        if ($scopeType === 'EMPLOYEE') {
            $this->assertCanManageEmployee($authUser, $scopeId);
            return [$scopeId];
        }

        $this->assertCanManageDepartment($authUser, $scopeId);
        $page = $this->employees->paginateList(0, 1000, null, null, $scopeId, null);
        $employeeIds = [];
        foreach ($page['items'] ?? [] as $employee) {
            $id = (int) ($employee['employee_id'] ?? 0);
            if ($id > 0) {
                $employeeIds[] = $id;
            }
        }

        return array_values(array_unique($employeeIds));
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
}
