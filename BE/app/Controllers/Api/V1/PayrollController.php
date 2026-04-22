<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Hierarchy;
use App\Core\HttpException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Contract;
use App\Models\PayrollAdjustment;
use App\Models\SalaryBreakdown;
use App\Models\SalaryDetail;
use App\Models\SalaryPeriod;
use App\Services\PayrollCalculatorService;
use Throwable;

class PayrollController extends Controller
{
    private SalaryPeriod $periods;
    private SalaryDetail $details;
    private SalaryBreakdown $breakdowns;
    private PayrollAdjustment $adjustments;
    private PayrollCalculatorService $calculator;

    public function __construct()
    {
        $this->periods    = new SalaryPeriod();
        $this->details    = new SalaryDetail();
        $this->breakdowns = new SalaryBreakdown();
        $this->adjustments = new PayrollAdjustment();
        $this->calculator = new PayrollCalculatorService();
    }

    public function create(Request $request): array
    {
        $path = $request->path();
        if (str_contains($path, 'salary-periods')) {
            return $this->periodStore($request);
        }
        if (str_contains($path, 'salary-details')) {
            return $this->detailStore($request);
        }
        throw new HttpException("Method create not handled for path: $path", 405, 'method_not_allowed');
    }

    /**
     * Auto-calculate total_deductions and net_salary based on salary components
     * Formula:
     *   total_deductions = BHXH + BHYT + BHTN + Tax + Penalty + Advance
     *   net_salary = gross_salary - total_deductions
     */
    private function recalculateSalary(array &$payload): void
    {
        $bhxh = (float) ($payload['social_insurance_employee'] ?? 0);
        $bhyt = (float) ($payload['health_insurance_employee'] ?? 0);
        $bhtn = (float) ($payload['unemployment_insurance_employee'] ?? 0);
        $tax = (float) ($payload['personal_income_tax'] ?? 0);
        $penalty = (float) ($payload['penalty'] ?? 0);
        $advance = (float) ($payload['advance_payment'] ?? 0);
        $gross = (float) ($payload['gross_salary'] ?? 0);

        // Recalculate total deductions
        $totalDeductions = $bhxh + $bhyt + $bhtn + $tax + $penalty + $advance;
        $payload['total_deductions'] = $totalDeductions;

        // Recalculate net salary
        $netSalary = max(0, $gross - $totalDeductions);
        $payload['net_salary'] = $netSalary;
    }

    public function periodIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $year = $request->query('year') !== null ? (int) $request->query('year') : null;
        $status = $request->query('status');

        $result = $this->periods->paginateList(
            $paging['offset'],
            $paging['per_page'],
            $year,
            is_string($status) ? $status : null
        );

        return $this->ok(
            $result['items'],
            'Salary period list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function periodShow(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $item = $this->periods->findDetail($id);
        if ($item === null) {
            throw new HttpException('Salary period not found', 404, 'not_found');
        }
        return $this->ok($item, 'Salary period detail');
    }

    public function periodStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'period_code' => ['required', 'string'],
            'period_name' => ['required', 'string'],
            'period_type' => ['required', 'string'],
            'year' => ['required', 'integer'],
            'month' => ['integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'payment_date' => ['date'],
            'standard_working_days' => ['integer'],
            'status' => ['string'],
            'notes' => ['string'],
        ]);
        $id = $this->periods->create($payload);
        return $this->created($this->periods->findDetail($id), 'Salary period created');
    }

    public function periodUpdate(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->periods->find($id) === null) {
            throw new HttpException('Salary period not found', 404, 'not_found');
        }
        $payload = Validator::validate($request->all(), [
            'period_name' => ['string'],
            'period_type' => ['string'],
            'year' => ['integer'],
            'month' => ['integer'],
            'start_date' => ['date'],
            'end_date' => ['date'],
            'payment_date' => ['date'],
            'standard_working_days' => ['integer'],
            'status' => ['string'],
            'notes' => ['string'],
            'closed_by' => ['integer'],
            'closed_date' => ['date'],
        ]);
        $this->periods->updateById($id, $payload);
        return $this->ok($this->periods->findDetail($id), 'Salary period updated');
    }

    public function periodClose(Request $request, array $params): array
    {
        $periodId = (int) ($params['id'] ?? 0);
        $period = $this->periods->findDetail($periodId);
        if ($period === null) {
            throw new HttpException('Salary period not found', 404, 'not_found');
        }

        $status = strtoupper((string) ($period['status'] ?? ''));
        if (in_array($status, ['CLOSED', 'PAID'], true)) {
            throw new HttpException('Salary period already finalized', 422, 'validation_error');
        }

        $monthKey = $this->resolveApplyMonthByPeriod($period);
        $details = $this->details->listByPeriodId($periodId);

        $db = Database::connection();
        $updatedEmployees = 0;
        $updatedAdjustments = 0;
        $updatedAmount = 0.0;
        $authUser = $request->attribute('auth_user');

        try {
            $db->beginTransaction();

            foreach ($details as $detail) {
                $salaryDetailId = (int) ($detail['salary_detail_id'] ?? 0);
                $employeeId = (int) ($detail['employee_id'] ?? 0);
                if ($salaryDetailId <= 0 || $employeeId <= 0) {
                    continue;
                }

                $pendingAdjustments = $this->adjustments->listPendingByEmployeeMonth($employeeId, $monthKey);
                if ($pendingAdjustments === []) {
                    continue;
                }

                $adjustmentIds = [];
                $sum = 0.0;
                foreach ($pendingAdjustments as $adjustment) {
                    $adjustmentIds[] = (int) $adjustment['adjustment_id'];
                    $sum += (float) ($adjustment['amount'] ?? 0);
                }

                if ($sum == 0.0) {
                    $updatedAdjustments += $this->adjustments->markPaidByIds($adjustmentIds, $salaryDetailId, $periodId);
                    continue;
                }

                $currentGross = (float) ($detail['gross_salary'] ?? 0);
                $currentNet = (float) ($detail['net_salary'] ?? 0);
                $currentAllowances = (float) ($detail['total_allowances'] ?? 0);
                $currentDeductions = (float) ($detail['total_deductions'] ?? 0);

                $allowanceDelta = $sum > 0 ? $sum : 0.0;
                $deductionDelta = $sum < 0 ? abs($sum) : 0.0;

                $this->details->updateById($salaryDetailId, [
                    'gross_salary' => $currentGross + $allowanceDelta,
                    'net_salary' => $currentNet + $sum,
                    'total_allowances' => $currentAllowances + $allowanceDelta,
                    'total_deductions' => $currentDeductions + $deductionDelta,
                ]);

                $updatedAdjustments += $this->adjustments->markPaidByIds($adjustmentIds, $salaryDetailId, $periodId);
                $updatedEmployees++;
                $updatedAmount += $sum;
            }

            $this->periods->updateById($periodId, [
                'status' => 'CLOSED',
                'closed_by' => (int) ($authUser['employee_id'] ?? 0),
                'closed_date' => date('Y-m-d H:i:s'),
            ]);

            $db->commit();
        } catch (Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            if ($exception instanceof HttpException) {
                throw $exception;
            }

            throw new HttpException('Failed to close salary period: ' . $exception->getMessage(), 500, 'server_error');
        }

        return $this->ok([
            'period_id' => $periodId,
            'apply_month' => $monthKey,
            'employees_updated' => $updatedEmployees,
            'adjustments_marked_paid' => $updatedAdjustments,
            'adjustment_amount_total' => round($updatedAmount, 2),
            'period_status' => 'CLOSED',
        ], 'Salary period closed successfully');
    }

    public function periodDestroy(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->periods->findDetail($id);
        if ($existing === null) {
            throw new HttpException('Salary period not found', 404, 'not_found');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            // Xóa tất cả phiếu lương của kỳ này
            $stmt1 = $db->prepare('DELETE FROM salary_details WHERE period_id = :id');
            $stmt1->execute(['id' => $id]);

            // Xóa tóm tắt chấm công của kỳ này
            $stmt2 = $db->prepare('DELETE FROM salary_attendance_summary WHERE period_id = :id');
            $stmt2->execute(['id' => $id]);

            // Cuối cùng xóa kỳ lương
            $stmt3 = $db->prepare('DELETE FROM salary_periods WHERE period_id = :id');
            $stmt3->execute(['id' => $id]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw new HttpException('Lỗi khi xóa kỳ lương: ' . $e->getMessage(), 500);
        }

        return $this->ok([], 'Đã xóa kỳ lương và toàn bộ dữ liệu đi kèm thành công');
    }

    public function detailIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $scopeIds = $request->attribute('scope_employee_ids');
        $periodId = $request->query('period_id') !== null ? (int) $request->query('period_id') : null;
        $transferStatus = $request->query('transfer_status');

        $result = $this->details->paginateList(
            $paging['offset'],
            $paging['per_page'],
            $periodId,
            is_array($scopeIds) ? $scopeIds : null,
            is_string($transferStatus) ? $transferStatus : null
        );
        return $this->ok(
            $result['items'],
            'Salary detail list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function detailShow(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $item = $this->details->findDetail($id);
        if ($item === null) {
            throw new HttpException('Salary detail not found', 404, 'not_found');
        }

        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $item['employee_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        $item['payroll_adjustments'] = $this->adjustments->listAppliedBySalaryDetail($id);
        return $this->ok($item, 'Salary detail detail');
    }

    public function detailStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'period_id' => ['required', 'integer'],
            'employee_id' => ['integer'],
            'contract_id' => ['integer'],
            'basic_salary' => ['required', 'numeric'],
            'gross_salary' => ['required', 'numeric'],
            'net_salary' => ['numeric'],
            'total_allowances' => ['numeric'],
            'total_deductions' => ['numeric'],
            'overtime_pay' => ['numeric'],
            'leave_pay' => ['numeric'],
            'bonus' => ['numeric'],
            'penalty' => ['numeric'],
            'personal_income_tax' => ['numeric'],
            'advance_payment' => ['numeric'],
            'bank_account' => ['string'],
            'bank_name' => ['string'],
            'transfer_status' => ['string'],
            'notes' => ['string'],
            'social_insurance_employee' => ['numeric'],
            'health_insurance_employee' => ['numeric'],
            'unemployment_insurance_employee' => ['numeric'],
        ]);

        $authUser = $request->attribute('auth_user');
        $employeeId = (int) ($payload['employee_id'] ?? $request->attribute('forced_employee_id') ?? ($authUser['employee_id'] ?? 0));
        if ($employeeId <= 0) {
            throw new HttpException('employee_id is required', 422, 'validation_error');
        }
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, $employeeId, true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        $payload['employee_id'] = $employeeId;
        
        // Auto-calculate total_deductions and net_salary
        $this->recalculateSalary($payload);
        
        $id = $this->details->create($payload);
        return $this->created($this->details->findDetail($id), 'Salary detail created');
    }

    public function detailUpdate(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->details->findDetail($id);
        if ($existing === null) {
            throw new HttpException('Salary detail not found', 404, 'not_found');
        }

        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $existing['employee_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        $payload = Validator::validate($request->all(), [
            'contract_id' => ['integer'],
            'basic_salary' => ['numeric'],
            'gross_salary' => ['numeric'],
            'net_salary' => ['numeric'],
            'total_allowances' => ['numeric'],
            'total_deductions' => ['numeric'],
            'overtime_pay' => ['numeric'],
            'leave_pay' => ['numeric'],
            'bonus' => ['numeric'],
            'penalty' => ['numeric'],
            'personal_income_tax' => ['numeric'],
            'advance_payment' => ['numeric'],
            'bank_account' => ['string'],
            'bank_name' => ['string'],
            'transfer_status' => ['string'],
            'transfer_date' => ['date'],
            'notes' => ['string'],
            'social_insurance_employee' => ['numeric'],
            'health_insurance_employee' => ['numeric'],
            'unemployment_insurance_employee' => ['numeric'],
        ]);
        
        // Merge with existing data for full calculation
        $mergedPayload = array_merge($existing, $payload);
        
        // Auto-calculate if any deduction or gross_salary changed
        if (count($payload) > 0 && isset($payload['gross_salary'], $payload['personal_income_tax']) 
            || isset($payload['penalty'], $payload['social_insurance_employee'], $payload['health_insurance_employee'])) {
            $this->recalculateSalary($mergedPayload);
            $payload = $mergedPayload;
        }
        
        $this->details->updateById($id, $payload);
        return $this->ok($this->details->findDetail($id), 'Salary detail updated');
    }

    public function detailDestroy(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->details->findDetail($id);
        if ($existing === null) {
            throw new HttpException('Salary detail not found', 404, 'not_found');
        }

        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $existing['employee_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        $period = $this->periods->findDetail((int) $existing['period_id']);
        if ($period) {
            $status = strtoupper((string) ($period['status'] ?? ''));
            if (in_array($status, ['CLOSED', 'PAID'], true)) {
                throw new HttpException('Không thể xóa phiếu lương của kỳ lương đã chốt', 422, 'validation_error');
            }
        }

        $this->details->deleteById($id);
        return $this->ok(null, 'Salary detail deleted');
    }

    public function breakdownIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $salaryDetailId = $request->query('salary_detail_id') !== null ? (int) $request->query('salary_detail_id') : null;
        $itemType = $request->query('item_type');

        $result = $this->breakdowns->paginateList(
            $paging['offset'],
            $paging['per_page'],
            $salaryDetailId,
            is_string($itemType) ? $itemType : null
        );
        return $this->ok(
            $result['items'],
            'Salary breakdown list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function breakdownShow(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $item = $this->breakdowns->findDetail($id);
        if ($item === null) {
            throw new HttpException('Salary breakdown not found', 404, 'not_found');
        }
        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $item['employee_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }
        return $this->ok($item, 'Salary breakdown detail');
    }

    public function breakdownStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'salary_detail_id' => ['required', 'integer'],
            'item_type' => ['required', 'string'],
            'item_id' => ['integer'],
            'item_name' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'is_taxable' => ['boolean'],
            'is_insurable' => ['boolean'],
            'description' => ['string'],
        ]);
        $id = $this->breakdowns->create($payload);
        return $this->created($this->breakdowns->findDetail($id), 'Salary breakdown created');
    }

    public function breakdownUpdate(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->breakdowns->find($id) === null) {
            throw new HttpException('Salary breakdown not found', 404, 'not_found');
        }
        $payload = Validator::validate($request->all(), [
            'item_type' => ['string'],
            'item_id' => ['integer'],
            'item_name' => ['string'],
            'amount' => ['numeric'],
            'is_taxable' => ['boolean'],
            'is_insurable' => ['boolean'],
            'description' => ['string'],
        ]);
        $this->breakdowns->updateById($id, $payload);
        return $this->ok($this->breakdowns->findDetail($id), 'Salary breakdown updated');
    }

    public function breakdownDelete(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        if ($this->breakdowns->find($id) === null) {
            throw new HttpException('Salary breakdown not found', 404, 'not_found');
        }
        $this->breakdowns->deleteById($id);
        return $this->ok(null, 'Salary breakdown deleted');
    }

    public function adjustmentIndex(Request $request): array
    {
        $paging = Paginator::resolve($request);
        $scopeIds = $request->attribute('scope_employee_ids');
        $employeeId = $request->query('employee_id') !== null ? (int) $request->query('employee_id') : null;
        $applyMonth = $request->query('apply_month');
        $status = $request->query('status');
        $statusInt = null;
        if ($status !== null && $status !== '') {
            if (!in_array((string) $status, ['0', '1'], true)) {
                throw new HttpException('status must be 0 or 1', 422, 'validation_error');
            }
            $statusInt = (int) $status;
        }

        if ($applyMonth !== null && $applyMonth !== '') {
            $this->assertApplyMonth((string) $applyMonth);
        }

        $result = $this->adjustments->paginateList(
            $paging['offset'],
            $paging['per_page'],
            $employeeId,
            is_string($applyMonth) ? $applyMonth : null,
            $statusInt,
            is_array($scopeIds) ? $scopeIds : null
        );

        return $this->ok(
            $result['items'],
            'Payroll adjustment list',
            Paginator::meta($result['total'], $paging['page'], $paging['per_page'])
        );
    }

    public function adjustmentShow(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $item = $this->adjustments->findDetail($id);
        if ($item === null) {
            throw new HttpException('Payroll adjustment not found', 404, 'not_found');
        }

        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $item['employee_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        return $this->ok($item, 'Payroll adjustment detail');
    }

    public function adjustmentStore(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'employee_id' => ['integer'],
            'amount' => ['required', 'numeric'],
            'description' => ['required', 'string'],
            'apply_month' => ['required', 'string'],
        ]);

        $this->assertApplyMonth((string) ($payload['apply_month'] ?? ''));

        $authUser = $request->attribute('auth_user');
        $employeeId = (int) ($payload['employee_id'] ?? $request->attribute('forced_employee_id') ?? ($authUser['employee_id'] ?? 0));
        if ($employeeId <= 0) {
            throw new HttpException('employee_id is required', 422, 'validation_error');
        }
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, $employeeId, true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        $payload['employee_id'] = $employeeId;
        $payload['status'] = 0;
        $payload['paid_salary_detail_id'] = null;
        $payload['paid_period_id'] = null;
        $payload['paid_at'] = null;

        $id = $this->adjustments->create($payload);
        return $this->created($this->adjustments->findDetail($id), 'Payroll adjustment created');
    }

    public function adjustmentUpdate(Request $request, array $params): array
    {
        $id = (int) ($params['id'] ?? 0);
        $existing = $this->adjustments->findDetail($id);
        if ($existing === null) {
            throw new HttpException('Payroll adjustment not found', 404, 'not_found');
        }

        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, (int) $existing['employee_id'], true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }
        if ((int) ($existing['status'] ?? 0) === 1) {
            throw new HttpException('Cannot update an adjustment that has already been paid', 422, 'validation_error');
        }

        $payload = Validator::validate($request->all(), [
            'amount' => ['numeric'],
            'description' => ['string'],
            'apply_month' => ['string'],
        ]);

        if (isset($payload['apply_month'])) {
            $this->assertApplyMonth((string) $payload['apply_month']);
        }

        $this->adjustments->updateById($id, $payload);
        return $this->ok($this->adjustments->findDetail($id), 'Payroll adjustment updated');
    }

    // =========================================================
    // PAYROLL CALCULATION FROM ATTENDANCE
    // =========================================================

    /**
     * POST /salary-periods/{id}/calculate
     * Tính lương từ dữ liệu chấm công cho TẤT CẢ nhân viên trong period.
     * Kết quả trả về danh sách, không ghi DB (preview trước khi tạo salary_details).
     */
    public function calculateByAttendance(Request $request, array $params): array
    {
        $periodId = (int) ($params['id'] ?? 0);
        $period   = $this->periods->findDetail($periodId);
        if ($period === null) {
            throw new HttpException('Salary period not found', 404, 'not_found');
        }

        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser)) {
            throw new HttpException('Chỉ admin/HR mới được tính lương', 403, 'forbidden');
        }

        $congChuan = (int) ($period['standard_working_days'] ?? 26);
        if ($congChuan <= 0) {
            $congChuan = 26;
        }

        $dateFrom = (string) ($period['start_date'] ?? '');
        $dateTo   = (string) ($period['end_date']   ?? '');

        if ($dateFrom === '' || $dateTo === '') {
            throw new HttpException('Period start_date / end_date is required', 422, 'validation_error');
        }

        $results = $this->calculator->calculateBatch($dateFrom, $dateTo, $congChuan);

        return $this->ok([
            'period_id'   => $periodId,
            'period_code' => $period['period_code'],
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'cong_chuan'  => $congChuan,
            'total_employees' => count($results),
            'results'     => array_values($results),
        ], 'Payroll calculated from attendance');
    }

    /**
     * POST /salary-periods/{id}/calculate-and-store
     * Tính lương và LƯU TRỰC TIẾP vào bảng salary_details cho tất cả nhân viên.
     */
    public function calculateAndStoreBatch(Request $request, array $params): array
    {
        $periodId = (int) ($params['id'] ?? 0);
        $period   = $this->periods->findDetail($periodId);
        if ($period === null) {
            throw new HttpException('Salary period not found', 404, 'not_found');
        }

        $authUser = $request->attribute('auth_user');
        if (!Auth::isPrivileged($authUser)) {
            throw new HttpException('Chỉ admin/HR mới được tính lương', 403, 'forbidden');
        }

        $congChuan = (int) ($period['standard_working_days'] ?? 26);
        $dateFrom = (string) ($period['start_date'] ?? '');
        $dateTo   = (string) ($period['end_date']   ?? '');

        $results = $this->calculator->calculateBatch($dateFrom, $dateTo, $congChuan);

        $db = Database::connection();
        $count = 0;
        foreach ($results as $item) {
            try {
                $db->beginTransaction();
                
                // Kiểm tra xem đã có bản ghi chưa
                $stmtExist = $db->prepare("SELECT salary_detail_id FROM salary_details WHERE period_id = ? AND employee_id = ?");
                $stmtExist->execute([$periodId, $item['employee_id']]);
                if ($stmtExist->fetch()) {
                    $db->rollBack();
                    continue; 
                }

                $basicSalary = (float)$item['luong_thang'];
                $gross = (float)$item['luong_cong'] + (float)$item['tien_ot'] + (float)$item['tien_bhxh'];
                
                // Khấu trừ
                $ins = (float)($item['social_insurance_employee'] + $item['health_insurance_employee'] + $item['unemployment_insurance_employee']);
                $penalty = (float)($item['tien_tru_muon_som'] + $item['tien_tru_khong_phep']);
                $tax = (float)$item['personal_income_tax'];
                $totalDeductions = $ins + $penalty + $tax;
                $net = max(0, $gross - $totalDeductions);

                $this->details->create([
                    'period_id' => $periodId,
                    'employee_id' => $item['employee_id'],
                    'basic_salary' => $basicSalary,
                    'gross_salary' => $gross,
                    'net_salary' => $net,
                    'total_allowances' => max(0, $gross - $basicSalary),
                    'total_deductions' => $totalDeductions,
                    'overtime_pay' => $item['tien_ot'],
                    'leave_pay' => $item['tien_bhxh'],
                    'penalty' => $penalty,
                    'personal_income_tax' => $tax,
                    'social_insurance_employee' => $item['social_insurance_employee'],
                    'health_insurance_employee' => $item['health_insurance_employee'],
                    'unemployment_insurance_employee' => $item['unemployment_insurance_employee'],
                    'transfer_status' => 'PENDING'
                ]);
                $db->commit();
                $count++;
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                // Ghi log lỗi nhẹ nhàng và tiếp tục người tiếp theo
                error_log("Payroll Batch Error for Employee {$item['employee_id']}: " . $e->getMessage());
            }
        }

        return $this->ok(['inserted_count' => $count], "Đã tự động tính và lưu $count phiếu lương.");
    }

    /**
     * POST /salary-details/calculate-preview
     * Tính lương preview cho 1 nhân viên cụ thể theo khoảng ngày và lương tuỳ nhập.
     * Nếu luong_thang không truyền hoặc = 0, tự động lấy từ hợp đồng đang hiệu lực.
     */
    public function calculatePreview(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'employee_id'  => ['required', 'integer'],
            'date_from'    => ['required', 'date'],
            'date_to'      => ['required', 'date'],
            'luong_thang'  => ['numeric'],
            'luong_bhxh'   => ['numeric'],
            'cong_chuan'   => ['integer'],
        ]);

        $authUser  = $request->attribute('auth_user');
        $employeeId = (int) $payload['employee_id'];

        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, $employeeId, true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        $luongThang = (float) ($payload['luong_thang'] ?? 0);
        $luongBhxh  = (float) ($payload['luong_bhxh'] ?? 0);

        // Tự động lấy lương từ hợp đồng nếu không truyền hoặc = 0
        if ($luongThang <= 0) {
            $db = Database::connection();
            // Ưu tiên hợp đồng CÓ_HIỆU_LỰC, nếu không có thì lấy cái mới nhất
            $stmt = $db->prepare(
                "SELECT basic_salary, gross_salary, status
                 FROM contracts
                 WHERE employee_id = :eid
                 ORDER BY (status = 'CÓ_HIỆU_LỰC') DESC, effective_date DESC, created_at DESC
                 LIMIT 1"
            );
            $stmt->execute(['eid' => $employeeId]);
            $contract = $stmt->fetch();
            if ($contract) {
                $luongThang = (float) ($contract['basic_salary'] ?? 0);
                if ($luongBhxh <= 0) {
                    $luongBhxh = (float) ($contract['gross_salary'] ?? $luongThang);
                }
            }
        }

        if ($luongBhxh <= 0) {
            $luongBhxh = $luongThang;
        }

        $congChuan  = (int) ($payload['cong_chuan'] ?? 26);
        if ($congChuan <= 0) {
            $congChuan = 26;
        }

        $result = $this->calculator->calculate(
            $employeeId,
            (string) $payload['date_from'],
            (string) $payload['date_to'],
            $luongThang,
            $luongBhxh,
            $congChuan
        );

        return $this->ok($result, 'Payroll preview calculated');
    }

    private function assertApplyMonth(string $value): void
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
            throw new HttpException('apply_month must be in YYYY-MM format', 422, 'validation_error');
        }
    }

    private function resolveApplyMonthByPeriod(array $period): string
    {
        $year = isset($period['year']) ? (int) $period['year'] : 0;
        $month = isset($period['month']) ? (int) $period['month'] : 0;
        if ($year > 0 && $month >= 1 && $month <= 12) {
            return sprintf('%04d-%02d', $year, $month);
        }

        $startDate = (string) ($period['start_date'] ?? '');
        if ($startDate === '' || strtotime($startDate) === false) {
            throw new HttpException('Salary period start_date is invalid', 422, 'validation_error');
        }

        return date('Y-m', strtotime($startDate));
    }

    /**
     * GET /my-insurance-info
     * Returns the social insurance info for the currently authenticated employee.
     */
    public function myInsuranceInfo(Request $request): array
    {
        $userId = Auth::id();
        $db = Database::connection();

        $stmt = $db->prepare(
            "SELECT
                si.social_insurance_number,
                si.health_insurance_number,
                si.tax_code,
                si.issue_date,
                si.issue_place,
                si.status,
                e.hire_date,
                e.seniority_start_date,
                e.full_name
             FROM social_insurance_info si
             JOIN employees e ON e.employee_id = si.employee_id
             WHERE si.employee_id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['success' => true, 'data' => null];
        }

        // Tính thời gian tham gia bảo hiểm từ hire_date
        $startDate = $row['seniority_start_date'] ?? $row['hire_date'];
        $totalMonths = 0;
        if ($startDate) {
            $from = new \DateTime($startDate);
            $now  = new \DateTime();
            $diff = $from->diff($now);
            $totalMonths = ($diff->y * 12) + $diff->m;
        }
        $years  = intdiv($totalMonths, 12);
        $months = $totalMonths % 12;

        return [
            'success' => true,
            'data' => [
                'socialInsuranceNumber'  => $row['social_insurance_number'],
                'healthInsuranceNumber'  => $row['health_insurance_number'],
                'taxCode'                => $row['tax_code'],
                'issueDate'              => $row['issue_date'],
                'issuePlace'             => $row['issue_place'],
                'status'                 => $row['status'],
                'hireDate'               => $row['hire_date'],
                'seniorityStartDate'     => $row['seniority_start_date'],
                'durationYears'          => $years,
                'durationMonths'         => $months,
                'durationLabel'          => "{$years} Năm {$months} Tháng",
            ],
        ];
    }
}
