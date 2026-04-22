<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Employee;
use App\Services\TimesheetService;
use App\Services\AttendanceImportService;

class TimesheetController extends Controller
{
    private TimesheetService $timesheetService;
    private AttendanceImportService $importService;
    private Employee $employees;

    public function __construct()
    {
        $this->timesheetService = new TimesheetService();
        $this->importService = new AttendanceImportService();
        $this->employees = new Employee();
    }

    public function dailyEntry(Request $request): array
    {
        $employeeId = (int)$request->query('employee_id');
        $workDate = $request->query('work_date');
        
        if (!$employeeId || !$workDate) {
            throw new HttpException('employee_id and work_date required', 400);
        }

        $entry = $this->timesheetService->buildDailyEntry($employeeId, $workDate);
        return $this->ok($entry, 'Daily timesheet entry');
    }

    public function periodSummary(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'employee_ids' => ['array'],
            'department_id' => ['integer'],
        ]);

        $authUser = $this->authUser($request);
        $employeeIds = $this->resolveEmployeeIds($authUser, $payload);
        $summary = $this->timesheetService->buildPeriodSummary(
            $employeeIds,
            $payload['from_date'],
            $payload['to_date']
        );
        return $this->ok($summary, 'Timesheet period summary');
    }

    public function exceptions(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'employee_ids' => ['array'],
            'department_id' => ['integer'],
        ]);

        $authUser = $this->authUser($request);
        $employeeIds = $this->resolveEmployeeIds($authUser, $payload);
        $exceptions = $this->timesheetService->buildExceptionList(
             $employeeIds,
             $payload['from_date'],
             $payload['to_date']
        );
        return $this->ok($exceptions, 'Timesheet exceptions');
    }
    
    public function import(Request $request): array
    {
        $authUser = $request->attribute('auth_user');
        $payload = Validator::validate($request->all(), [
            'rows' => ['required', 'array'],
        ]);
        
        $result = $this->importService->importLogs($payload['rows'], (int)$authUser['employee_id']);
        if (!$result['success']) {
            throw new HttpException($result['message'] ?? 'Import failed', 500);
        }
        
        return $this->ok($result, 'Logs imported');
    }
    
    public function payrollExport(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'employee_ids' => ['array'],
            'department_id' => ['integer'],
        ]);
        
        $authUser = $this->authUser($request);
        $employeeIds = $this->resolveEmployeeIds($authUser, $payload);
        $exportData = $this->timesheetService->buildPayrollExport(
             $employeeIds,
             $payload['from_date'],
             $payload['to_date']
        );
        return $this->ok($exportData, 'Payroll ready data');
    }

    private function authUser(Request $request): array
    {
        $authUser = $request->attribute('auth_user');
        if (!is_array($authUser)) {
            throw new HttpException('Unauthorized', 401, 'unauthorized');
        }

        return $authUser;
    }

    private function resolveEmployeeIds(array $authUser, array $payload): array
    {
        $explicitIds = [];
        if (isset($payload['employee_ids']) && is_array($payload['employee_ids'])) {
            $explicitIds = array_values(array_unique(array_filter(
                array_map('intval', $payload['employee_ids']),
                static fn (int $id): bool => $id > 0
            )));
        }

        $departmentId = isset($payload['department_id']) ? (int) $payload['department_id'] : 0;

        if ($explicitIds !== []) {
            if (!Auth::isPrivileged($authUser)) {
                $allowedIds = array_values(array_unique(array_map('intval', array_merge(
                    [(int) ($authUser['employee_id'] ?? 0)],
                    $authUser['hierarchy_employee_ids'] ?? []
                ))));
                $explicitIds = array_values(array_intersect($explicitIds, $allowedIds));
            }

            if ($explicitIds === []) {
                throw new HttpException('No accessible employees matched the request.', 403, 'forbidden');
            }

            return $explicitIds;
        }

        $scopeEmployeeIds = null;
        if (!Auth::isPrivileged($authUser)) {
            $scopeEmployeeIds = array_values(array_unique(array_map('intval', array_merge(
                [(int) ($authUser['employee_id'] ?? 0)],
                $authUser['hierarchy_employee_ids'] ?? []
            ))));
        }

        $page = $this->employees->paginateList(
            0,
            5000,
            null,
            null,
            $departmentId > 0 ? $departmentId : null,
            $scopeEmployeeIds
        );

        $employeeIds = array_values(array_unique(array_filter(
            array_map(
                static fn (array $employee): int => (int) ($employee['employee_id'] ?? 0),
                $page['items'] ?? []
            ),
            static fn (int $id): bool => $id > 0
        )));

        if ($employeeIds === []) {
            throw new HttpException('No employees found for the selected scope.', 422, 'validation_error');
        }

        return $employeeIds;
    }
}
