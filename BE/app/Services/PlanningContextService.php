<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class PlanningContextService
{
    public function __construct(
        private readonly WorkScheduleResolverService $workScheduleResolver = new WorkScheduleResolverService(),
        private readonly HolidayResolverService $holidayResolver = new HolidayResolverService(),
    ) {
    }

    public function build(int $employeeId, string $workDate): array
    {
        return [
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'shift' => $this->workScheduleResolver->resolve($employeeId, $workDate),
            'holiday' => $this->holidayResolver->resolve($employeeId, $workDate),
            'leave' => $this->approvedLeave($employeeId, $workDate),
            'business_trip' => $this->approvedGenericRequest($employeeId, $workDate, ['CÔNG_TÁC'], ['CONG_TAC', 'BUSINESS_TRIP', 'CT']),
            'remote' => $this->approvedGenericRequest($employeeId, $workDate, ['KHÁC'], ['REMOTE', 'REMOTE_WORK', 'LAM_VIEC_TU_XA']),
            'overtime' => $this->approvedOvertime($employeeId, $workDate),
        ];
    }

    private function approvedLeave(int $employeeId, string $workDate): ?array
    {
        $sql = "SELECT lr.*, lt.leave_type_name, lt.leave_type_code, r.request_code, r.status AS request_status
                FROM leave_requests lr
                JOIN leave_types lt ON lt.leave_type_id = lr.leave_type_id
                JOIN requests r ON r.request_id = lr.request_id
                WHERE lr.employee_id = :employee_id
                  AND DATE(lr.from_date) <= :work_date
                  AND DATE(lr.to_date) >= :work_date
                  AND r.status = 'ĐÃ_DUYỆT'
                ORDER BY lr.leave_request_id DESC
                LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function approvedOvertime(int $employeeId, string $workDate): ?array
    {
        $sql = "SELECT ot.*, r.request_code, r.status AS request_status
                FROM overtime_requests ot
                JOIN requests r ON r.request_id = ot.request_id
                WHERE ot.employee_id = :employee_id
                  AND ot.overtime_date = :work_date
                  AND (
                    ot.status = 'ĐÃ_DUYỆT'
                    OR r.status = 'ĐÃ_DUYỆT'
                  )
                ORDER BY ot.overtime_id DESC
                LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
        ]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    private function approvedGenericRequest(int $employeeId, string $workDate, array $categories, array $codes): ?array
    {
        $categoryPlaceholders = [];
        $codePlaceholders = [];
        $params = [
            'employee_id' => $employeeId,
            'work_date' => $workDate,
        ];

        foreach (array_values($categories) as $index => $category) {
            $key = 'category_' . $index;
            $categoryPlaceholders[] = ':' . $key;
            $params[$key] = $category;
        }
        foreach (array_values($codes) as $index => $code) {
            $key = 'code_' . $index;
            $codePlaceholders[] = ':' . $key;
            $params[$key] = $code;
        }

        $sql = "SELECT r.*, rt.request_type_code, rt.request_type_name, rt.category
                FROM requests r
                JOIN request_types rt ON rt.request_type_id = r.request_type_id
                WHERE r.requester_id = :employee_id
                  AND r.status = 'ĐÃ_DUYỆT'
                  AND DATE(COALESCE(r.from_date, r.request_date)) <= :work_date
                  AND DATE(COALESCE(r.to_date, r.request_date)) >= :work_date
                  AND (
                    rt.category IN (" . implode(', ', $categoryPlaceholders) . ")
                    OR rt.request_type_code IN (" . implode(', ', $codePlaceholders) . ")
                  )
                ORDER BY r.request_id DESC
                LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, (string) $value);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
