<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Exception;

class AttendanceImportService
{
    /**
     * Import attendance logs from a machine/file.
     * Expects rows: [['employee_code', 'check_time'], ...]
     */
    public function importLogs(array $rows, int $importedBy): array
    {
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $employeeCode = $row[0] ?? null;
                $checkTimeStr = $row[1] ?? null;

                if (!$employeeCode || !$checkTimeStr) {
                    $errorCount++;
                    $errors[] = "Row {$index}: Missing employee_code or check_time";
                    continue;
                }

                $employeeId = $this->findEmployeeIdStr($employeeCode);
                if (!$employeeId) {
                    $errorCount++;
                    $errors[] = "Row {$index}: Employee '{$employeeCode}' not found";
                    continue;
                }

                $checkTime = date('Y-m-d H:i:s', strtotime((string)$checkTimeStr));
                $workDate = date('Y-m-d', strtotime($checkTime));

                // Basic pairing logic:
                // Find attendance record for this date
                $stmt = $db->prepare("SELECT attendance_id, check_in_time, check_out_time, location 
                                      FROM attendances 
                                      WHERE employee_id = :eid AND DATE(check_in_time) = :wdate 
                                      ORDER BY attendance_id DESC LIMIT 1");
                $stmt->execute(['eid' => $employeeId, 'wdate' => $workDate]);
                $attendance = $stmt->fetch();

                if (!$attendance) {
                    // Create new check-in
                    $ins = $db->prepare("INSERT INTO attendances (employee_id, check_in_time, status, location, device_info, created_at) 
                                         VALUES (:eid, :chk, 'PENDING', 'IMPORTED', :inf, NOW())");
                    $ins->execute([
                        'eid' => $employeeId,
                        'chk' => $checkTime,
                        'inf' => "Import by {$importedBy}",
                    ]);
                    $successCount++;
                } else {
                    $inTime = $attendance['check_in_time'];
                    $outTime = $attendance['check_out_time'];
                    
                    if (strtotime($checkTime) <= strtotime((string)$inTime)) {
                        // ignore or update earlier checkin
                        continue; 
                    }

                    if (!$outTime) {
                        // Update checkout
                        $upd = $db->prepare("UPDATE attendances SET check_out_time = :chk, sync_status = 'SYNCED', location = CONCAT(location, ',IMPORTED') WHERE attendance_id = :aid");
                        $upd->execute(['chk' => $checkTime, 'aid' => $attendance['attendance_id']]);
                        $successCount++;
                    } else if (strtotime($checkTime) > strtotime((string)$outTime)) {
                         // Update later check out
                        $upd = $db->prepare("UPDATE attendances SET check_out_time = :chk WHERE attendance_id = :aid");
                        $upd->execute(['chk' => $checkTime, 'aid' => $attendance['attendance_id']]);
                        $successCount++;
                    }
                }
            }

            $db->commit();
            
            return [
                'success' => true,
                'imported_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function findEmployeeIdStr(string $code): ?int
    {
        $stmt = Database::connection()->prepare("SELECT employee_id FROM employees WHERE employee_code = :c LIMIT 1");
        $stmt->execute(['c' => $code]);
        $row = $stmt->fetch();
        return $row ? (int)$row['employee_id'] : null;
    }
}
