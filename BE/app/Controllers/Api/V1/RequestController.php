<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Hierarchy;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Validator;
use App\Models\RequestModel;
use App\Services\WorkflowAuditService;
use App\Services\WorkflowTransitionGuardService;

class RequestController extends Controller
{
    private RequestModel $requests;
    private WorkflowTransitionGuardService $workflowTransitionGuard;
    private WorkflowAuditService $workflowAuditService;

    public function __construct()
    {
        $this->requests = new RequestModel();
        $this->workflowTransitionGuard = new WorkflowTransitionGuardService();
        $this->workflowAuditService = new WorkflowAuditService();
    }

    public function index(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $search = $request->query('q');
        $status = $request->query('status');
        $requesterId = $request->query('requester_id') !== null ? (int) $request->query('requester_id') : null;
        $requestTypeId = $request->query('request_type_id') !== null ? (int) $request->query('request_type_id') : null;
        $scopeIds = $request->attribute('scope_employee_ids');
        $requesterIds = is_array($scopeIds) ? $scopeIds : null;
        if ($requesterId !== null) {
            $requesterIds = [$requesterId];
        }

        $result = $this->requests->paginateList(
            $paging['offset'],
            $paging['per_page'],
            is_string($search) ? $search : null,
            is_string($status) ? $this->normalizeRequestStatus($status) : null,
            $requesterIds,
            $requestTypeId
        );

        return $this->ok(
            $result['items'],
            'Request list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function show(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $item = $this->requests->findDetail($id);
        if ($item === null) {
            throw new HttpException('Request not found', 404, 'not_found');
        }
        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $item['requester_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }
        return $this->ok($item, 'Request detail');
    }

    public function store(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'request_type_id' => ['required', 'integer'],
            'requester_id' => ['integer'],
            'request_date' => ['required', 'date'],
            'from_date' => ['date'],
            'to_date' => ['date'],
            'duration' => ['numeric'],
            'reason' => ['string'],
            'status' => ['string'],
            'current_step_id' => ['integer'],
            'is_urgent' => ['boolean'],
            'attachments' => ['string'],
            'notes' => ['string'],
        ]);

        if (!isset($payload['request_code'])) {
            $payload['request_code'] = 'REQ-' . date('Ymd-His') . '-' . random_int(100, 999);
        }

        if (isset($payload['status'])) {
            $payload['status'] = $this->normalizeRequestStatus((string) $payload['status']);
            $this->workflowTransitionGuard->assertTransitionAllowed('request', 'NHÁP', (string) $payload['status']);
        }

        $authUser = $request->attribute('auth_user');
        $payload['requester_id'] = (int) ($payload['requester_id'] ?? $request->attribute('forced_employee_id') ?? ($authUser['employee_id'] ?? 0));
        if ($payload['requester_id'] <= 0) {
            throw new HttpException('requester_id is required', 422, 'validation_error');
        }

        $payload['created_by'] = (int) ($authUser['employee_id'] ?? 1);
        $payload['updated_by'] = (int) ($authUser['employee_id'] ?? 1);

        $id = $this->requests->create($payload);
        $created = $this->requests->findDetail($id);
        $status = (string) (($created['status'] ?? $payload['status'] ?? 'NHÁP'));
        $this->workflowAuditService->recordTransition(
            'REQUEST',
            (string) $id,
            'NHÁP',
            $status,
            (int) ($authUser['employee_id'] ?? 0),
            [
                'requester_id' => (int) ($created['requester_id'] ?? $payload['requester_id']),
                'request_type_id' => (int) ($created['request_type_id'] ?? $payload['request_type_id']),
            ]
        );

        return $this->created($created, 'Request created');
    }

    public function update(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->requests->find($id);
        if ($existing === null) {
            throw new HttpException('Request not found', 404, 'not_found');
        }
        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $existing['requester_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        $payload = Validator::validate($request->all(), [
            'from_date' => ['date'],
            'to_date' => ['date'],
            'duration' => ['numeric'],
            'reason' => ['string'],
            'status' => ['string'],
            'current_step_id' => ['integer'],
            'is_urgent' => ['boolean'],
            'attachments' => ['string'],
            'notes' => ['string'],
            'completed_date' => ['date'],
        ]);

        if (isset($payload['status'])) {
            $payload['status'] = $this->normalizeRequestStatus((string) $payload['status']);
        }

        $previousStatus = $this->normalizeRequestStatus((string) ($existing['status'] ?? 'NHÁP'));
        $nextStatus = isset($payload['status']) ? (string) $payload['status'] : $previousStatus;
        if ($nextStatus !== $previousStatus) {
            $this->workflowTransitionGuard->assertTransitionAllowed('request', $previousStatus, $nextStatus);
        }

        $payload['updated_by'] = (int) ($authUser['employee_id'] ?? 1);

        $this->requests->updateById($id, $payload);
        $updated = $this->requests->findDetail($id);

        if ($nextStatus !== $previousStatus) {
            $this->workflowAuditService->recordTransition(
                'REQUEST',
                (string) $id,
                $previousStatus,
                $nextStatus,
                (int) ($authUser['employee_id'] ?? 0),
                [
                    'requester_id' => (int) ($existing['requester_id'] ?? 0),
                ]
            );
        }

        return $this->ok($updated, 'Request updated');
    }

    public function destroy(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->requests->find($id);
        if ($existing === null) {
            throw new HttpException('Request not found', 404, 'not_found');
        }
        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $existing['requester_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }
        $this->requests->deleteById($id);
        return $this->ok(null, 'Request deleted');
    }

    private function normalizeRequestStatus(string $status): string
    {
        $normalized = $this->normalizeStatusToken($status);

        return match (true) {
            $normalized === 'NHAP',
            $normalized === 'NH_P',
            $normalized === 'DRAFT' => 'NHÁP',
            str_contains($normalized, 'GIAM') && str_contains($normalized, 'DUY') => 'CHỜ_GIÁM_ĐỐC_DUYỆT',
            (str_contains($normalized, 'XAC') && str_contains($normalized, 'HR')),
            (str_starts_with($normalized, 'CH') && str_contains($normalized, 'HR')),
            in_array($normalized, ['WAIT_HR_CONFIRM', 'WAIT_HR'], true) => 'CHỜ_XÁC_NHẬN_HR',
            $normalized === 'ANG_X_L',
            str_contains($normalized, 'DANG') && (str_contains($normalized, 'XU') || str_contains($normalized, 'PROGRESS')) => 'ĐANG_XỬ_LÝ',
            in_array($normalized, ['CH_DUY_T', 'CHO_DUYET'], true),
            (str_contains($normalized, 'CH') && str_contains($normalized, 'DUY') && !str_contains($normalized, 'GIAM') && !str_contains($normalized, 'XAC')),
            in_array($normalized, ['PENDING', 'SUBMITTED'], true) => 'CHỜ_DUYỆT',
            in_array($normalized, ['DUY_T', 'DA_DUYET'], true),
            (str_contains($normalized, 'DA') && str_contains($normalized, 'DUY')),
            $normalized === 'APPROVED' => 'ĐÃ_DUYỆT',
            $normalized === 'T_CH_I',
            str_contains($normalized, 'CHOI'),
            $normalized === 'REJECTED' => 'TỪ_CHỐI',
            in_array($normalized, ['H_Y', 'DA_HUY'], true),
            str_contains($normalized, 'HUY'),
            in_array($normalized, ['CANCELED', 'CANCELLED'], true) => 'ĐÃ_HỦY',
            in_array($normalized, ['HO_N_TH_NH', 'HOAN_THANH'], true),
            str_contains($normalized, 'HOAN'),
            in_array($normalized, ['DONE', 'COMPLETED'], true) => 'HOÀN_THÀNH',
            default => 'CHỜ_DUYỆT',
        };
    }

    private function normalizeStatusToken(string $status): string
    {
        $upper = mb_strtoupper(trim($status), 'UTF-8');
        $ascii = strtr($upper, [
            'À' => 'A', 'Á' => 'A', 'Ả' => 'A', 'Ã' => 'A', 'Ạ' => 'A',
            'Ă' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ẳ' => 'A', 'Ẵ' => 'A', 'Ặ' => 'A',
            'Â' => 'A', 'Ầ' => 'A', 'Ấ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ậ' => 'A',
            'Đ' => 'D',
            'È' => 'E', 'É' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ẹ' => 'E',
            'Ê' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ể' => 'E', 'Ễ' => 'E', 'Ệ' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ỉ' => 'I', 'Ĩ' => 'I', 'Ị' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ỏ' => 'O', 'Õ' => 'O', 'Ọ' => 'O',
            'Ô' => 'O', 'Ồ' => 'O', 'Ố' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ộ' => 'O',
            'Ơ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O', 'Ợ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ủ' => 'U', 'Ũ' => 'U', 'Ụ' => 'U',
            'Ư' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ự' => 'U',
            'Ỳ' => 'Y', 'Ý' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y', 'Ỵ' => 'Y',
        ]);

        $token = preg_replace('/[^A-Z0-9]+/', '_', $ascii) ?? '';
        return trim($token, '_');
    }
}
