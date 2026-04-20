<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\TextEncoding;

class PlanningContextService
{
    public function __construct(
        private readonly WorkScheduleResolverService $workScheduleResolver = new WorkScheduleResolverService(),
        private readonly HolidayResolverService $holidayResolver = new HolidayResolverService(),
    ) {
    }

    public function build(int $employeeId, string $workDate, bool $onlyPublished = false): array
    {
        return [
            'employee_id' => $employeeId,
            'work_date' => $workDate,
            'shift' => $this->workScheduleResolver->resolve($employeeId, $workDate, $onlyPublished),
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
                  AND DATE(lr.from_date) <= :work_date_from
                  AND DATE(lr.to_date) >= :work_date_to
                ORDER BY lr.leave_request_id DESC
                LIMIT 10";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date_from' => $workDate,
            'work_date_to' => $workDate,
        ]);
        foreach ($stmt->fetchAll() ?: [] as $row) {
            if ($this->isApprovedStatus($row['request_status'] ?? null)) {
                return $row;
            }
        }

        return null;
    }

    private function approvedOvertime(int $employeeId, string $workDate): ?array
    {
        $sql = "SELECT ot.*, r.request_code, r.status AS request_status
                FROM overtime_requests ot
                JOIN requests r ON r.request_id = ot.request_id
                WHERE ot.employee_id = :employee_id
                  AND ot.overtime_date = :work_date
                ORDER BY ot.overtime_id DESC
                LIMIT 10";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'work_date' => $workDate,
        ]);
        foreach ($stmt->fetchAll() ?: [] as $row) {
            if ($this->isApprovedStatus($row['status'] ?? null) || $this->isApprovedStatus($row['request_status'] ?? null)) {
                return $row;
            }
        }

        return null;
    }

    private function approvedGenericRequest(int $employeeId, string $workDate, array $categories, array $codes): ?array
    {
        $categoryPlaceholders = [];
        $codePlaceholders = [];
        $params = [
            'employee_id' => $employeeId,
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
                  AND DATE(COALESCE(r.from_date, r.request_date)) <= :work_date_from
                  AND DATE(COALESCE(r.to_date, r.request_date)) >= :work_date_to
                  AND (
                    rt.category IN (" . implode(', ', $categoryPlaceholders) . ")
                    OR rt.request_type_code IN (" . implode(', ', $codePlaceholders) . ")
                  )
                ORDER BY r.request_id DESC
                LIMIT 10";
        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, (string) $value);
        }
        $stmt->bindValue(':work_date_from', $workDate);
        $stmt->bindValue(':work_date_to', $workDate);
        $stmt->execute();
        foreach ($stmt->fetchAll() ?: [] as $row) {
            if ($this->isApprovedStatus($row['status'] ?? null)) {
                return $row;
            }
        }

        return null;
    }

    private function isApprovedStatus(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        $fixed = TextEncoding::fixMojibake($value);
        $token = $this->normalizeStatusToken(is_string($fixed) ? $fixed : $value);
        return in_array($token, ['DA_DUYET', 'DUY_T', 'APPROVED'], true)
            || (str_contains($token, 'DUY') && !str_starts_with($token, 'CH') && !str_contains($token, 'ANG'));
    }

    private function normalizeStatusToken(string $value): string
    {
        $upper = mb_strtoupper(trim($value), 'UTF-8');
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
