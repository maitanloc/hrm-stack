<?php
declare(strict_types=1);

use App\Controllers\Api\V1\AssetController;
use App\Controllers\Api\V1\AttendanceController;
use App\Controllers\Api\V1\AttendanceRiskController;
use App\Controllers\Api\V1\AuthController;
use App\Controllers\Api\V1\CommunicationController;
use App\Controllers\Api\V1\ContractChangeLogController;
use App\Controllers\Api\V1\ContractController;
use App\Controllers\Api\V1\DepartmentController;
use App\Controllers\Api\V1\EmployeeController;
use App\Controllers\Api\V1\FaceController;
use App\Controllers\Api\V1\FaceEnrollmentController;
use App\Controllers\Api\V1\HealthController;
use App\Controllers\Api\V1\InternalServiceController;
use App\Controllers\Api\V1\KioskController;
use App\Controllers\Api\V1\LeaveController;
use App\Controllers\Api\V1\NotificationController;
use App\Controllers\Api\V1\PayrollController;
use App\Controllers\Api\V1\PositionController;
use App\Controllers\Api\V1\RecruitmentController;
use App\Controllers\Api\V1\RequestController;
use App\Controllers\Api\V1\RequestTypeController;
use App\Controllers\Api\V1\SettingController;
use App\Controllers\Api\V1\TimesheetController;
use App\Controllers\Api\V1\WorkforceController;
use App\Controllers\Api\V1\WorkflowGovernanceController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\HierarchyEmployeeBodyMiddleware;
use App\Middlewares\HierarchyEmployeeParamMiddleware;
use App\Middlewares\HierarchyScopeMiddleware;
use App\Middlewares\PermissionMiddleware;

$router->get('/', function (): array {
    return [
        'status' => 200,
        'message' => 'HRM API is running',
        'data' => [
            'health' => '/api/v1/health',
            'login' => '/api/v1/auth/login',
            'me' => '/api/v1/auth/me',
            'version' => 'v1',
        ],
    ];
});

$router->group('/api/v1', function ($router): void {
    $router->get('/health', [HealthController::class, 'index']);
    $router->post('/auth/login', [AuthController::class, 'login']);
    $router->post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    $router->post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    $router->get('/public/positions', [PositionController::class, 'publicCatalog']);
    $router->post('/public/recruitment/applications', [RecruitmentController::class, 'publicCandidateApply']);
    $router->post('/public/face/recognize', [FaceController::class, 'recognizeAndCheckIn']);
    $router->get('/public/face/embeddings', [FaceController::class, 'embeddings']);
    $router->get('/public/settings/general', [FaceController::class, 'publicGeneralSettings']);
    $router->post('/kiosk/face-attendance', [KioskController::class, 'faceAttendance']);
    $router->get('/recruitment-candidates/{id}/cv', [RecruitmentController::class, 'candidateDownloadCv']);
    $router->post('/internal/recruitment-ai-jobs/process', [RecruitmentController::class, 'internalProcessAiScoringJobs']);

    $router->group('', function ($router): void {
        $router->get('/auth/me', [AuthController::class, 'me']);
        $router->post('/auth/refresh', [AuthController::class, 'refresh']);
        $router->get('/auth/hierarchy', [AuthController::class, 'hierarchy']);

        $router->get('/contract-change-logs', [ContractChangeLogController::class, 'index'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->post('/contract-change-logs', [ContractChangeLogController::class, 'store'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);

        $router->get('/positions', [PositionController::class, 'index'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->get('/positions/{id}', [PositionController::class, 'show'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->post('/positions', [PositionController::class, 'store'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'create'],
        ]);
        $router->put('/positions/{id}', [PositionController::class, 'update'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);
        $router->patch('/positions/{id}', [PositionController::class, 'update'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);
        $router->delete('/positions/{id}', [PositionController::class, 'destroy'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'delete'],
        ]);

        $router->get('/contract-types', [ContractController::class, 'contractTypes'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->get('/contracts', [ContractController::class, 'index'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->get('/contracts/{id}', [ContractController::class, 'show'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->post('/contracts', [ContractController::class, 'store'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'create'],
        ]);
        $router->put('/contracts/{id}', [ContractController::class, 'update'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);
        $router->patch('/contracts/{id}', [ContractController::class, 'update'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);

        $router->get('/settings/general', [SettingController::class, 'general'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'access'],
        ]);
        $router->put('/settings/general', [SettingController::class, 'updateGeneral'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'edit'],
        ]);
        $router->get('/settings/notifications', [SettingController::class, 'notificationIndex'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'access'],
        ]);
        $router->put('/settings/notifications', [SettingController::class, 'notificationUpdate'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'edit'],
        ]);
        $router->get('/shifts', [WorkforceController::class, 'shiftCatalog'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/my-shift/today', [WorkforceController::class, 'myShiftToday'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/my-schedule', [WorkforceController::class, 'mySchedule'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/team-schedule', [WorkforceController::class, 'teamSchedule'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/team-schedule/assign', [WorkforceController::class, 'assignShift'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
        ]);
        $router->post('/team-schedule/bulk-assign', [WorkforceController::class, 'bulkAssignShift'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
        ]);
        $router->patch('/team-schedule/override', [WorkforceController::class, 'overrideShift'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);
        $router->post('/team-schedule/publish', [WorkforceController::class, 'publishSchedule'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);
        $router->get('/team-schedule/publish-logs', [WorkforceController::class, 'publishLogs'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/face/register', [FaceController::class, 'register'], [
            // Cả NV và Admin đều có thể quét đăng ký mặt
        ]);
        $router->post('/team-schedule/copy-week', [WorkforceController::class, 'copyScheduleWeek'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);
        $router->get('/team-schedule/suggestions', [WorkforceController::class, 'teamScheduleSuggestions'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/team-schedule/warnings', [WorkforceController::class, 'teamScheduleWarnings'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/attendance-results', [WorkforceController::class, 'attendanceResultIndex'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/workflow-governance/overview', [WorkflowGovernanceController::class, 'overview'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/workflow-governance/catalog', [WorkflowGovernanceController::class, 'catalog'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/workflow-governance/transitions', [WorkflowGovernanceController::class, 'transitions'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/workflow-governance/validate-transition', [WorkflowGovernanceController::class, 'validateTransition'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/workflow-governance/validate-schedule-publish', [WorkflowGovernanceController::class, 'validateSchedulePublish'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);
        $router->get('/workflow-governance/audit-logs', [WorkflowGovernanceController::class, 'auditLogs'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/timesheet/period-summary', [TimesheetController::class, 'periodSummary'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/timesheet/exceptions', [TimesheetController::class, 'exceptions'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/timesheet/import', [TimesheetController::class, 'import'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
        ]);
        $router->post('/timesheet/payroll-export', [TimesheetController::class, 'payrollExport'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/holidays', [WorkforceController::class, 'holidayIndex'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/holidays/{id}', [WorkforceController::class, 'holidayShow'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/holidays', [WorkforceController::class, 'holidayStore'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'create'],
        ]);
        $router->put('/holidays/{id}', [WorkforceController::class, 'holidayUpdate'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'edit'],
        ]);
        $router->patch('/holidays/{id}', [WorkforceController::class, 'holidayUpdate'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'edit'],
        ]);
        $router->delete('/holidays/{id}', [WorkforceController::class, 'holidayDelete'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'delete'],
        ]);

        $router->get('/recruitment-positions', [RecruitmentController::class, 'positionIndex'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->post('/recruitment-positions', [RecruitmentController::class, 'positionStore'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'create'],
        ]);
        $router->patch('/recruitment-positions/{id}', [RecruitmentController::class, 'positionUpdate'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);
        $router->delete('/recruitment-positions/{id}', [RecruitmentController::class, 'positionDelete'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'delete'],
        ]);

        $router->get('/recruitment-candidates', [RecruitmentController::class, 'candidateIndex'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->get('/recruitment-candidates/{id}', [RecruitmentController::class, 'candidateShow'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->post('/recruitment-candidates', [RecruitmentController::class, 'candidateStore'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'create'],
        ]);
        $router->post('/recruitment-candidates/{id}/cv', [RecruitmentController::class, 'candidateUploadCv'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);
        $router->post('/recruitment-candidates/{id}/ai-score/retry', [RecruitmentController::class, 'candidateRetryAiScore'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);
        $router->patch('/recruitment-candidates/{id}', [RecruitmentController::class, 'candidateUpdate'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);
        $router->get('/recruitment-candidates/{id}/manager-review', [RecruitmentController::class, 'candidateManagerReviewShow'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->patch('/recruitment-candidates/{id}/manager-review', [RecruitmentController::class, 'candidateManagerReviewUpsert'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);

        $router->get('/interviews', [RecruitmentController::class, 'interviewIndex'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);
        $router->post('/interviews', [RecruitmentController::class, 'interviewStore'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'create'],
        ]);
        $router->patch('/interviews/{id}', [RecruitmentController::class, 'interviewUpdate'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
        ]);
        $router->patch('/interviews/{id}/manager-review', [RecruitmentController::class, 'interviewManagerReview'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
        ]);

        $router->get('/service-categories', [InternalServiceController::class, 'categoryIndex'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);
        $router->get('/service-tickets', [InternalServiceController::class, 'ticketIndex'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);
        $router->post('/service-tickets', [InternalServiceController::class, 'ticketStore'], [
            [PermissionMiddleware::class, 'LEAVE_CREATE', 'create'],
        ]);
        $router->patch('/service-tickets/{id}', [InternalServiceController::class, 'ticketUpdate'], [
            [PermissionMiddleware::class, 'LEAVE_EDIT', 'edit'],
        ]);

        $router->get('/employees', [EmployeeController::class, 'index'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyScopeMiddleware::class, 'employee_id', true],
        ]);
        $router->get('/employees/{id}', [EmployeeController::class, 'show'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->get('/employees/{id}/profile', [EmployeeController::class, 'profileShow'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->post('/employees/{id}/enroll-face', [FaceEnrollmentController::class, 'enroll'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->post('/employees', [EmployeeController::class, 'store'], [
            [PermissionMiddleware::class, 'EMP_CREATE', 'create'],
        ]);
        $router->post('/employees/import-probation', [EmployeeController::class, 'importProbation'], [
            [PermissionMiddleware::class, 'EMP_CREATE', 'create'],
        ]);
        $router->put('/employees/{id}', [EmployeeController::class, 'update'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->patch('/employees/{id}', [EmployeeController::class, 'update'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->patch('/employees/{id}/profile', [EmployeeController::class, 'profileUpdate'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->get('/employees/{id}/employment-histories', [EmployeeController::class, 'employmentHistory'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->get('/employees/{id}/certificates', [EmployeeController::class, 'certificateIndex'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->post('/employees/{id}/certificates', [EmployeeController::class, 'certificateStore'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->delete('/employees/{id}/certificates/{certificateId}', [EmployeeController::class, 'certificateDelete'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->get('/employees/{id}/dependents', [EmployeeController::class, 'dependentIndex'], [
            [PermissionMiddleware::class, 'EMP_VIEW', 'access'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->post('/employees/{id}/dependents', [EmployeeController::class, 'dependentStore'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->delete('/employees/{id}/dependents/{dependentId}', [EmployeeController::class, 'dependentDelete'], [
            [PermissionMiddleware::class, 'EMP_EDIT', 'edit'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);
        $router->delete('/employees/{id}', [EmployeeController::class, 'destroy'], [
            [PermissionMiddleware::class, 'EMP_DELETE', 'delete'],
            [HierarchyEmployeeParamMiddleware::class, 'id', true],
        ]);

        $router->get('/departments', [DepartmentController::class, 'index'], [
            [PermissionMiddleware::class, 'DEPARTMENT_VIEW', 'access'],
        ]);
        $router->get('/departments/{id}', [DepartmentController::class, 'show'], [
            [PermissionMiddleware::class, 'DEPARTMENT_VIEW', 'access'],
        ]);
        $router->post('/departments', [DepartmentController::class, 'store'], [
            [PermissionMiddleware::class, 'DEPARTMENT_EDIT', 'create'],
        ]);
        $router->put('/departments/{id}', [DepartmentController::class, 'update'], [
            [PermissionMiddleware::class, 'DEPARTMENT_EDIT', 'edit'],
        ]);
        $router->patch('/departments/{id}', [DepartmentController::class, 'update'], [
            [PermissionMiddleware::class, 'DEPARTMENT_EDIT', 'edit'],
        ]);
        $router->delete('/departments/{id}', [DepartmentController::class, 'destroy'], [
            [PermissionMiddleware::class, 'DEPARTMENT_EDIT', 'delete'],
        ]);

        $router->get('/request-types', [RequestTypeController::class, 'index'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);
        $router->get('/request-types/{id}', [RequestTypeController::class, 'show'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);
        $router->post('/request-types', [RequestTypeController::class, 'store'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'create'],
        ]);
        $router->put('/request-types/{id}', [RequestTypeController::class, 'update'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'edit'],
        ]);
        $router->patch('/request-types/{id}', [RequestTypeController::class, 'update'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'edit'],
        ]);
        $router->delete('/request-types/{id}', [RequestTypeController::class, 'destroy'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'delete'],
        ]);

        $router->get('/requests', [RequestController::class, 'index'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
            [HierarchyScopeMiddleware::class, 'requester_id', true],
        ]);
        $router->get('/requests/{id}', [RequestController::class, 'show'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);
        $router->post('/requests', [RequestController::class, 'store'], [
            [PermissionMiddleware::class, 'LEAVE_CREATE', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'requester_id', true],
        ]);
        $router->put('/requests/{id}', [RequestController::class, 'update'], [
            [PermissionMiddleware::class, 'LEAVE_EDIT', 'edit'],
        ]);
        $router->patch('/requests/{id}', [RequestController::class, 'update'], [
            [PermissionMiddleware::class, 'LEAVE_EDIT', 'edit'],
        ]);
        $router->delete('/requests/{id}', [RequestController::class, 'destroy'], [
            [PermissionMiddleware::class, 'LEAVE_DELETE', 'delete'],
        ]);

        $router->get('/leave-requests', [LeaveController::class, 'leaveRequestIndex'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
            [HierarchyScopeMiddleware::class, 'employee_id', true],
        ]);
        $router->get('/leave-requests/{id}', [LeaveController::class, 'leaveRequestShow'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);
        $router->post('/leave-requests', [LeaveController::class, 'leaveRequestStore'], [
            [PermissionMiddleware::class, 'LEAVE_CREATE', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->patch('/leave-requests/{id}', [LeaveController::class, 'leaveRequestUpdate'], [
            [PermissionMiddleware::class, 'LEAVE_EDIT', 'edit'],
        ]);
        $router->delete('/leave-requests/{id}', [LeaveController::class, 'leaveRequestDelete'], [
            [PermissionMiddleware::class, 'LEAVE_DELETE', 'delete'],
        ]);
        $router->get('/leave-balances', [LeaveController::class, 'leaveBalanceIndex'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
            [HierarchyScopeMiddleware::class, 'employee_id', true],
        ]);
        $router->get('/leave-balances/{id}', [LeaveController::class, 'leaveBalanceShow'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);

        $router->get('/assets', [AssetController::class, 'assetIndex'], [
            [PermissionMiddleware::class, 'ASSET_VIEW', 'access'],
        ]);
        $router->get('/assets/{id}', [AssetController::class, 'assetShow'], [
            [PermissionMiddleware::class, 'ASSET_VIEW', 'access'],
        ]);
        $router->post('/assets', [AssetController::class, 'assetStore'], [
            [PermissionMiddleware::class, 'ASSET_CREATE', 'create'],
        ]);
        $router->put('/assets/{id}', [AssetController::class, 'assetUpdate'], [
            [PermissionMiddleware::class, 'ASSET_EDIT', 'edit'],
        ]);
        $router->patch('/assets/{id}', [AssetController::class, 'assetUpdate'], [
            [PermissionMiddleware::class, 'ASSET_EDIT', 'edit'],
        ]);
        $router->get('/asset-assignments', [AssetController::class, 'assignmentIndex'], [
            [PermissionMiddleware::class, 'ASSET_VIEW', 'access'],
        ]);
        $router->post('/asset-assignments', [AssetController::class, 'assignmentStore'], [
            [PermissionMiddleware::class, 'ASSET_ASSIGN', 'create'],
        ]);

        $router->get('/attendances', [AttendanceController::class, 'attendanceIndex'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
            [HierarchyScopeMiddleware::class, 'employee_id', true],
        ]);
        $router->get('/attendances/{id}', [AttendanceController::class, 'attendanceShow'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/attendances', [AttendanceController::class, 'attendanceStore'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->put('/attendances/{id}', [AttendanceController::class, 'attendanceUpdate'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);
        $router->patch('/attendances/{id}', [AttendanceController::class, 'attendanceUpdate'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);
        $router->delete('/attendances/{id}', [AttendanceController::class, 'attendanceDelete'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'delete'],
        ]);

        $router->get('/attendance/today-status', [AttendanceController::class, 'getTodayStatus'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/attendance/events', [AttendanceController::class, 'getEvents'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/attendance/adjust', [AttendanceController::class, 'adjust'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);

        $router->post('/attendance/bootstrap', [AttendanceRiskController::class, 'bootstrapDevice'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/attendance/precheck', [AttendanceRiskController::class, 'precheck'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/attendance/pre-check', [AttendanceRiskController::class, 'precheck'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/attendance/checkin', [AttendanceRiskController::class, 'checkin'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/attendance/clock-in', [AttendanceRiskController::class, 'checkin'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/attendance/checkout', [AttendanceRiskController::class, 'checkout'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/attendance/clock-out', [AttendanceRiskController::class, 'checkout'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/device/reverify', [AttendanceRiskController::class, 'reverifyDevice'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/exceptions/request', [AttendanceRiskController::class, 'requestException'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/attendance/exceptions', [AttendanceRiskController::class, 'requestException'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->post('/exceptions/{id}/approve-once', [AttendanceRiskController::class, 'approveExceptionOnce'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'approve'],
        ]);
        $router->post('/attendance/exceptions/{id}/approve-once', [AttendanceRiskController::class, 'approveExceptionOnce'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'approve'],
        ]);
        $router->get('/risk-alerts', [AttendanceRiskController::class, 'riskAlerts'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->get('/attendance/risk-alerts', [AttendanceRiskController::class, 'riskAlerts'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);

        $router->get('/overtime-requests', [AttendanceController::class, 'overtimeIndex'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
            [HierarchyScopeMiddleware::class, 'employee_id', true],
        ]);
        $router->get('/overtime-requests/{id}', [AttendanceController::class, 'overtimeShow'], [
            [PermissionMiddleware::class, 'ATTENDANCE_VIEW', 'access'],
        ]);
        $router->post('/overtime-requests', [AttendanceController::class, 'overtimeStore'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->put('/overtime-requests/{id}', [AttendanceController::class, 'overtimeUpdate'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);
        $router->patch('/overtime-requests/{id}', [AttendanceController::class, 'overtimeUpdate'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'edit'],
        ]);
        $router->delete('/overtime-requests/{id}', [AttendanceController::class, 'overtimeDelete'], [
            [PermissionMiddleware::class, 'ATTENDANCE_EDIT', 'delete'],
        ]);

        $router->get('/salary-periods', [PayrollController::class, 'periodIndex'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
        ]);
        $router->get('/salary-periods/{id}', [PayrollController::class, 'periodShow'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
        ]);
        $router->post('/salary-periods', [PayrollController::class, 'periodStore'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'create'],
        ]);
        $router->post('/salary-periods/{id}/calculate', [PayrollController::class, 'calculateByAttendance'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'create'],
        ]);
        $router->post('/salary-periods/{id}/calculate-and-store', [PayrollController::class, 'calculateAndStoreBatch'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'create'],
        ]);
        $router->put('/salary-periods/{id}', [PayrollController::class, 'periodUpdate'], [
            [PermissionMiddleware::class, 'SALARY_APPROVE', 'edit'],
        ]);
        $router->patch('/salary-periods/{id}', [PayrollController::class, 'periodUpdate'], [
            [PermissionMiddleware::class, 'SALARY_APPROVE', 'edit'],
        ]);
        $router->post('/salary-periods/{id}/close', [PayrollController::class, 'periodClose'], [
            [PermissionMiddleware::class, 'SALARY_APPROVE', 'approve'],
        ]);
        $router->delete('/salary-periods/{id}', [PayrollController::class, 'periodDestroy'], [
            [PermissionMiddleware::class, 'SALARY_APPROVE', 'delete'],
        ]);

        $router->get('/salary-details', [PayrollController::class, 'detailIndex'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
            [HierarchyScopeMiddleware::class, 'employee_id', true],
        ]);
        $router->get('/salary-details/{id}', [PayrollController::class, 'detailShow'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
        ]);
        $router->post('/salary-details/calculate-preview', [PayrollController::class, 'calculatePreview'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
        ]);
        $router->post('/salary-details', [PayrollController::class, 'detailStore'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->put('/salary-details/{id}', [PayrollController::class, 'detailUpdate'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'edit'],
        ]);
        $router->patch('/salary-details/{id}', [PayrollController::class, 'detailUpdate'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'edit'],
        ]);
        $router->delete('/salary-details/{id}', [PayrollController::class, 'detailDestroy'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'delete'],
        ]);

        $router->get('/salary-breakdowns', [PayrollController::class, 'breakdownIndex'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
        ]);
        $router->get('/salary-breakdowns/{id}', [PayrollController::class, 'breakdownShow'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
        ]);
        $router->post('/salary-breakdowns', [PayrollController::class, 'breakdownStore'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'create'],
        ]);
        $router->put('/salary-breakdowns/{id}', [PayrollController::class, 'breakdownUpdate'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'edit'],
        ]);
        $router->patch('/salary-breakdowns/{id}', [PayrollController::class, 'breakdownUpdate'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'edit'],
        ]);
        $router->delete('/salary-breakdowns/{id}', [PayrollController::class, 'breakdownDelete'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'delete'],
        ]);

        $router->get('/my-insurance-info', [PayrollController::class, 'myInsuranceInfo']);

        $router->get('/payroll-adjustments', [PayrollController::class, 'adjustmentIndex'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
            [HierarchyScopeMiddleware::class, 'employee_id', true],
        ]);
        $router->get('/payroll-adjustments/{id}', [PayrollController::class, 'adjustmentShow'], [
            [PermissionMiddleware::class, 'SALARY_VIEW', 'access'],
        ]);
        $router->post('/payroll-adjustments', [PayrollController::class, 'adjustmentStore'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'create'],
            [HierarchyEmployeeBodyMiddleware::class, 'employee_id', true],
        ]);
        $router->put('/payroll-adjustments/{id}', [PayrollController::class, 'adjustmentUpdate'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'edit'],
        ]);
        $router->patch('/payroll-adjustments/{id}', [PayrollController::class, 'adjustmentUpdate'], [
            [PermissionMiddleware::class, 'SALARY_CALCULATE', 'edit'],
        ]);

        $router->get('/news-categories', [CommunicationController::class, 'categoryIndex'], [
            [PermissionMiddleware::class, 'NEWS_VIEW', 'access'],
        ]);
        $router->get('/news-categories/{id}', [CommunicationController::class, 'categoryShow'], [
            [PermissionMiddleware::class, 'NEWS_VIEW', 'access'],
        ]);
        $router->post('/news-categories', [CommunicationController::class, 'categoryStore'], [
            [PermissionMiddleware::class, 'NEWS_CREATE', 'create'],
        ]);
        $router->put('/news-categories/{id}', [CommunicationController::class, 'categoryUpdate'], [
            [PermissionMiddleware::class, 'NEWS_EDIT', 'edit'],
        ]);
        $router->patch('/news-categories/{id}', [CommunicationController::class, 'categoryUpdate'], [
            [PermissionMiddleware::class, 'NEWS_EDIT', 'edit'],
        ]);
        $router->delete('/news-categories/{id}', [CommunicationController::class, 'categoryDelete'], [
            [PermissionMiddleware::class, 'NEWS_DELETE', 'delete'],
        ]);

        $router->get('/news', [CommunicationController::class, 'newsIndex'], [
            [PermissionMiddleware::class, 'NEWS_VIEW', 'access'],
        ]);
        $router->get('/news/{id}', [CommunicationController::class, 'newsShow'], [
            [PermissionMiddleware::class, 'NEWS_VIEW', 'access'],
        ]);
        $router->post('/news', [CommunicationController::class, 'newsStore'], [
            [PermissionMiddleware::class, 'NEWS_CREATE', 'create'],
        ]);
        $router->put('/news/{id}', [CommunicationController::class, 'newsUpdate'], [
            [PermissionMiddleware::class, 'NEWS_EDIT', 'edit'],
        ]);
        $router->patch('/news/{id}', [CommunicationController::class, 'newsUpdate'], [
            [PermissionMiddleware::class, 'NEWS_EDIT', 'edit'],
        ]);
        $router->delete('/news/{id}', [CommunicationController::class, 'newsDelete'], [
            [PermissionMiddleware::class, 'NEWS_DELETE', 'delete'],
        ]);
        $router->post('/news/{id}/read', [CommunicationController::class, 'newsMarkRead'], [
            [PermissionMiddleware::class, 'NEWS_VIEW', 'access'],
        ]);

        $router->get('/notifications', [NotificationController::class, 'index'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);
        $router->post('/notifications', [NotificationController::class, 'store'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);
        $router->patch('/notifications/{id}', [NotificationController::class, 'update'], [
            [PermissionMiddleware::class, 'LEAVE_VIEW', 'access'],
        ]);

        $router->get('/policies', [CommunicationController::class, 'policyIndex'], [
            [PermissionMiddleware::class, 'NEWS_VIEW', 'access'],
        ]);
        $router->get('/policies/{id}', [CommunicationController::class, 'policyShow'], [
            [PermissionMiddleware::class, 'NEWS_VIEW', 'access'],
        ]);
        $router->post('/policies', [CommunicationController::class, 'policyStore'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'create'],
        ]);
        $router->put('/policies/{id}', [CommunicationController::class, 'policyUpdate'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'edit'],
        ]);
        $router->patch('/policies/{id}', [CommunicationController::class, 'policyUpdate'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'edit'],
        ]);
        $router->delete('/policies/{id}', [CommunicationController::class, 'policyDelete'], [
            [PermissionMiddleware::class, 'SYSTEM_CONFIG', 'delete'],
        ]);
        $router->post('/policies/{id}/acknowledge', [CommunicationController::class, 'policyAcknowledge'], [
            [PermissionMiddleware::class, 'NEWS_VIEW', 'access'],
        ]);
    }, [AuthMiddleware::class]);
});
