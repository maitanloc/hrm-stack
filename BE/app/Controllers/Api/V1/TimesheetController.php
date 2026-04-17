<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Validator;
use App\Services\TimesheetService;
use App\Services\AttendanceImportService;

class TimesheetController extends Controller
{
    private TimesheetService $timesheetService;
    private AttendanceImportService $importService;

    public function __construct()
    {
        $this->timesheetService = new TimesheetService();
        $this->importService = new AttendanceImportService();
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
            'employee_ids' => ['required', 'array'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
        ]);

        $summary = $this->timesheetService->buildPeriodSummary(
            $payload['employee_ids'],
            $payload['from_date'],
            $payload['to_date']
        );
        return $this->ok($summary, 'Timesheet period summary');
    }

    public function exceptions(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'employee_ids' => ['required', 'array'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
        ]);

        $exceptions = $this->timesheetService->buildExceptionList(
             $payload['employee_ids'],
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
            'employee_ids' => ['required', 'array'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
        ]);
        
        $exportData = $this->timesheetService->buildPayrollExport(
             $payload['employee_ids'],
             $payload['from_date'],
             $payload['to_date']
        );
        return $this->ok($exportData, 'Payroll ready data');
    }
}
