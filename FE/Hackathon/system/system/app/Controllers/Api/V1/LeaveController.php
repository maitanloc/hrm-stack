<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Hierarchy;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Validator;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\RequestModel;

class LeaveController extends Controller
{
    private LeaveRequest $leaveRequests;
    private LeaveBalance $leaveBalances;
    private RequestModel $requests;

    public function __construct()
    {
        $this->leaveRequests = new LeaveRequest();
        $this->leaveBalances = new LeaveBalance();
        $this->requests = new RequestModel();
    }

    public function leaveRequestIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $scopeEmployeeIds = $request->attribute('scope_employee_ids');
        $employeeId = $request->query('employee_id') !== null ? (int) $request->query('employee_id') : null;
        $leaveTypeId = $request->query('leave_type_id') !== null ? (int) $request->query('leave_type_id') : null;
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $employeeIds = is_array($scopeEmployeeIds) ? $scopeEmployeeIds : null;
        if ($employeeId !== null) {
            $employeeIds = [$employeeId];
        }

        $result = $this->leaveRequests->paginateList(
            $paging['offset'],
            $paging['per_page'],
            $employeeIds,
            $leaveTypeId,
            is_string($dateFrom) ? $dateFrom : null,
            is_string($dateTo) ? $dateTo : null
        );

        return $this->ok(
            $result['items'],
            'Leave request list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function leaveRequestShow(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $item = $this->leaveRequests->find($id);
        if ($item === null) {
            throw new HttpException('Leave request not found', 404, 'not_found');
        }
        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $item['employee_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }
        return $this->ok($item, 'Leave request detail');
    }

    public function leaveRequestStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'requester_id' => ['required', 'integer'],
            'request_date' => ['required', 'date'],
            'leave_type_id' => ['required', 'integer'],
            'employee_id' => ['required', 'integer'],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'number_of_days' => ['required', 'numeric', 'min:0.5'],
            'reason' => ['string'],
            'is_urgent' => ['boolean'],
            'from_session' => ['string'],
            'to_session' => ['string'],
            'leave_used_type' => ['string'],
            'base_days_used' => ['numeric'],
            'seniority_days_used' => ['numeric'],
            'carried_over_days_used' => ['numeric'],
            'paid_days' => ['numeric'],
            'unpaid_days' => ['numeric'],
        ]);

        if ((string) $payload['from_date'] > (string) $payload['to_date']) {
            throw new HttpException('from_date must be <= to_date', 422, 'validation_error');
        }

        $authUser = $request->attribute('auth_user');
        $creatorId = (int) ($authUser['employee_id'] ?? 1);

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $requestData = [
                'request_code' => 'LV-' . date('Ymd-His') . '-' . random_int(100, 999),
                'request_type_id' => (int) ($request->input('request_type_id', 1)),
                'requester_id' => (int) $payload['requester_id'],
                'request_date' => $payload['request_date'],
                'from_date' => $payload['from_date'],
                'to_date' => $payload['to_date'],
                'duration' => $payload['number_of_days'],
                'reason' => $payload['reason'] ?? null,
                'is_urgent' => $payload['is_urgent'] ?? 0,
                'status' => $request->input('status', 'CHO_DUYET'),
                'created_by' => $creatorId,
                'updated_by' => $creatorId,
            ];

            if ($requestData['status'] === 'CHO_DUYET') {
                $requestData['status'] = 'CHỜ_DUYỆT';
            }

            $requestId = $this->requests->create($requestData);

            $leaveData = [
                'request_id' => $requestId,
                'leave_type_id' => (int) $payload['leave_type_id'],
                'employee_id' => (int) $payload['employee_id'],
                'from_date' => $payload['from_date'],
                'to_date' => $payload['to_date'],
                'from_session' => $payload['from_session'] ?? 'CẢ_NGÀY',
                'to_session' => $payload['to_session'] ?? 'CẢ_NGÀY',
                'number_of_days' => $payload['number_of_days'],
                'leave_used_type' => $payload['leave_used_type'] ?? 'BASE',
                'base_days_used' => $payload['base_days_used'] ?? 0,
                'seniority_days_used' => $payload['seniority_days_used'] ?? 0,
                'carried_over_days_used' => $payload['carried_over_days_used'] ?? 0,
                'paid_days' => $payload['paid_days'] ?? $payload['number_of_days'],
                'unpaid_days' => $payload['unpaid_days'] ?? 0,
                'handover_notes' => $request->input('handover_notes'),
                'contact_phone' => $request->input('contact_phone'),
                'emergency_contact' => $request->input('emergency_contact'),
                'attachment_url' => $request->input('attachment_url'),
            ];

            $leaveRequestId = $this->leaveRequests->create($leaveData);
            $db->commit();

            return $this->created(
                [
                    'request' => $this->requests->findDetail($requestId),
                    'leave_request' => $this->leaveRequests->find($leaveRequestId),
                ],
                'Leave request created'
            );
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function leaveBalanceIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $scopeEmployeeIds = $request->attribute('scope_employee_ids');
        $employeeId = $request->query('employee_id') !== null ? (int) $request->query('employee_id') : null;
        $year = $request->query('year') !== null ? (int) $request->query('year') : null;
        $employeeIds = is_array($scopeEmployeeIds) ? $scopeEmployeeIds : null;
        if ($employeeId !== null) {
            $employeeIds = [$employeeId];
        }

        $result = $this->leaveBalances->paginateList(
            $paging['offset'],
            $paging['per_page'],
            $employeeIds,
            $year
        );

        return $this->ok(
            $result['items'],
            'Leave balance list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function leaveBalanceShow(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $item = $this->leaveBalances->find($id);
        if ($item === null) {
            throw new HttpException('Leave balance not found', 404, 'not_found');
        }
        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $item['employee_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }
        return $this->ok($item, 'Leave balance detail');
    }
}
