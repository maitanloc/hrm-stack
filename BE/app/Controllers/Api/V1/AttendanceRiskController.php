<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Hierarchy;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Attendance;
use App\Models\AttendanceExceptionRequest;
use App\Models\AttendancePrecheck;
use App\Models\RiskAlert;
use App\Models\TrustedDevice;

class AttendanceRiskController extends Controller
{
    private const OFFICE_LAT = 10.776889;
    private const OFFICE_LNG = 106.700806;
    private const GREEN_RADIUS_M = 120.0;
    private const YELLOW_RADIUS_M = 250.0;
    private const PRECHECK_TTL_SECONDS = 180;
    private const IMPOSSIBLE_TRAVEL_M = 20000.0;
    private const SUSPICIOUS_WINDOW_SECONDS = 1800;
    private const PRECHECK_CLOCK_SKEW_SECONDS = 300;

    private Attendance $attendances;
    private AttendancePrecheck $prechecks;
    private TrustedDevice $trustedDevices;
    private RiskAlert $riskAlerts;
    private AttendanceExceptionRequest $exceptionRequests;

    public function __construct()
    {
        $this->attendances = new Attendance();
        $this->prechecks = new AttendancePrecheck();
        $this->trustedDevices = new TrustedDevice();
        $this->riskAlerts = new RiskAlert();
        $this->exceptionRequests = new AttendanceExceptionRequest();
    }

    public function precheck(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'employee_id' => ['required', 'integer'],
            'device_id' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:ANDROID,IOS'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'accuracy_m' => ['required', 'numeric', 'min:0'],
            'attendance_type' => ['required', 'string', 'in:CHECKIN,CHECKOUT'],
            'client_time' => ['required', 'date'],
            'app_version' => ['string'],
            'wifi_ssid' => ['string'],
            'qr_area_code' => ['string'],
        ]);

        $employeeId = (int) $payload['employee_id'];
        $deviceId = (string) $payload['device_id'];
        $platform = (string) $payload['platform'];
        $lat = (float) $payload['lat'];
        $lng = (float) $payload['lng'];
        $accuracyM = (float) $payload['accuracy_m'];
        $attendanceType = (string) $payload['attendance_type'];
        $clientTs = strtotime((string) $payload['client_time']) ?: time();

        $this->assertEmployeeScope($request, $employeeId);

        $trusted = $this->trustedDevices->findActiveByEmployeeAndDevice($employeeId, $deviceId) !== null;
        $risk = $this->evaluateRisk($employeeId, $deviceId, $trusted, $lat, $lng, $accuracyM, $clientTs);

        $issuedAt = time();
        $precheckToken = $this->buildPrecheckToken(
            $employeeId,
            $attendanceType,
            (string) $risk['risk_level'],
            $issuedAt
        );
        $precheckTokenHash = hash('sha256', $precheckToken);
        $expiresAt = $risk['action'] === 'BLOCK'
            ? null
            : date('Y-m-d H:i:s', $issuedAt + self::PRECHECK_TTL_SECONDS);

        $this->prechecks->create([
            'precheck_token' => $precheckTokenHash,
            'employee_id' => $employeeId,
            'device_id' => $deviceId,
            'attendance_type' => $attendanceType,
            'risk_level' => $risk['risk_level'],
            'action' => $risk['action'],
            'reason_code' => $risk['reason_code'],
            'next_action' => $risk['next_action'],
            'lat' => $lat,
            'lng' => $lng,
            'accuracy_m' => $accuracyM,
            'distance_m' => $risk['distance_m'],
            'requires_manager_review' => $risk['requires_manager_review'] ? 1 : 0,
            'expires_at' => $expiresAt,
            'is_used' => 0,
        ]);

        if ($risk['risk_level'] !== 'GREEN') {
            $this->riskAlerts->create([
                'employee_id' => $employeeId,
                'attendance_id' => null,
                'attendance_type' => $attendanceType,
                'risk_level' => $risk['risk_level'],
                'reason_code' => $risk['reason_code'],
                'distance_m' => $risk['distance_m'],
                'happened_at' => date('Y-m-d H:i:s', $clientTs),
                'status' => 'OPEN',
                'details_json' => json_encode([
                    'device_id' => $deviceId,
                    'platform' => $platform,
                    'accuracy_m' => $accuracyM,
                    'app_version' => $payload['app_version'] ?? null,
                    'wifi_ssid' => $payload['wifi_ssid'] ?? null,
                    'qr_area_code' => $payload['qr_area_code'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        return $this->ok([
            'risk_level' => $risk['risk_level'],
            'action' => $risk['action'],
            'reason_code' => $risk['reason_code'],
            'next_action' => $risk['next_action'],
            'user_message' => $risk['user_message'],
            'precheck_token' => $risk['action'] === 'BLOCK' ? null : $precheckToken,
            'expires_at' => $expiresAt !== null ? date(DATE_ATOM, strtotime($expiresAt)) : null,
            'distance_m' => $risk['distance_m'],
            'requires_manager_review' => $risk['requires_manager_review'],
        ], $risk['action'] === 'BLOCK' ? 'Precheck blocked' : 'Precheck success', []);
    }

    public function checkin(Request $request): array
    {
        return $this->commitAttendance($request, 'CHECKIN');
    }

    public function checkout(Request $request): array
    {
        return $this->commitAttendance($request, 'CHECKOUT');
    }

    public function reverifyDevice(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'employee_id' => ['required', 'integer'],
            'otp_code' => ['required', 'string'],
            'new_device_id' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:ANDROID,IOS'],
            'client_time' => ['required', 'date'],
            'app_version' => ['string'],
        ]);

        $employeeId = (int) $payload['employee_id'];
        $newDeviceId = (string) $payload['new_device_id'];
        $platform = (string) $payload['platform'];
        $this->assertEmployeeScope($request, $employeeId);

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $this->trustedDevices->activateForEmployee($employeeId, $newDeviceId, $platform);
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }

        return $this->ok([
            'employee_id' => $employeeId,
            'device_bound' => true,
            'trusted_device_id' => $newDeviceId,
            'status' => 'VERIFIED',
            'reverified_at' => date(DATE_ATOM),
            'user_message' => 'Thiết bị đã được xác minh. Bạn có thể tiếp tục chấm công.',
        ], 'Device reverified', []);
    }

    public function requestException(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'employee_id' => ['required', 'integer'],
            'attendance_event_id' => ['integer'],
            'precheck_token' => ['string'],
            'reason' => ['required', 'string'],
            'lat' => ['numeric'],
            'lng' => ['numeric'],
            'requested_at' => ['required', 'date'],
        ]);

        $employeeId = (int) $payload['employee_id'];
        $this->assertEmployeeScope($request, $employeeId);

        $attendanceId = isset($payload['attendance_event_id']) ? (int) $payload['attendance_event_id'] : null;
        if ($attendanceId !== null && $attendanceId > 0) {
            $attendance = $this->attendances->findDetail($attendanceId);
            if ($attendance === null || (int) ($attendance['employee_id'] ?? 0) !== $employeeId) {
                throw new HttpException('Attendance event not found', 404, 'not_found');
            }
        } else {
            $attendanceId = null;
        }

        $requestedAt = date('Y-m-d H:i:s', strtotime((string) $payload['requested_at']) ?: time());
        $exceptionId = $this->exceptionRequests->create([
            'employee_id' => $employeeId,
            'attendance_id' => $attendanceId,
            'precheck_token' => isset($payload['precheck_token'])
                ? hash('sha256', (string) $payload['precheck_token'])
                : null,
            'reason' => (string) $payload['reason'],
            'lat' => $payload['lat'] ?? null,
            'lng' => $payload['lng'] ?? null,
            'status' => 'PENDING',
            'requested_at' => $requestedAt,
            'approved_by' => null,
            'approved_at' => null,
            'valid_until' => null,
            'review_note' => null,
        ]);

        return $this->created([
            'exception_id' => $exceptionId,
            'status' => 'PENDING',
            'employee_id' => $employeeId,
            'manager_id' => $this->resolveManagerIdForEmployee($employeeId),
            'sla_minutes' => 30,
            'user_message' => 'Yêu cầu ngoại lệ đã được gửi cho quản lý.',
        ], 'Exception request submitted');
    }

    public function approveExceptionOnce(Request $request, array $params): array
    {
        $authUser = $this->authUser($request);
        if (!$this->canApproveRisk($authUser)) {
            throw new HttpException('Only manager/admin can approve exception', 403, 'forbidden');
        }

        $id = (int) ($params['id'] ?? 0);
        $exception = $id > 0 ? $this->exceptionRequests->findDetail($id) : null;
        if ($exception === null) {
            throw new HttpException('Exception request not found', 404, 'not_found');
        }

        $targetEmployeeId = (int) ($exception['employee_id'] ?? 0);
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, $targetEmployeeId, true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }

        if ((string) ($exception['status'] ?? '') !== 'PENDING') {
            throw new HttpException('Exception request already processed', 409, 'conflict');
        }

        $payload = Validator::validate($request->all(), [
            'decision' => ['required', 'string', 'in:APPROVE,REJECT'],
            'note' => ['string'],
            'valid_minutes' => ['integer', 'min:1', 'max:240'],
        ]);

        $decision = (string) $payload['decision'];
        $validMinutes = (int) ($payload['valid_minutes'] ?? 30);
        $status = $decision === 'APPROVE' ? 'APPROVED_ONCE' : 'REJECTED';
        $validUntil = $decision === 'APPROVE'
            ? date('Y-m-d H:i:s', time() + ($validMinutes * 60))
            : null;

        $approverId = (int) ($authUser['employee_id'] ?? 0);

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $this->exceptionRequests->updateById($id, [
                'status' => $status,
                'approved_by' => $approverId,
                'approved_at' => date('Y-m-d H:i:s'),
                'valid_until' => $validUntil,
                'review_note' => isset($payload['note']) ? (string) $payload['note'] : null,
            ]);

            if ($status === 'APPROVED_ONCE') {
                $this->riskAlerts->resolveOpenAlertsForEmployee($targetEmployeeId, $approverId);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }

        return $this->ok([
            'exception_id' => $id,
            'status' => $status,
            'valid_until' => $validUntil !== null ? date(DATE_ATOM, strtotime($validUntil)) : null,
            'approved_by' => $approverId,
            'approved_at' => date(DATE_ATOM),
            'user_message' => $decision === 'APPROVE'
                ? 'Ngoại lệ đã được duyệt một lần.'
                : 'Yêu cầu ngoại lệ đã bị từ chối.',
        ], 'Exception decision recorded', []);
    }

    public function riskAlerts(Request $request): array
    {
        $authUser = $this->authUser($request);
        if (!$this->canApproveRisk($authUser)) {
            throw new HttpException('Only manager/admin can view risk alerts', 403, 'forbidden');
        }

        $riskLevel = strtoupper((string) ($request->query('risk_level') ?? ''));
        if (!in_array($riskLevel, ['GREEN', 'YELLOW', 'RED'], true)) {
            $riskLevel = '';
        }

        $status = strtoupper((string) ($request->query('status') ?? ''));
        if (!in_array($status, ['OPEN', 'RESOLVED'], true)) {
            $status = '';
        }

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $page = max(1, (int) ($request->query('page') ?? 1));
        $perPage = (int) ($request->query('per_page') ?? 20);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $employeeScopeIds = null;
        if (!Auth::isPrivileged($authUser)) {
            $employeeScopeIds = array_values(array_unique(array_merge(
                [(int) ($authUser['employee_id'] ?? 0)],
                $authUser['hierarchy_employee_ids'] ?? []
            )));
        }

        $result = $this->riskAlerts->paginateList(
            $offset,
            $perPage,
            $employeeScopeIds,
            is_string($dateFrom) ? $dateFrom : null,
            is_string($dateTo) ? $dateTo : null,
            $riskLevel !== '' ? $riskLevel : null,
            $status !== '' ? $status : null
        );

        $items = array_map(static function (array $item): array {
            if (!empty($item['happened_at'])) {
                $item['happened_at'] = date(DATE_ATOM, strtotime((string) $item['happened_at']));
            }
            if (array_key_exists('distance_m', $item) && $item['distance_m'] !== null) {
                $item['distance_m'] = (float) $item['distance_m'];
            }
            $item['employee_id'] = (int) ($item['employee_id'] ?? 0);
            $item['id'] = (int) ($item['id'] ?? 0);
            return $item;
        }, $result['items']);

        $total = (int) $result['total'];
        $lastPage = $total === 0 ? 1 : (int) ceil($total / $perPage);

        return $this->ok($items, 'Risk alerts', [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ]);
    }

    private function commitAttendance(Request $request, string $expectedType): array
    {
        $payload = Validator::validate($request->all(), [
            'employee_id' => ['required', 'integer'],
            'precheck_token' => ['required', 'string'],
            'client_time' => ['required', 'date'],
        ]);

        $employeeId = (int) $payload['employee_id'];
        $precheckToken = (string) $payload['precheck_token'];
        $precheckTokenHash = hash('sha256', $precheckToken);
        $eventTs = strtotime((string) $payload['client_time']) ?: time();
        $this->assertEmployeeScope($request, $employeeId);

        $precheck = $this->prechecks->findByTokenHash($precheckTokenHash);
        if ($precheck === null) {
            throw new HttpException('Invalid or expired precheck token', 409, 'conflict');
        }

        $tokenMeta = $this->validateSignedPrecheckToken($precheckToken, $precheck);
        if ($tokenMeta === null) {
            throw new HttpException('Invalid precheck signature', 409, 'conflict');
        }
        $issuedAt = (int) ($tokenMeta['issued_at'] ?? 0);

        if ((int) ($precheck['employee_id'] ?? 0) !== $employeeId
            || (string) ($precheck['attendance_type'] ?? '') !== $expectedType
        ) {
            throw new HttpException('Precheck token does not match this request', 409, 'conflict');
        }

        if ((int) ($precheck['is_used'] ?? 0) === 1) {
            throw new HttpException('Precheck token already used', 409, 'conflict');
        }

        $expiresAt = $precheck['expires_at'] ?? null;
        if (is_string($expiresAt) && $expiresAt !== '' && strtotime($expiresAt) < time()) {
            throw new HttpException('Precheck token expired', 409, 'conflict');
        }
        if (
            $eventTs < ($issuedAt - self::PRECHECK_CLOCK_SKEW_SECONDS)
            || $eventTs > ($issuedAt + self::PRECHECK_TTL_SECONDS + self::PRECHECK_CLOCK_SKEW_SECONDS)
        ) {
            throw new HttpException('Attendance timestamp is outside precheck window', 409, 'conflict');
        }

        $riskLevel = (string) ($precheck['risk_level'] ?? 'RED');
        $action = (string) ($precheck['action'] ?? 'BLOCK');
        if ($action === 'BLOCK' || $riskLevel === 'RED') {
            throw new HttpException('This precheck cannot be used for attendance', 409, 'conflict');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $attendanceId = $this->persistAttendanceFromPrecheck($employeeId, $expectedType, $eventTs, $precheck);

            $marked = $this->prechecks->markUsed((int) ($precheck['precheck_id'] ?? 0));
            if (!$marked) {
                throw new HttpException('Precheck token already used', 409, 'conflict');
            }

            if ($riskLevel === 'YELLOW') {
                $this->riskAlerts->create([
                    'employee_id' => $employeeId,
                    'attendance_id' => $attendanceId,
                    'attendance_type' => $expectedType,
                    'risk_level' => 'YELLOW',
                    'reason_code' => (string) ($precheck['reason_code'] ?? 'GPS_DRIFT_MINOR'),
                    'distance_m' => $precheck['distance_m'] ?? null,
                    'happened_at' => date('Y-m-d H:i:s', $eventTs),
                    'status' => 'OPEN',
                    'details_json' => json_encode([
                        'source' => 'attendance_commit',
                        'precheck_id' => (int) ($precheck['precheck_id'] ?? 0),
                        'precheck_token_hash' => $precheckTokenHash,
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }

        $flagged = $riskLevel === 'YELLOW';
        $status = $expectedType === 'CHECKIN'
            ? ($flagged ? 'CHECKIN_OK_FLAGGED' : 'CHECKIN_OK')
            : ($flagged ? 'CHECKOUT_OK_FLAGGED' : 'CHECKOUT_OK');

        return $this->created([
            'attendance_event_id' => $attendanceId,
            'employee_id' => $employeeId,
            'attendance_type' => $expectedType,
            'risk_level' => $riskLevel,
            'flagged' => $flagged,
            'status' => $status,
            'event_time' => date(DATE_ATOM, $eventTs),
            'manager_review_status' => $flagged ? 'PENDING' : 'NONE',
            'user_message' => 'Chấm công thành công lúc ' . date('H:i', $eventTs) . '.',
        ], 'Attendance recorded');
    }

    /**
     * @param array<string, mixed> $precheck
     */
    private function persistAttendanceFromPrecheck(int $employeeId, string $attendanceType, int $eventTs, array $precheck): int
    {
        $attendanceDate = date('Y-m-d', $eventTs);
        $eventDateTime = date('Y-m-d H:i:s', $eventTs);
        $lat = isset($precheck['lat']) ? (float) $precheck['lat'] : null;
        $lng = isset($precheck['lng']) ? (float) $precheck['lng'] : null;

        $existing = $this->attendances->findByEmployeeDate($employeeId, $attendanceDate);
        if ($existing === null) {
            $payload = [
                'employee_id' => $employeeId,
                'attendance_date' => $attendanceDate,
            ];

            if ($attendanceType === 'CHECKIN') {
                $payload['check_in_time'] = $eventDateTime;
                $payload['check_in_method'] = 'MOBILE';
                $payload['check_in_latitude'] = $lat;
                $payload['check_in_longitude'] = $lng;
            } else {
                $payload['check_out_time'] = $eventDateTime;
                $payload['check_out_method'] = 'MOBILE';
                $payload['check_out_latitude'] = $lat;
                $payload['check_out_longitude'] = $lng;
            }

            return $this->attendances->create($payload);
        }

        $attendanceId = (int) $existing['attendance_id'];
        $updatePayload = [];

        if ($attendanceType === 'CHECKIN') {
            if ($this->isEmptyValue($existing['check_in_time'] ?? null)) {
                $updatePayload['check_in_time'] = $eventDateTime;
                $updatePayload['check_in_method'] = 'MOBILE';
                $updatePayload['check_in_latitude'] = $lat;
                $updatePayload['check_in_longitude'] = $lng;
            } elseif ($this->isEmptyValue($existing['check_in_time_2'] ?? null)) {
                $updatePayload['check_in_time_2'] = $eventDateTime;
            } else {
                throw new HttpException('All check-in slots already used for today', 409, 'conflict');
            }
        } else {
            if ($this->isEmptyValue($existing['check_out_time'] ?? null)) {
                $updatePayload['check_out_time'] = $eventDateTime;
                $updatePayload['check_out_method'] = 'MOBILE';
                $updatePayload['check_out_latitude'] = $lat;
                $updatePayload['check_out_longitude'] = $lng;
            } elseif ($this->isEmptyValue($existing['check_out_time_2'] ?? null)) {
                $updatePayload['check_out_time_2'] = $eventDateTime;
            } else {
                throw new HttpException('All check-out slots already used for today', 409, 'conflict');
            }
        }

        $this->attendances->updateById($attendanceId, $updatePayload);
        return $attendanceId;
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateRisk(
        int $employeeId,
        string $deviceId,
        bool $trustedDevice,
        float $lat,
        float $lng,
        float $accuracyM,
        int $clientTs
    ): array {
        if (!$trustedDevice) {
            return [
                'risk_level' => 'RED',
                'action' => 'BLOCK',
                'reason_code' => 'DEVICE_NOT_TRUSTED',
                'next_action' => 'REVERIFY_DEVICE',
                'user_message' => 'Đây là điện thoại mới. Vui lòng xác minh để tiếp tục.',
                'distance_m' => null,
                'requires_manager_review' => true,
            ];
        }

        $latest = $this->prechecks->latestByEmployee($employeeId);
        if ($latest !== null) {
            $latestTs = strtotime((string) ($latest['created_at'] ?? '')) ?: null;
            if ($latestTs !== null) {
                $elapsed = abs($clientTs - $latestTs);
                if ($elapsed <= self::SUSPICIOUS_WINDOW_SECONDS) {
                    if ((string) ($latest['device_id'] ?? '') !== $deviceId) {
                        return [
                            'risk_level' => 'RED',
                            'action' => 'BLOCK',
                            'reason_code' => 'MULTI_DEVICE_SHORT_TIME',
                            'next_action' => 'REVERIFY_DEVICE',
                            'user_message' => 'Phát hiện đổi thiết bị trong thời gian ngắn. Vui lòng xác minh lại.',
                            'distance_m' => null,
                            'requires_manager_review' => true,
                        ];
                    }

                    $latestLat = isset($latest['lat']) ? (float) $latest['lat'] : null;
                    $latestLng = isset($latest['lng']) ? (float) $latest['lng'] : null;
                    if ($latestLat !== null && $latestLng !== null) {
                        $moveDistance = $this->haversineMeters($lat, $lng, $latestLat, $latestLng);
                        if ($moveDistance >= self::IMPOSSIBLE_TRAVEL_M) {
                            return [
                                'risk_level' => 'RED',
                                'action' => 'BLOCK',
                                'reason_code' => 'IMPOSSIBLE_TRAVEL',
                                'next_action' => 'REQUEST_EXCEPTION',
                                'user_message' => 'Phát hiện di chuyển bất thường. Vui lòng liên hệ quản lý để xác minh.',
                                'distance_m' => round($moveDistance, 1),
                                'requires_manager_review' => true,
                            ];
                        }
                    }
                }
            }
        }

        $distanceM = $this->haversineMeters($lat, $lng, self::OFFICE_LAT, self::OFFICE_LNG);

        if ($distanceM <= self::GREEN_RADIUS_M && $accuracyM <= 120) {
            return [
                'risk_level' => 'GREEN',
                'action' => 'ALLOW',
                'reason_code' => 'OK_IN_ZONE',
                'next_action' => 'NONE',
                'user_message' => 'Bạn đang ở đúng khu vực làm việc. Bấm Đi làm để chấm công.',
                'distance_m' => round($distanceM, 1),
                'requires_manager_review' => false,
            ];
        }

        if ($distanceM <= self::YELLOW_RADIUS_M || ($accuracyM > 120 && $distanceM <= 1000.0)) {
            return [
                'risk_level' => 'YELLOW',
                'action' => 'ALLOW_FLAG',
                'reason_code' => $accuracyM > 120 ? 'SIGNAL_WEAK' : 'GPS_DRIFT_MINOR',
                'next_action' => 'NONE',
                'user_message' => 'Vị trí đang lệch nhẹ, hệ thống vẫn ghi nhận chấm công.',
                'distance_m' => round($distanceM, 1),
                'requires_manager_review' => true,
            ];
        }

        return [
            'risk_level' => 'RED',
            'action' => 'BLOCK',
            'reason_code' => 'OUT_OF_GEOFENCE',
            'next_action' => 'RETRY',
            'user_message' => 'Bạn đang ngoài khu vực làm việc. Vui lòng đến đúng vị trí rồi bấm Thử lại.',
            'distance_m' => round($distanceM, 1),
            'requires_manager_review' => true,
        ];
    }

    private function buildPrecheckToken(int $employeeId, string $attendanceType, string $riskLevel, int $issuedAt): string
    {
        $nonce = bin2hex(random_bytes(8));
        $payload = sprintf('%d|%s|%s|%d|%s', $employeeId, $attendanceType, $riskLevel, $issuedAt, $nonce);
        $signature = substr(hash_hmac('sha256', $payload, $this->tokenSigningSecret()), 0, 24);

        return sprintf('pk1.%s.%d.%s', $nonce, $issuedAt, $signature);
    }

    /**
     * @param array<string, mixed> $precheck
     * @return array{issued_at:int,nonce:string}|null
     */
    private function validateSignedPrecheckToken(string $token, array $precheck): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 4 || $parts[0] !== 'pk1') {
            return null;
        }

        $nonce = $parts[1];
        $issuedRaw = $parts[2];
        $signature = $parts[3];

        if (!ctype_xdigit($nonce) || strlen($nonce) !== 16) {
            return null;
        }
        if (!ctype_digit($issuedRaw)) {
            return null;
        }
        if (!ctype_xdigit($signature) || strlen($signature) !== 24) {
            return null;
        }

        $issuedAt = (int) $issuedRaw;
        if ($issuedAt <= 0) {
            return null;
        }

        $payload = sprintf(
            '%d|%s|%s|%d|%s',
            (int) ($precheck['employee_id'] ?? 0),
            (string) ($precheck['attendance_type'] ?? ''),
            (string) ($precheck['risk_level'] ?? ''),
            $issuedAt,
            $nonce
        );
        $expectedSignature = substr(hash_hmac('sha256', $payload, $this->tokenSigningSecret()), 0, 24);
        if (!hash_equals(strtolower($expectedSignature), strtolower($signature))) {
            return null;
        }

        $createdAtTs = strtotime((string) ($precheck['created_at'] ?? ''));
        if ($createdAtTs !== false && abs($createdAtTs - $issuedAt) > self::PRECHECK_CLOCK_SKEW_SECONDS) {
            return null;
        }

        return [
            'issued_at' => $issuedAt,
            'nonce' => strtolower($nonce),
        ];
    }

    private function tokenSigningSecret(): string
    {
        $secret = (string) env('ATTENDANCE_PRECHECK_SECRET', '');
        if ($secret !== '') {
            return $secret;
        }

        $config = require base_path('config/app.php');
        $fallback = (string) (($config['jwt']['secret'] ?? '') ?: 'change-this-secret');
        return $fallback;
    }

    private function canApproveRisk(array $authUser): bool
    {
        if (Auth::isPrivileged($authUser)) {
            return true;
        }

        $managedDepartments = $authUser['managed_department_ids'] ?? [];
        if (is_array($managedDepartments) && count($managedDepartments) > 0) {
            return true;
        }

        $roleCodes = array_map(
            static fn(array|string $role): string => strtoupper(is_array($role) ? (string) ($role['role_code'] ?? '') : (string) $role),
            $authUser['roles'] ?? []
        );

        return in_array('MANAGER', $roleCodes, true)
            || in_array('TRUONGPHONG', $roleCodes, true)
            || in_array('TRUONG_PHONG', $roleCodes, true);
    }

    private function assertEmployeeScope(Request $request, int $employeeId): void
    {
        $authUser = $this->authUser($request);
        if (!Auth::isPrivileged($authUser) && !Hierarchy::canAccessEmployee($authUser, $employeeId, true)) {
            throw new HttpException('Hierarchy scope denied', 403, 'forbidden');
        }
    }

    private function authUser(Request $request): array
    {
        $authUser = $request->attribute('auth_user');
        if (!is_array($authUser)) {
            throw new HttpException('Unauthorized', 401, 'unauthorized');
        }
        return $authUser;
    }

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function isEmptyValue(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    private function resolveManagerIdForEmployee(int $employeeId): ?int
    {
        $sql = "SELECT d.manager_id
                FROM employment_histories eh
                JOIN departments d ON d.department_id = eh.department_id
                WHERE eh.employee_id = :employee_id
                  AND eh.is_current = 1
                LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['employee_id' => $employeeId]);
        $row = $stmt->fetch();
        if ($row === false || !isset($row['manager_id'])) {
            return null;
        }

        $managerId = (int) $row['manager_id'];
        return $managerId > 0 ? $managerId : null;
    }
}
