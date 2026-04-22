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
    private const APP_TIMEZONE = 'Asia/Ho_Chi_Minh';
    private const FACE_REPEAT_BLOCK_SECONDS = 900;
    private const FACE_CHECKOUT_MIN_SECONDS = 1800;

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
        $timezone = new \DateTimeZone(self::APP_TIMEZONE);
        $dt = $clientTime ? new \DateTime($clientTime) : new \DateTime('now', $timezone);
        $dt->setTimezone($timezone);
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
            throw new HttpException('Không nhận diện được khuôn mặt. Vui lòng thử lại.', 403, 'face_not_recognized');
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
            return $this->errorResponse(
                'Không tìm thấy ca làm việc hợp lệ cho hôm nay.',
                'NO_SHIFT',
                'warning',
                $empInfo,
                null,
                422
            );
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
        $recentSuccess = $this->findLatestSuccessfulFaceEvent($employeeId);
        $latestScan = $this->resolveLatestSuccessfulScan($existing, $recentSuccess);
        if ($latestScan !== null) {
            $recentTs = $this->parseLocalTimestamp((string) ($latestScan['event_time'] ?? ''));
            if ($recentTs !== false) {
                $diffSeconds = $nowTs - $recentTs;
                if ($diffSeconds >= 0 && $diffSeconds < self::FACE_REPEAT_BLOCK_SECONDS) {
                    $retryAfterSeconds = self::FACE_REPEAT_BLOCK_SECONDS - $diffSeconds;
                    $waitMessage = $this->buildRepeatScanMessage($retryAfterSeconds);
                    $lastEventType = (string) ($latestScan['event_type'] ?? 'unknown');
                    $this->logEvent(
                        $employeeId,
                        $today,
                        $shiftId,
                        'duplicate_check',
                        $now,
                        $payload,
                        'REPEAT_SCAN_BLOCKED',
                        'BLOCK',
                        $geofenceStatus,
                        $geofenceDistance,
                        $ipAddress,
                        $userAgent,
                        sprintf(
                            'Repeat face scan blocked after %d seconds from %s via %s',
                            $diffSeconds,
                            $lastEventType,
                            (string) ($latestScan['source'] ?? 'unknown')
                        )
                    );

                    return $this->errorResponse(
                        $waitMessage,
                        'REPEAT_SCAN_BLOCKED',
                        'info',
                        $empInfo,
                        $geofenceStatus,
                        409,
                        [
                            'repeat_scan_blocked' => true,
                            'retry_after_seconds' => $retryAfterSeconds,
                            'last_success_event' => [
                                'event_type' => $lastEventType,
                                'event_time' => (string) ($latestScan['event_time'] ?? ''),
                                'source' => (string) ($latestScan['source'] ?? 'attendance_events'),
                            ],
                            'shift_info' => [
                                'shift_code' => $shift['shift_code'] ?? null,
                                'shift_name' => $shift['shift_name'] ?? null,
                                'start_time' => $shift['start_time'] ?? null,
                                'end_time' => $shift['end_time'] ?? null,
                            ],
                        ]
                    );
                }
            }
        }

        $eventType = 'check_in';
        $resultCode = 'CHECKIN_SUCCESS';
        $lateMinutes = 0;
        $earlyMinutes = 0;

        if (!$existing) {
            // NOT_CHECKED_IN -> CHECKED_IN
            $eventType = 'check_in';
            // Calculate late directly from the assigned shift start time.
            $startTs = strtotime("$today $shiftStart");
            if ($nowTs > $startTs) {
                $lateMinutes = max(0, (int) floor(($nowTs - $startTs) / 60));
                if ($lateMinutes > 0) {
                    $resultCode = 'CHECKIN_LATE';
                }
            }
        } else {
            $lastTimeTs = 0;
            if (!empty($existing['check_in_time'])) {
                $parsedCheckIn = $this->parseLocalTimestamp((string) $existing['check_in_time']);
                if ($parsedCheckIn !== false) {
                    $lastTimeTs = $parsedCheckIn;
                }
            }
            if (!empty($existing['check_out_time'])) {
                $parsedCheckOut = $this->parseLocalTimestamp((string) $existing['check_out_time']);
                if ($parsedCheckOut !== false) {
                    $lastTimeTs = $parsedCheckOut;
                }
            }

            $diffSeconds = $nowTs - $lastTimeTs;

            if (!empty($existing['check_in_time']) && empty($existing['check_out_time'])) {
                // CHECKED_IN -> CHECKED_OUT
                // Not Ready for Checkout soft block: 30 minutes
                if ($diffSeconds < self::FACE_CHECKOUT_MIN_SECONDS) {
                    $this->logEvent($employeeId, $today, $shiftId, 'check_out_attempt', $now, $payload, 'NOT_READY_FOR_CHECKOUT', 'BLOCK', $geofenceStatus, $geofenceDistance, $ipAddress, $userAgent, "Scanned too early for checkout ($diffSeconds sec)");
                    return $this->errorResponse(
                        'Bạn đã check-in rồi. Vui lòng đợi đủ 30 phút trước khi check-out.',
                        'NOT_READY_FOR_CHECKOUT',
                        'info',
                        $empInfo,
                        $geofenceStatus,
                        409,
                        [
                            'retry_after_seconds' => self::FACE_CHECKOUT_MIN_SECONDS - max(0, $diffSeconds),
                            'repeat_scan_blocked' => false,
                        ]
                    );
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
                return $this->errorResponse(
                    'Bạn đã hoàn thành chấm công cho ca này rồi.',
                    'ATTENDANCE_COMPLETED',
                    'info',
                    $empInfo,
                    $geofenceStatus,
                    409,
                    [
                        'repeat_scan_blocked' => false,
                        'shift_info' => [
                            'shift_code' => $shift['shift_code'] ?? null,
                            'shift_name' => $shift['shift_name'] ?? null,
                            'start_time' => $shift['start_time'] ?? null,
                            'end_time' => $shift['end_time'] ?? null,
                        ],
                    ]
                );
            } else {
                $eventType = 'check_in';
            }
        }

        // 6. Persist Attendance
        try {
            $attendanceId = $this->persistAttendance($existing, $employeeId, $eventType, $today, $now, $payload, $shiftId, $lateMinutes, $earlyMinutes);
            
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
                'is_late' => $lateMinutes > 0,
                'early_checkout_minutes' => $earlyMinutes,
                'repeat_scan_blocked' => false,
                'retry_after_seconds' => 0,
                'geofence_status' => $geofenceStatus,
                'message_code' => $warningCode,
                'message' => $this->getMessageForCode($resultCode, $warningCode, $lateMinutes, $earlyMinutes),
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

    private function persistAttendance(?array $existing, int $employeeId, string $eventType, string $date, string $time, array $payload, int $shiftId, int $lateMinutes, int $earlyMinutes): int
    {
        $method = 'FACE_KIOSK';

        if (!$existing) {
            return $this->attendances->create([
                'employee_id' => $employeeId,
                'attendance_date' => $date,
                'shift_type_id' => $shiftId > 0 ? $shiftId : null,
                'check_in_time' => $time,
                'check_in_method' => $method,
                'check_in_latitude' => $payload['lat'],
                'check_in_longitude' => $payload['lng'],
                'late_minutes' => $lateMinutes,
                'status' => 'ĐÃ_DUYỆT',
                'work_type' => 'VĂN_PHÒNG',
                'notes' => 'Quick Face Web'
            ]);
        }

        $id = (int) $existing['attendance_id'];
        $update = [
            'shift_type_id' => $shiftId > 0 ? $shiftId : ($existing['shift_type_id'] ?? null),
            'status' => 'ĐÃ_DUYỆT',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($eventType === 'check_in') {
            if (empty($existing['check_in_time'])) {
                $update['check_in_time'] = $time;
                $update['check_in_method'] = $method;
                $update['check_in_latitude'] = $payload['lat'];
                $update['check_in_longitude'] = $payload['lng'];
                $update['late_minutes'] = $lateMinutes;
            } else {
                $update['check_in_time_2'] = $time;
            }
        } else {
            if (empty($existing['check_out_time'])) {
                $update['check_out_time'] = $time;
                $update['check_out_method'] = $method;
                $update['check_out_latitude'] = $payload['lat'];
                $update['check_out_longitude'] = $payload['lng'];
                $update['early_leave_minutes'] = $earlyMinutes;
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

    private function errorResponse(string $message, string $resultCode, string $severity, ?array $employee = null, ?string $geoStatus = null, int $status = 403, array $extra = []): array
    {
        $data = array_merge([
            'result_code' => $resultCode,
            'severity' => $severity,
            'event_effect' => 'none',
            'employee' => $employee,
            'geofence_status' => $geoStatus,
            'message_code' => $resultCode,
            'message' => $message,
        ], $extra);

        return [
            'status' => $status,
            'success' => false,
            'message' => $message,
            'data' => $data,
        ];
    }

    private function getMessageForCode(string $code, ?string $warning, int $lateMinutes = 0, int $earlyMinutes = 0): string
    {
        $msg = 'Chấm công thành công.';
        if ($code === 'CHECKIN_LATE') {
            $msg = sprintf('Chấm công thành công. Bạn đã đi trễ %d phút.', max(1, $lateMinutes));
        }
        if ($code === 'CHECKOUT_EARLY') {
            $msg = $earlyMinutes > 0
                ? sprintf('Check-out thành công. Bạn về sớm %d phút.', $earlyMinutes)
                : 'Check-out thành công (Về sớm).';
        }
        
        if ($warning === 'GPS_DRIFT_MINOR') $msg .= ' (Cảnh báo: Vị trí lệch nhẹ)';
        if ($warning === 'SIGNAL_WEAK') $msg .= ' (Cảnh báo: Tín hiệu GPS yếu)';

        return trim($msg);
    }

    private function buildRepeatScanMessage(int $retryAfterSeconds): string
    {
        return 'Bạn vừa chấm công rồi. Vui lòng chờ đủ 15 phút giữa hai lần quét.';
    }

    private function findLatestSuccessfulFaceEvent(int $employeeId): ?array
    {
        $db = \App\Core\Database::connection();
        $sql = "SELECT event_type, event_time, result_code, warning_code
                FROM attendance_events
                WHERE employee_id = :employee_id
                  AND source_type = 'face_quick_web'
                  AND result_code IN ('CHECKIN_SUCCESS', 'CHECKIN_LATE', 'CHECKOUT_SUCCESS', 'CHECKOUT_EARLY')
                ORDER BY event_time DESC
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'employee_id' => $employeeId,
        ]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }

        $row['source'] = 'attendance_events';
        return $row;
    }

    private function resolveLatestSuccessfulScan(?array $existingAttendance, ?array $eventLog): ?array
    {
        $candidates = [];

        if ($eventLog !== null && !empty($eventLog['event_time'])) {
            $candidates[] = [
                'event_type' => (string) ($eventLog['event_type'] ?? 'unknown'),
                'event_time' => (string) $eventLog['event_time'],
                'source' => (string) ($eventLog['source'] ?? 'attendance_events'),
            ];
        }

        if ($existingAttendance !== null) {
            if (!empty($existingAttendance['check_in_time'])) {
                $candidates[] = [
                    'event_type' => 'check_in',
                    'event_time' => (string) $existingAttendance['check_in_time'],
                    'source' => 'attendances.check_in_time',
                ];
            }
            if (!empty($existingAttendance['check_out_time'])) {
                $candidates[] = [
                    'event_type' => 'check_out',
                    'event_time' => (string) $existingAttendance['check_out_time'],
                    'source' => 'attendances.check_out_time',
                ];
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static function (array $left, array $right): int {
            $timezone = new \DateTimeZone(self::APP_TIMEZONE);
            $leftTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) ($left['event_time'] ?? ''), $timezone);
            $rightTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) ($right['event_time'] ?? ''), $timezone);
            $leftTs = $leftTime ? $leftTime->getTimestamp() : 0;
            $rightTs = $rightTime ? $rightTime->getTimestamp() : 0;
            return $rightTs <=> $leftTs;
        });

        return $candidates[0];
    }

    private function parseLocalTimestamp(string $value): int|false
    {
        $raw = trim($value);
        if ($raw === '') {
            return false;
        }

        $timezone = new \DateTimeZone(self::APP_TIMEZONE);
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw, $timezone);
        if ($parsed instanceof \DateTimeImmutable) {
            return $parsed->getTimestamp();
        }

        return strtotime($raw);
    }

    private function stripBase64Prefix(string $data): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
        }
        return $data;
    }
}
