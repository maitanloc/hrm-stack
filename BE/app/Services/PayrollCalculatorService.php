<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * PayrollCalculatorService
 *
 * Tính lương theo nghiệp vụ chuẩn doanh nghiệp Việt Nam:
 *
 * Nguyên tắc ưu tiên trạng thái (1 ngày nhiều trạng thái):
 *   LEAVE_UNPAID > SICK > LEAVE_PAID > BUSINESS_TRIP > HALF_DAY > WORK/LATE/EARLY
 *
 * Công thức:
 *   luong_thuc_nhan =
 *     (luong_thang / cong_chuan × tong_cong)
 *     + tien_ot + tien_bhxh
 *     - tien_muon_som - tien_khong_phep
 */
class PayrollCalculatorService
{
    // Hệ số OT
    private const OT_RATE_NORMAL  = 1.5;
    private const OT_RATE_WEEKEND = 2.0;
    private const OT_RATE_HOLIDAY = 3.0;

    // Ngưỡng muộn/sớm tính phạt (phút)
    private const LATE_THRESHOLD_MINUTES = 15;

    // Số giờ làm việc chuẩn trong ngày
    private const STD_HOURS_PER_DAY = 8;

    // Tỷ lệ đóng bảo hiểm trích từ lương nhân viên
    private const RATE_BHXH = 0.08;   // 8%
    private const RATE_BHYT = 0.015;  // 1.5%
    private const RATE_BHTN = 0.01;   // 1%

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    // =========================================================
    //  PUBLIC ENTRY POINT
    // =========================================================

    /**
     * Tính lương cho 1 nhân viên trong 1 khoảng ngày.
     *
     * @param  int    $employeeId
     * @param  string $dateFrom      YYYY-MM-DD
     * @param  string $dateTo        YYYY-MM-DD
     * @param  float  $luongThang    Lương tháng cơ bản
     * @param  float  $luongBhxh     Lương đóng BHXH
     * @param  int    $congChuan     Công chuẩn (mặc định 26)
     * @return array
     */
    public function calculate(
        int    $employeeId,
        string $dateFrom,
        string $dateTo,
        float  $luongThang,
        float  $luongBhxh,
        int    $congChuan = 26
    ): array {
        // Lấy dữ liệu chấm công
        $attendances = $this->fetchAttendances($employeeId, $dateFrom, $dateTo);

        // Lấy nghỉ phép trong khoảng
        $leaveMap = $this->fetchLeaveMap($employeeId, $dateFrom, $dateTo);

        // Lương 1 giờ và 1 ngày
        $luongGio  = $luongThang / $congChuan / self::STD_HOURS_PER_DAY;
        $luongNgay = $luongThang / $congChuan;

        $tongCong          = 0.0;
        $tienOt            = 0.0;
        $tienBhxh          = 0.0;
        $tienTruMuonSom    = 0.0;
        $tienTruKhongPhep  = 0.0;
        $chiTiet           = [];

        foreach ($attendances as $row) {
            $date = (string) $row['attendance_date'];

            // --- 1. Xác định trạng thái ưu tiên ---
            $priority = $this->resolveStatus($row, $leaveMap[$date] ?? []);

            // --- 2. Tính công theo trạng thái ---
            $cong          = $this->calcCong($priority['status']);
            $isCheckinMissing = $this->isCheckinMissing($row);

            // Nếu thiếu check-in hoặc check-out → LEAVE_UNPAID
            if ($isCheckinMissing && !in_array($priority['status'], ['LEAVE_PAID', 'LEAVE_UNPAID', 'SICK', 'BUSINESS_TRIP'], true)) {
                $priority['status'] = 'LEAVE_UNPAID';
                $cong = 0.0;
            }

            // --- 3. Giờ làm thực tế ---
            $actualHours = $this->calcActualHours($row);

            // --- 4. Tính muộn/sớm ---
            $tienMuonSomNgay = 0.0;
            $thieu_gio       = 0.0;
            if (in_array($priority['status'], ['WORK', 'LATE', 'EARLY', 'HALF_DAY'], true)) {
                [$thieu_gio, $tienMuonSomNgay] = $this->calcLatePenalty($row, $luongGio);
                $tienTruMuonSom += $tienMuonSomNgay;
            }

            // --- 5. Tính OT ---
            $tienOtNgay = 0.0;
            $otHours    = (float) ($row['overtime_hours'] ?? 0);
            if ($otHours > 0) {
                $tienOtNgay = $this->calcOT($otHours, $row, $luongGio);
                $tienOt    += $tienOtNgay;
            }

            // --- 6. Tiền nghỉ ốm BHXH ---
            $tienBhxhNgay = 0.0;
            if ($priority['status'] === 'SICK') {
                $tienBhxhNgay = ($luongBhxh / 24) * 0.75 * $cong; // cong = 0 cho SICK nhưng ta cộng 1
                // Theo nghiệp vụ: 1 ngày nghỉ ốm vẫn tính đủ 1 ngày BHXH
                $tienBhxhNgay = ($luongBhxh / 24) * 0.75 * 1;
                $tienBhxh    += $tienBhxhNgay;
            }

            // --- 7. Nghỉ không phép ---
            $tienKhongPhepNgay = 0.0;
            if ($priority['status'] === 'LEAVE_UNPAID') {
                $tienKhongPhepNgay = $luongNgay;
                $tienTruKhongPhep += $tienKhongPhepNgay;
            }

            $tongCong += $cong;

            $chiTiet[] = [
                'date'               => $date,
                'status'             => $priority['status'],
                'status_source'      => $priority['source'],
                'cong'               => round($cong, 2),
                'check_in'           => $row['check_in_time']  ?? null,
                'check_out'          => $row['check_out_time'] ?? null,
                'actual_hours'       => round($actualHours, 2),
                'thieu_gio'          => round($thieu_gio, 2),
                'ot_hours'           => round($otHours, 2),
                'is_weekend'         => (bool) ($row['is_weekend']  ?? false),
                'is_holiday'         => (bool) ($row['is_holiday']  ?? false),
                'tien_muon_som'      => round($tienMuonSomNgay, 0),
                'tien_ot'            => round($tienOtNgay, 0),
                'tien_bhxh'          => round($tienBhxhNgay, 0),
                'tien_khong_phep'    => round($tienKhongPhepNgay, 0),
            ];
        }

        // Giới hạn tổng công tối đa = số ngày có mặt theo dữ liệu chấm công
        $tongCong = min($tongCong, (float) count($attendances));

        $luongCong       = $luongNgay * $tongCong;

        // --- 8. Tính các khoản khấu trừ bảo hiểm ---
        // Theo quy định: Nếu lương thực nhận thấp hơn lương định biên, ta tính bảo hiểm dựa trên thu nhập gộp (luongCong)
        // hoặc tính theo lương đóng BHXH đã thỏa thuận. 
        // Để khớp với UI, ta tính dựa trên luongCong nếu đi làm không đủ tháng.
        $baseForInsurance = $luongCong; 
        
        $tienBhxh_emp = round($baseForInsurance * self::RATE_BHXH, 0);
        $tienBhyt_emp = round($baseForInsurance * self::RATE_BHYT, 0);
        $tienBhtn_emp = round($baseForInsurance * self::RATE_BHTN, 0);
        $tongKhauTruBh = $tienBhxh_emp + $tienBhyt_emp + $tienBhtn_emp;

        // --- 9. Tính Thuế Thu nhập Cán nhân (PIT) ---
        // Tổng thu nhập chịu thuế (cơ bản)
        $tongThuNhap = $luongCong + $tienOt + $tienBhxh;
        
        // Đếm số người phụ thuộc đang có hiệu lực trong tháng này
        $periodMonthKey = date('Y-m', strtotime($dateFrom));
        $stmtDep = $this->db->prepare("
            SELECT COUNT(*) 
            FROM dependents 
            WHERE employee_id = ? 
              AND status = 1 
              AND (start_date IS NULL OR DATE_FORMAT(start_date, '%Y-%m') <= ?)
              AND (end_date IS NULL OR DATE_FORMAT(end_date, '%Y-%m') >= ?)
        ");
        $stmtDep->execute([$employeeId, $periodMonthKey, $periodMonthKey]);
        $soNguoiPhuThuoc = (int) $stmtDep->fetchColumn();

        // Các khoản giảm trừ
        $giamTruBanThan = 11000000;
        $giamTruPhuThuoc = $soNguoiPhuThuoc * 4400000;
        $tongGiamTru = $giamTruBanThan + $giamTruPhuThuoc + $tongKhauTruBh;

        // Thu nhập tính thuế
        $thuNhapTinhThue = max(0, $tongThuNhap - $tongGiamTru);

        // Tính thuế theo biểu thuế lũy tiến từng phần
        $tienThueTNCN = 0.0;
        if ($thuNhapTinhThue > 0) {
            if ($thuNhapTinhThue <= 5000000) {
                $tienThueTNCN = $thuNhapTinhThue * 0.05;
            } elseif ($thuNhapTinhThue <= 10000000) {
                $tienThueTNCN = $thuNhapTinhThue * 0.10 - 250000;
            } elseif ($thuNhapTinhThue <= 18000000) {
                $tienThueTNCN = $thuNhapTinhThue * 0.15 - 750000;
            } elseif ($thuNhapTinhThue <= 32000000) {
                $tienThueTNCN = $thuNhapTinhThue * 0.20 - 1650000;
            } elseif ($thuNhapTinhThue <= 52000000) {
                $tienThueTNCN = $thuNhapTinhThue * 0.25 - 3250000;
            } elseif ($thuNhapTinhThue <= 80000000) {
                $tienThueTNCN = $thuNhapTinhThue * 0.30 - 5850000;
            } else {
                $tienThueTNCN = $thuNhapTinhThue * 0.35 - 9850000;
            }
        }
        $tienThueTNCN = round(max(0, $tienThueTNCN), 0);

        // --- 10. Tính lương thực nhận cuối cùng ---
        $tongKhauTruTatCa = $tongKhauTruBh + $tienTruMuonSom + $tienTruKhongPhep + $tienThueTNCN;
        $luongThucNhan   = $tongThuNhap - $tongKhauTruTatCa;

        return [
            'tong_cong'            => round($tongCong, 2),
            'luong_cong'           => round($luongCong, 0),
            'tien_ot'              => round($tienOt, 0),
            'tien_bhxh'            => round($tienBhxh, 0),
            'tien_tru_muon_som'    => round($tienTruMuonSom, 0),
            'tien_tru_khong_phep'  => round($tienTruKhongPhep, 0),
            'social_insurance_employee'      => $tienBhxh_emp,
            'health_insurance_employee'      => $tienBhyt_emp,
            'unemployment_insurance_employee' => $tienBhtn_emp,
            'personal_income_tax'  => $tienThueTNCN,
            'total_deductions'     => round($tongKhauTruTatCa, 0),
            'luong_thuc_nhan'      => round(max(0, $luongThucNhan), 0),
            'chi_tiet'             => $chiTiet,
            // meta
            'employee_id'          => $employeeId,
            'date_from'            => $dateFrom,
            'date_to'              => $dateTo,
            'luong_thang'          => $luongThang,
            'luong_bhxh'           => $luongBhxh,
            'cong_chuan'           => $congChuan,
        ];
    }

    // =========================================================
    //  PRIVATE HELPERS
    // =========================================================

    /**
     * Lấy danh sách chấm công theo khoảng ngày.
     * Thêm cột is_weekend tính từ ngày trong tuần.
     */
    private function fetchAttendances(int $employeeId, string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT a.*,
                       DAYOFWEEK(a.attendance_date) AS _dow
                FROM attendances a
                WHERE a.employee_id = :employee_id
                  AND a.attendance_date BETWEEN :date_from AND :date_to
                ORDER BY a.attendance_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
        ]);
        $rows = $stmt->fetchAll() ?: [];

        // Đánh dấu cuối tuần (DAYOFWEEK: 1=CN, 7=T7)
        foreach ($rows as &$r) {
            $dow = (int) ($r['_dow'] ?? 0);
            $r['is_weekend'] = ($dow === 1 || $dow === 7) ? 1 : 0;
            unset($r['_dow']);
        }
        unset($r);

        return $rows;
    }

    /**
     * Lấy map: date => [leave rows] cho nhân viên trong khoảng ngày.
     * Chỉ lấy các đơn đã duyệt (ĐÃ_DUYỆT).
     */
    private function fetchLeaveMap(int $employeeId, string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT lr.leave_request_id,
                       lr.from_date,
                       lr.to_date,
                       lr.paid_days,
                       lr.unpaid_days,
                       lt.leave_type_name,
                       lt.is_paid,
                       r.status AS request_status
                FROM leave_requests lr
                JOIN leave_types lt ON lt.leave_type_id = lr.leave_type_id
                JOIN requests r ON r.request_id = lr.request_id
                WHERE lr.employee_id = :employee_id
                  AND r.status = 'ĐÃ_DUYỆT'
                  AND lr.from_date <= :date_to
                  AND lr.to_date >= :date_from";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
        ]);
        $leaves = $stmt->fetchAll() ?: [];

        $map = [];
        foreach ($leaves as $leave) {
            $start = strtotime((string) $leave['from_date']);
            $end   = strtotime((string) $leave['to_date']);
            if ($start === false || $end === false) {
                continue;
            }
            for ($ts = $start; $ts <= $end; $ts += 86400) {
                $d = date('Y-m-d', $ts);
                $map[$d][] = $leave;
            }
        }

        return $map;
    }

    /**
     * Xác định trạng thái ưu tiên cho 1 ngày.
     * Thứ tự: LEAVE_UNPAID > SICK > LEAVE_PAID > BUSINESS_TRIP > HALF_DAY > WORK/LATE/EARLY
     *
     * @param  array $row      Bản ghi attendance
     * @param  array $leaves   Danh sách nghỉ phép liên quan đến ngày đó
     * @return array           ['status' => string, 'source' => string]
     */
    private function resolveStatus(array $row, array $leaves): array
    {
        // --- Kiểm tra từ leave_requests ---
        $hasUnpaid    = false;
        $hasSick      = false;
        $hasPaid      = false;
        $hasHalfDay   = false;

        foreach ($leaves as $leave) {
            $isPaid = (bool) ($leave['is_paid'] ?? true);
            $name   = strtolower((string) ($leave['leave_type_name'] ?? ''));

            // Nghỉ ốm (sick)
            if (str_contains($name, 'ốm') || str_contains($name, 'sick')) {
                $hasSick = true;
                continue;
            }
            // Nghỉ không phép / không lương
            if (!$isPaid || str_contains($name, 'không phép') || str_contains($name, 'unpaid')) {
                $hasUnpaid = true;
                continue;
            }
            // Nửa ngày
            if (str_contains($name, 'nửa') || str_contains($name, 'half')) {
                $hasHalfDay = true;
                continue;
            }
            // Còn lại: nghỉ phép có lương
            if ($isPaid) {
                $hasPaid = true;
            }
        }

        // Ưu tiên
        if ($hasUnpaid) {
            return ['status' => 'LEAVE_UNPAID', 'source' => 'leave_request'];
        }
        if ($hasSick) {
            return ['status' => 'SICK', 'source' => 'leave_request'];
        }
        if ($hasPaid) {
            return ['status' => 'LEAVE_PAID', 'source' => 'leave_request'];
        }

        // --- Từ trạng thái chấm công ---
        $rawStatus  = strtoupper(trim((string) ($row['status'] ?? '')));
        $workType   = strtoupper(trim((string) ($row['work_type'] ?? '')));

        // Công tác
        if (str_contains($workType, 'BUSINESS') || str_contains($workType, 'CONG_TAC') || str_contains($workType, 'CÔNG_TÁC')) {
            return ['status' => 'BUSINESS_TRIP', 'source' => 'attendance'];
        }
        // Từ attendance status
        if (str_contains($rawStatus, 'LEAVE_UNPAID') || str_contains($rawStatus, 'NGHI_KHONG_PHEP')) {
            return ['status' => 'LEAVE_UNPAID', 'source' => 'attendance'];
        }
        if (str_contains($rawStatus, 'SICK') || str_contains($rawStatus, 'NGHI_OM')) {
            return ['status' => 'SICK', 'source' => 'attendance'];
        }
        if (str_contains($rawStatus, 'LEAVE_PAID') || str_contains($rawStatus, 'NGHI_PHEP')) {
            return ['status' => 'LEAVE_PAID', 'source' => 'attendance'];
        }

        if ($hasHalfDay) {
            return ['status' => 'HALF_DAY', 'source' => 'leave_request'];
        }

        // Kiểm tra đi muộn / về sớm từ cột
        $lateMin  = (int) ($row['late_minutes'] ?? 0);
        $earlyMin = (int) ($row['early_leave_minutes'] ?? 0);

        if ($lateMin >= self::LATE_THRESHOLD_MINUTES || $earlyMin >= self::LATE_THRESHOLD_MINUTES) {
            return ['status' => 'LATE', 'source' => 'attendance'];
        }

        // Mặc định: đi làm bình thường
        return ['status' => 'WORK', 'source' => 'attendance'];
    }

    /**
     * Định mức công theo trạng thái.
     */
    private function calcCong(string $status): float
    {
        return match ($status) {
            'WORK', 'LATE', 'EARLY', 'BUSINESS_TRIP', 'LEAVE_PAID' => 1.0,
            'HALF_DAY'    => 0.5,
            'LEAVE_UNPAID', 'SICK' => 0.0,
            default => 1.0,
        };
    }

    /**
     * Kiểm tra thiếu check-in hoặc check-out (tính LEAVE_UNPAID).
     */
    private function isCheckinMissing(array $row): bool
    {
        $checkIn  = $row['check_in_time']  ?? null;
        $checkOut = $row['check_out_time'] ?? null;

        // Nếu cả 2 trường đều null/rỗng → chưa chấm công (có thể là nghỉ có đơn)
        if (empty($checkIn) && empty($checkOut)) {
            return false; // sẽ được xử lý bởi leave_request hoặc status
        }
        // Thiếu 1 trong 2
        if ((empty($checkIn) && !empty($checkOut)) || (!empty($checkIn) && empty($checkOut))) {
            return true;
        }
        return false;
    }

    /**
     * Tính giờ làm thực tế (hỗ trợ ca đêm - qua ngày).
     */
    private function calcActualHours(array $row): float
    {
        // Ưu tiên trường actual_working_hours nếu có
        if (isset($row['actual_working_hours']) && (float)$row['actual_working_hours'] > 0) {
            return (float) $row['actual_working_hours'];
        }

        $checkIn  = $row['check_in_time']  ?? null;
        $checkOut = $row['check_out_time'] ?? null;

        if (empty($checkIn) || empty($checkOut)) {
            return 0.0;
        }

        $tsIn  = strtotime((string) $checkIn);
        $tsOut = strtotime((string) $checkOut);

        if ($tsIn === false || $tsOut === false) {
            return 0.0;
        }

        // Ca đêm: check_out < check_in → qua ngày hôm sau
        if ($tsOut < $tsIn) {
            $tsOut += 86400;
        }

        $diffSeconds = $tsOut - $tsIn;
        return max(0.0, $diffSeconds / 3600);
    }

    /**
     * Tính tiền phạt đi muộn / về sớm.
     * Chỉ xét nếu ≥ 15 phút.
     * OT không bù trừ vào khoản này.
     *
     * @return array [thieu_gio, tien_phat]
     */
    private function calcLatePenalty(array $row, float $luongGio): array
    {
        $lateMin  = (int) ($row['late_minutes'] ?? 0);
        $earlyMin = (int) ($row['early_leave_minutes'] ?? 0);

        $tongThieuPhut = 0;

        if ($lateMin >= self::LATE_THRESHOLD_MINUTES) {
            $tongThieuPhut += $lateMin;
        }
        if ($earlyMin >= self::LATE_THRESHOLD_MINUTES) {
            $tongThieuPhut += $earlyMin;
        }

        if ($tongThieuPhut === 0) {
            return [0.0, 0.0];
        }

        $thieuGio = $tongThieuPhut / 60;
        $tienPhat = $luongGio * $thieuGio;

        return [$thieuGio, $tienPhat];
    }

    /**
     * Tính tiền OT.
     * Ngày thường: 150%, Cuối tuần: 200%, Lễ: 300%
     */
    private function calcOT(float $otHours, array $row, float $luongGio): float
    {
        $isHoliday = (bool) ($row['is_holiday'] ?? false);
        $isWeekend = (bool) ($row['is_weekend'] ?? false);

        $rate = self::OT_RATE_NORMAL;
        if ($isHoliday) {
            $rate = self::OT_RATE_HOLIDAY;
        } elseif ($isWeekend) {
            $rate = self::OT_RATE_WEEKEND;
        }

        return $luongGio * $rate * $otHours;
    }

    // =========================================================
    //  BATCH: Calculate for all employees in a salary period
    // =========================================================

    /**
     * Tính lương hàng loạt cho tất cả nhân viên trong 1 period.
     * Lấy lương cơ bản từ hợp đồng hiện tại.
     * Trả về mảng kết quả theo employee_id.
     *
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  int    $congChuan
     * @return array  [employee_id => result]
     */
    public function calculateBatch(string $dateFrom, string $dateTo, int $congChuan = 26): array
    {
        // Lấy tất cả nhân viên cùng lương từ hợp đồng đang hiệu lực
        $sql = "SELECT e.employee_id,
                       e.full_name,
                       e.employee_code,
                       c.basic_salary   AS luong_thang,
                       c.gross_salary   AS luong_bhxh,
                       c.contract_id
                FROM employees e
                LEFT JOIN (
                    -- Lấy hợp đồng mới nhất theo ID để đảm bảo duy nhất
                    SELECT c1.*
                    FROM contracts c1
                    WHERE c1.contract_id IN (
                        SELECT MAX(contract_id)
                        FROM contracts
                        GROUP BY employee_id
                    )
                ) c ON e.employee_id = c.employee_id
                ORDER BY e.employee_id ASC";

        $stmt = $this->db->query($sql);
        $employees = $stmt ? ($stmt->fetchAll() ?: []) : [];

        $results = [];
        foreach ($employees as $emp) {
            $empId     = (int) $emp['employee_id'];
            $luongThang = (float) ($emp['luong_thang'] ?? 0);
            $luongBhxh  = (float) ($emp['luong_bhxh'] ?? $luongThang);

            if ($luongThang <= 0) {
                continue;
            }

            $result = $this->calculate($empId, $dateFrom, $dateTo, $luongThang, $luongBhxh, $congChuan);
            $result['full_name']    = $emp['full_name'];
            $result['employee_code'] = $emp['employee_code'];
            $result['contract_id']  = $emp['contract_id'];
            $results[$empId] = $result;
        }

        return $results;
    }
}
