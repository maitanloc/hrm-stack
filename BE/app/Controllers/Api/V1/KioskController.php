<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Core\HttpException;
use App\Models\Employee;
use App\Models\Attendance;
use App\Services\FaceIdentificationService;
use App\Services\AttendanceRiskService;
use App\Services\AttendanceResultService;
use App\Services\PlanningContextService;

class KioskController extends Controller
{
    private Employee $employees;
    private Attendance $attendances;
    private FaceIdentificationService $faceService;
    private AttendanceRiskService $riskService;
    private AttendanceResultService $resultService;
    private PlanningContextService $planningContext;

    public function __construct()
    {
        $this->employees = new Employee();
        $this->attendances = new Attendance();
        $this->faceService = new FaceIdentificationService();
        $this->riskService = new AttendanceRiskService();
        $this->resultService = new AttendanceResultService();
        $this->planningContext = new PlanningContextService();
    }

    public function faceAttendance(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'image' => ['required', 'string'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'accuracy_m' => ['required', 'numeric'],
            'client_time' => ['string'],
        ]);

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // 1. Time Handling
        $clientTime = $payload['client_time'] ?? null;
        $dt = $clientTime ? new \DateTime($clientTime) : new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $today = $dt->format('Y-m-d');
        $now = $dt->format('Y-m-d H:i:s');
        $nowTs = $dt->getTimestamp();

        // 2. Identify Face
        $rawImage = $this->stripBase64Prefix($payload['image']);
        try {
            $idResult = $this->faceService->identify($rawImage);
        } catch (\Exception $e) {
            $this->logEvent(null, $today, null, 'UNKNOWN', $now, $payload, 'FACE_SERVICE_ERROR', 'BLOCK', null, null, $ipAddress, $userAgent, $e->getMessage());
            throw new HttpException('Lỗi kết nối AI: ' . $e->getMessage(), 502, 'ai_integration_error');
        }

        if (!$idResult['success'] || empty($idResult['employee_code'])) {
            $this->logEvent(null, $today, null, 'UNKNOWN', $now, $payload, 'FACE_NOT_MATCHED', 'BLOCK', null, null, $ipAddress, $userAgent, $idResult['error'] ?? 'Face not recognized');
            throw new HttpException($idResult['error'] ?? 'Không thể nhận diện khuôn mặt.', 403, 'face_not_recognized');
        }

        $employee = $this->employees->findByCode($idResult['employee_code']);
        if (!$employee) {
            $this->logEvent(null, $today, null, 'UNKNOWN', $now, $payload, 'FACE_NOT_REGISTERED', 'BLOCK', null, null, $ipAddress, $userAgent, 'Employee code not found: ' . $idResult['employee_code']);
            throw new HttpException('Khuôn mặt chưa được đăng ký trong hệ thống.', 404, 'employee_not_found');
        }

        $employeeId = (int) $employee['employee_id'];
        $empInfo = [
            'employee_id' => $employeeId,
            'full_name' => $employee['full_name'],
            'employee_code' => $employee['employee_code'],
            'department_name' => $employee['department_name'],
            'avatar_url' => $employee['avatar_url']
        ];

        // 3. Check Shift
        $context = $this->planningContext->build($employeeId, $today, true);
        $shift = $context['shift'];
        
        if (!$shift) {
            $this->logEvent($employeeId, $today, null, 'UNKNOWN', $now, $payload, 'NO_SHIFT', 'BLOCK', null, null, $ipAddress, $userAgent, 'No valid shift for today');
            return $this->errorResponse('Bạn không có lịch làm việc trong ngày hôm nay.', 'NO_SHIFT', 'blocked', $empInfo);
        }
        $shiftId = (int) ($shift['shift_type_id'] ?? 0);
        $shiftStart = $shift['start_time'] ?? '00:00:00';
        $shiftEnd = $shift['end_time'] ?? '23:59:59';

        // 4. Geofence Rule
        $risk = $this->riskService->evaluateRisk(
            $employeeId,
            'WEB_QUICK_DEVICE',
            (float) $payload['lat'],
            (float) $payload['lng'],
            (float) $payload['accuracy_m'],
            $nowTs
        );

        $geofenceStatus = $risk['risk_level'] ?? 'RED';
        $geofenceDistance = $risk['distance_m'] ?? null;
        $warningCode = null;

        if ($risk['action'] === 'BLOCK') {
            $this->logEvent($employeeId, $today, $shiftId, 'UNKNOWN', $now, $payload, 'OUT_OF_GEOFENCE', 'BLOCK', $geofenceStatus, $geofenceDistance, $ipAddress, $userAgent, $risk['user_message']);
            return $this->errorResponse($risk['user_message'], 'OUT_OF_GEOFENCE', 'blocked', $empInfo, $geofenceStatus);
        }
        if ($risk['action'] === 'ALLOW_FLAG') {
            $warningCode = $risk['reason_code'];
        }

        // 5. State Machine & Cooldown Rules
        $existing = $this->attendances->findByEmployeeDate($employeeId, $today);
        $eventType = 'check_in';
        $resultCode = 'CHECKIN_SUCCESS';
        $lateMinutes = 0;
        $earlyMinutes = 0;

        if (!$existing) {
            // NOT_CHECKED_IN -> CHECKED_IN
            $eventType = 'check_in';
            // Calculate late
            $startTs = strtotime("$today $shiftStart");
            if ($nowTs > $startTs) {
                $rawLate = (int) floor(($nowTs - $startTs) / 60);
                if ($rawLate > 5) { // 5 mins grace period
                    $lateMinutes = $rawLate;
                    $resultCode = 'CHECKIN_LATE';
                }
            }
        } else {
            $lastTimeTs = 0;
            if (!empty($existing['check_in_time'])) $lastTimeTs = strtotime($existing['check_in_time']);
            if (!empty($existing['check_out_time'])) $lastTimeTs = strtotime($existing['check_out_time']);

            $diffSeconds = $nowTs - $lastTimeTs;

            // Duplicate Hard Block: 2 minutes
            if ($diffSeconds < 120) {
                $this->logEvent($employeeId, $today, $shiftId, 'duplicate_check', $now, $payload, 'DUPLICATE_RECENT', 'BLOCK', $geofenceStatus, $geofenceDistance, $ipAddress, $userAgent, 'Scanned within 2 minutes');
                return $this->errorResponse('Bạn vừa chấm công xong. Vui lòng không quét liên tục.', 'DUPLICATE_RECENT', 'blocked', $empInfo, $geofenceStatus);
            }

            if (!empty($existing['check_in_time']) && empty($existing['check_out_time'])) {
                // CHECKED_IN -> CHECKED_OUT
                // Not Ready for Checkout soft block: 30 minutes
                if ($diffSeconds < 1800) {
                    $this->logEvent($employeeId, $today, $shiftId, 'check_out_attempt', $now, $payload, 'NOT_READY_FOR_CHECKOUT', 'BLOCK', $geofenceStatus, $geofenceDistance, $ipAddress, $userAgent, "Scanned too early for checkout ($diffSeconds sec)");
                    return $this->errorResponse('Vui lòng đợi ít nhất 30 phút kể từ lúc Check-in để có thể Check-out.', 'NOT_READY_FOR_CHECKOUT', 'blocked', $empInfo, $geofenceStatus);
                }

                $eventType = 'check_out';
                $resultCode = 'CHECKOUT_SUCCESS';

                // Calculate early
                $endTs = strtotime("$today $shiftEnd");
                if (!empty($shift['is_night_shift']) || $shiftEnd < $shiftStart) {
                    $endTs += 86400;
                }
                if ($nowTs < $endTs) {
                    $rawEarly = (int) floor(($endTs - $nowTs) / 60);
                    if ($rawEarly > 5) {
                        $earlyMinutes = $rawEarly;
                        $resultCode = 'CHECKOUT_EARLY';
                    }
                }
            } else if (!empty($existing['check_in_time']) && !empty($existing['check_out_time'])) {
                // COMPLETED
                $this->logEvent($employeeId, $today, $shiftId, 'completed_check', $now, $payload, 'ATTENDANCE_COMPLETED', 'INFO', $geofenceStatus, $geofenceDistance, $ipAddress, $userAgent, 'Already checked in and out');
                return $this->errorResponse('Bạn đã hoàn thành chấm công ca này.', 'ATTENDANCE_COMPLETED', 'info', $empInfo, $geofenceStatus);
            } else {
                $eventType = 'check_in';
            }
        }

        // 6. Persist Attendance
        try {
            $attendanceId = $this->persistAttendance($existing, $employeeId, $eventType, $today, $now, $payload);
            
            // Log Event
            $this->logEvent($employeeId, $today, $shiftId, $eventType, $now, $payload, $resultCode, $warningCode ?? 'SUCCESS', $geofenceStatus, $geofenceDistance, $ipAddress, $userAgent, 'Quick Face Web Attendance');
            
            // Recalculate Final Result in background
            $this->resultService->evaluateAndPersist($employeeId, $today);

            return $this->ok([
                'result_code' => $resultCode,
                'severity' => $warningCode ? 'warning' : 'success',
                'event_effect' => $eventType,
                'employee' => $empInfo,
                'event' => [
                    'attendance_id' => $attendanceId,
                    'time' => date('H:i:s', $nowTs),
                    'date' => $today
                ],
                'shift_info' => [
                    'shift_code' => $shift['shift_code'] ?? null,
                    'shift_name' => $shift['shift_name'] ?? null,
                    'start_time' => $shift['start_time'] ?? null,
                    'end_time' => $shift['end_time'] ?? null,
                ],
                'late_minutes' => $lateMinutes,
                'early_checkout_minutes' => $earlyMinutes,
                'geofence_status' => $geofenceStatus,
                'message_code' => $warningCode,
                'message' => $this->getMessageForCode($resultCode, $warningCode),
            ], 'Thành công');

        } catch (\Exception $e) {
            error_log("KIOSK_ERROR: " . $e->getMessage());
            return $this->errorResponse('Lỗi hệ thống khi lưu chấm công.', 'SYSTEM_ERROR', 'blocked', $empInfo, $geofenceStatus);
        }
    }

    public function syncEmbeddings(Request $request): array
    {
        $db = \App\Core\Database::connection();
        $sql = "SELECT e.employee_code, efv.vector 
                FROM employee_face_vectors efv
                JOIN employees e ON e.employee_id = efv.employee_id";
        
        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'employee_code' => $row['employee_code'],
                'vector' => json_decode($row['vector'], true)
            ];
        }

        return $this->ok($payload, 'Embeddings synced');
    }

    private function persistAttendance(?array $existing, int $employeeId, string $eventType, string $date, string $time, array $payload): int
    {
        $method = 'FACE_KIOSK';

        if (!$existing) {
            return $this->attendances->create([
                'employee_id' => $employeeId,
                'attendance_date' => $date,
                'check_in_time' => $time,
                'check_in_method' => $method,
                'check_in_latitude' => $payload['lat'],
                'check_in_longitude' => $payload['lng'],
                'status' => 'ĐÃ_DUYỆT',
                'work_type' => 'VĂN_PHÒNG',
                'notes' => 'Quick Face Web'
            ]);
        }

        $id = (int) $existing['attendance_id'];
        $update = [
            'status' => 'ĐÃ_DUYỆT',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($eventType === 'check_in') {
            if (empty($existing['check_in_time'])) {
                $update['check_in_time'] = $time;
                $update['check_in_method'] = $method;
                $update['check_in_latitude'] = $payload['lat'];
                $update['check_in_longitude'] = $payload['lng'];
            } else {
                $update['check_in_time_2'] = $time;
            }
        } else {
            if (empty($existing['check_out_time'])) {
                $update['check_out_time'] = $time;
                $update['check_out_method'] = $method;
                $update['check_out_latitude'] = $payload['lat'];
                $update['check_out_longitude'] = $payload['lng'];
            } else {
                $update['check_out_time_2'] = $time;
            }
        }

        $this->attendances->updateById($id, $update);
        return $id;
    }

    private function logEvent($empId, $date, $shiftId, $eventType, $time, $payload, $resultCode, $warningCode, $geoStatus, $geoDist, $ip, $ua, $note): void
    {
        $db = \App\Core\Database::connection();
        $sql = "INSERT INTO attendance_events (employee_id, attendance_date, shift_id, event_type, source_type, event_time, gps_lat, gps_lng, gps_accuracy, geofence_status, geofence_distance, result_code, warning_code, ip_address, user_agent, note) VALUES (?, ?, ?, ?, 'face_quick_web', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $empId ?? 0,
            $date,
            $shiftId,
            $eventType,
            $time,
            $payload['lat'] ?? null,
            $payload['lng'] ?? null,
            $payload['accuracy_m'] ?? null,
            $geoStatus,
            $geoDist,
            $resultCode,
            $warningCode,
            $ip,
            $ua,
            $note
        ]);
    }

    private function errorResponse(string $message, string $resultCode, string $severity, array $employee = null, ?string $geoStatus = null): array
    {
        return [
            'status' => 403,
            'success' => false,
            'message' => $message,
            'data' => [
                'result_code' => $resultCode,
                'severity' => $severity,
                'event_effect' => 'none',
                'employee' => $employee,
                'geofence_status' => $geoStatus,
                'message_code' => $resultCode,
                'message' => $message
            ]
        ];
    }

    private function getMessageForCode(string $code, ?string $warning): string
    {
        $msg = 'Chấm công thành công.';
        if ($code === 'CHECKIN_LATE') $msg = 'Check-in thành công (Đi trễ).';
        if ($code === 'CHECKOUT_EARLY') $msg = 'Check-out thành công (Về sớm).';
        
        if ($warning === 'GPS_DRIFT_MINOR') $msg .= ' (Cảnh báo: Vị trí lệch nhẹ)';
        if ($warning === 'SIGNAL_WEAK') $msg .= ' (Cảnh báo: Tín hiệu GPS yếu)';

        return trim($msg);
    }

    private function stripBase64Prefix(string $data): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
        }
        return $data;
    }
}
