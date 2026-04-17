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
use App\Models\SystemConfig;
use App\Models\TrustedDevice;

class AttendanceRiskController extends Controller
{
    private const DEFAULT_OFFICE_LAT = 10.776889;
    private const DEFAULT_OFFICE_LNG = 106.700806;
    private const GREEN_RADIUS_M = 120.0;
    private const YELLOW_RADIUS_M = 250.0;
    private const PRECHECK_TTL_SECONDS = 180;
    private const IMPOSSIBLE_TRAVEL_M = 20000.0;
    private const SUSPICIOUS_WINDOW_SECONDS = 1800;
    private const PRECHECK_CLOCK_SKEW_SECONDS = 300;
    private const CONFIG_GEO_LOCK_ENABLED_KEY = 'attendance_company_geo_lock_enabled';
    private const CONFIG_ANCHORS_JSON_KEY = 'attendance_company_anchor_points_json';
    private const CONFIG_GREEN_RADIUS_KEY = 'attendance_green_radius_m';
    private const CONFIG_YELLOW_RADIUS_KEY = 'attendance_yellow_radius_m';
    private const DEFAULT_COMPANY_ANCHORS = [
        [
            'label' => 'Mốc công ty chuẩn 1',
            'address' => '10°56\'38.7"N 106°52\'54.0"E',
            'lat' => 10.9440833,
            'lng' => 106.8816667,
        ],
        [
            'label' => 'Mốc công ty chuẩn 2',
            'address' => '193 Đỗ Văn Thi, Phường Trấn Biên, Đồng Nai 700000, Việt Nam',
            'lat' => 10.9350102,
            'lng' => 106.8293991,
        ],
    ];

    private Attendance $attendances;
    private AttendancePrecheck $prechecks;
    private TrustedDevice $trustedDevices;
    private RiskAlert $riskAlerts;
    private AttendanceExceptionRequest $exceptionRequests;
    private SystemConfig $systemConfigs;
    /** @var array<string, mixed>|null */
    private ?array $geoPolicyCache = null;
    private ?bool $attendanceSecondSlotsSupported = null;
    /** @var array<string, string> */
    private array $attendanceMethodCache = [];

    public function __construct()
    {
        $this->attendances = new Attendance();
        $this->prechecks = new AttendancePrecheck();
        $this->trustedDevices = new TrustedDevice();
        $this->riskAlerts = new RiskAlert();
        $this->exceptionRequests = new AttendanceExceptionRequest();
        $this->systemConfigs = new SystemConfig();
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

        $trustedDevice = $this->trustedDevices->findActiveByEmployeeAndDevice($employeeId, $deviceId);
        $risk = null;
        if ($trustedDevice === null) {
            $latestActiveDevice = $this->trustedDevices->findLatestActiveByEmployee($employeeId);
            if ($latestActiveDevice === null) {
                $trustedDevice = $this->autoRegisterDevice(
                    $employeeId,
                    $deviceId,
                    $platform,
                    $lat,
                    $lng,
                    $accuracyM
                );
            } else {
                $trustedDevice = $latestActiveDevice;
                $risk = $this->buildUnregisteredDeviceRisk($lat, $lng, $trustedDevice);
            }
        }

        if ($risk === null) {
            $risk = $this->evaluateRisk($employeeId, $deviceId, $lat, $lng, $accuracyM, $clientTs, $trustedDevice);
        }

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
                    'working_area' => $trustedDevice['working_area_label'] ?? null,
                    'company_anchor_label' => $risk['company_anchor_label'] ?? null,
                    'company_anchor_distance_m' => $risk['company_anchor_distance_m'] ?? null,
                    'app_version' => $payload['app_version'] ?? null,
                    'wifi_ssid' => $payload['wifi_ssid'] ?? null,
                    'qr_area_code' => $payload['qr_area_code'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        $geoPolicy = $this->loadGeoPolicy();
        $allowClockIn = $risk['action'] !== 'BLOCK';

        return $this->ok([
            'status' => $this->mapPrecheckStatus($risk),
            'allow_clock_in' => $allowClockIn,
            'risk_level' => $risk['risk_level'],
            'action' => $risk['action'],
            'reason_code' => $risk['reason_code'],
            'next_action' => $risk['next_action'],
            'user_message' => $risk['user_message'],
            'precheck_token' => $allowClockIn ? $precheckToken : null,
            'expires_at' => $expiresAt !== null ? date(DATE_ATOM, strtotime($expiresAt)) : null,
            'distance_m' => $risk['distance_m'],
            'current_location' => [
                'lat' => $lat,
                'lng' => $lng,
                'accuracy_m' => $accuracyM,
            ],
            'zone' => [
                'green_radius_m' => (float) ($geoPolicy['green_radius_m'] ?? self::GREEN_RADIUS_M),
                'yellow_radius_m' => (float) ($geoPolicy['yellow_radius_m'] ?? self::YELLOW_RADIUS_M),
            ],
            'company_anchor_label' => $risk['company_anchor_label'] ?? null,
            'company_anchor_distance_m' => $risk['company_anchor_distance_m'] ?? null,
            'company_anchor_count' => $risk['company_anchor_count'] ?? 0,
            'company_geo_lock_enabled' => $risk['company_geo_lock_enabled'] ?? false,
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

    public function bootstrapDevice(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'employee_id' => ['required', 'integer'],
            'device_id' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:ANDROID,IOS'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'accuracy_m' => ['numeric', 'min:0'],
            'working_area' => ['string'],
        ]);

        $employeeId = (int) $payload['employee_id'];
        $deviceId = (string) $payload['device_id'];
        $platform = (string) $payload['platform'];
        $lat = (float) $payload['lat'];
        $lng = (float) $payload['lng'];
        $accuracyM = isset($payload['accuracy_m']) ? (float) $payload['accuracy_m'] : null;
        $workingArea = isset($payload['working_area']) ? trim((string) $payload['working_area']) : null;
        if ($workingArea === '') {
            $workingArea = null;
        }

        $this->assertEmployeeScope($request, $employeeId);

        $originLat = $lat;
        $originLng = $lng;
        $resolvedWorkingArea = $workingArea;
        $baselineDevice = $this->trustedDevices->findLatestActiveByEmployee($employeeId);
        $geoPolicy = $this->loadGeoPolicy();

        $nearestAnchor = null;
        if ((bool) ($geoPolicy['geo_lock_enabled'] ?? true)) {
            $nearestAnchor = $this->resolveNearestCompanyAnchor($lat, $lng, $geoPolicy['anchors'] ?? []);
        }

        if ($nearestAnchor !== null) {
            $originLat = (float) $nearestAnchor['lat'];
            $originLng = (float) $nearestAnchor['lng'];
            if ($resolvedWorkingArea === null && isset($nearestAnchor['label'])) {
                $resolvedWorkingArea = (string) $nearestAnchor['label'];
            }
        } elseif ($baselineDevice !== null) {
            $baselineLatRaw = $baselineDevice['origin_lat'] ?? null;
            $baselineLngRaw = $baselineDevice['origin_lng'] ?? null;
            if ($baselineLatRaw !== null && $baselineLngRaw !== null) {
                $originLat = (float) $baselineLatRaw;
                $originLng = (float) $baselineLngRaw;
            }
            if ($resolvedWorkingArea === null && isset($baselineDevice['working_area_label'])) {
                $resolvedWorkingArea = (string) $baselineDevice['working_area_label'];
            }
        }

        $this->trustedDevices->activateForEmployee(
            $employeeId,
            $deviceId,
            $platform,
            $originLat,
            $originLng,
            $accuracyM,
            $resolvedWorkingArea
        );

        return $this->ok([
            'employee_id' => $employeeId,
            'device_bound' => true,
            'trusted_device_id' => $deviceId,
            'working_area' => $resolvedWorkingArea,
            'origin_location' => [
                'lat' => $originLat,
                'lng' => $originLng,
                'accuracy_m' => $accuracyM,
            ],
            'user_message' => 'Đã ghi nhận vị trí làm việc ban đầu cho thiết bị này.',
        ], 'Device bootstrap completed', []);
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
        $serverNowTs = time();
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
        $windowStart = $issuedAt - self::PRECHECK_CLOCK_SKEW_SECONDS;
        $windowEnd = $issuedAt + self::PRECHECK_TTL_SECONDS + self::PRECHECK_CLOCK_SKEW_SECONDS;
        if ($eventTs < $windowStart || $eventTs > $windowEnd) {
            if ($serverNowTs >= $windowStart && $serverNowTs <= $windowEnd) {
                $eventTs = $serverNowTs;
            } else {
                throw new HttpException('Attendance timestamp is outside precheck window', 409, 'conflict');
            }
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
        $checkInMethod = $this->resolveSupportedAttendanceMethod('check_in_method');
        $checkOutMethod = $this->resolveSupportedAttendanceMethod('check_out_method');

        $existing = $this->attendances->findByEmployeeDate($employeeId, $attendanceDate);
        if ($existing === null) {
            $payload = [
                'employee_id' => $employeeId,
                'attendance_date' => $attendanceDate,
            ];

            if ($attendanceType === 'CHECKIN') {
                $payload['check_in_time'] = $eventDateTime;
                $payload['check_in_method'] = $checkInMethod;
                $payload['check_in_latitude'] = $lat;
                $payload['check_in_longitude'] = $lng;
            } else {
                $payload['check_out_time'] = $eventDateTime;
                $payload['check_out_method'] = $checkOutMethod;
                $payload['check_out_latitude'] = $lat;
                $payload['check_out_longitude'] = $lng;
            }

            return $this->attendances->create($payload);
        }

        $attendanceId = (int) $existing['attendance_id'];
        $updatePayload = [];
        $supportsSecondSlots = $this->supportsAttendanceSecondSlots();

        if ($attendanceType === 'CHECKIN') {
            if ($this->isEmptyValue($existing['check_in_time'] ?? null)) {
                $updatePayload['check_in_time'] = $eventDateTime;
                $updatePayload['check_in_method'] = $checkInMethod;
                $updatePayload['check_in_latitude'] = $lat;
                $updatePayload['check_in_longitude'] = $lng;
            } elseif ($supportsSecondSlots && $this->isEmptyValue($existing['check_in_time_2'] ?? null)) {
                $updatePayload['check_in_time_2'] = $eventDateTime;
            } else {
                throw new HttpException('All check-in slots already used for today', 409, 'conflict');
            }
        } else {
            if ($this->isEmptyValue($existing['check_out_time'] ?? null)) {
                $updatePayload['check_out_time'] = $eventDateTime;
                $updatePayload['check_out_method'] = $checkOutMethod;
                $updatePayload['check_out_latitude'] = $lat;
                $updatePayload['check_out_longitude'] = $lng;
            } elseif ($supportsSecondSlots && $this->isEmptyValue($existing['check_out_time_2'] ?? null)) {
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
        float $lat,
        float $lng,
        float $accuracyM,
        int $clientTs,
        ?array $trustedDeviceRow = null
    ): array {
        $geoPolicy = $this->loadGeoPolicy();
        $distanceRef = $this->resolveDistanceReference($lat, $lng, $trustedDeviceRow, $geoPolicy);
        $distanceM = (float) ($distanceRef['distance_m'] ?? 0.0);
        $greenRadius = (float) ($geoPolicy['green_radius_m'] ?? self::GREEN_RADIUS_M);
        $yellowRadius = (float) ($geoPolicy['yellow_radius_m'] ?? self::YELLOW_RADIUS_M);
        $riskMeta = [
            'company_anchor_label' => $distanceRef['label'] ?? null,
            'company_anchor_distance_m' => round($distanceM, 1),
            'company_anchor_count' => (int) ($distanceRef['anchor_count'] ?? 0),
            'company_geo_lock_enabled' => (bool) ($distanceRef['geo_lock_enabled'] ?? false),
        ];
        $recentDeviceSwitch = false;

        $latest = $this->prechecks->latestByEmployee($employeeId);
        if ($latest !== null) {
            $latestTs = strtotime((string) ($latest['created_at'] ?? '')) ?: null;
            if ($latestTs !== null) {
                $elapsed = abs($clientTs - $latestTs);
                if ($elapsed <= self::SUSPICIOUS_WINDOW_SECONDS) {
                    if ((string) ($latest['device_id'] ?? '') !== $deviceId) {
                        $recentDeviceSwitch = true;
                    }

                    $latestLat = isset($latest['lat']) ? (float) $latest['lat'] : null;
                    $latestLng = isset($latest['lng']) ? (float) $latest['lng'] : null;
                    if ($latestLat !== null && $latestLng !== null) {
                        $moveDistance = $this->haversineMeters($lat, $lng, $latestLat, $latestLng);
                        if ($moveDistance >= self::IMPOSSIBLE_TRAVEL_M) {
                            return array_merge([
                                'risk_level' => 'RED',
                                'action' => 'BLOCK',
                                'reason_code' => 'IMPOSSIBLE_TRAVEL',
                                'next_action' => 'REQUEST_EXCEPTION',
                                'user_message' => 'Phát hiện di chuyển bất thường. Vui lòng liên hệ quản lý để xác minh.',
                                'distance_m' => round($moveDistance, 1),
                                'requires_manager_review' => true,
                            ], $riskMeta);
                        }
                    }
                }
            }
        }

        $baselineLabel = isset($distanceRef['label']) ? (string) $distanceRef['label'] : null;

        if ($distanceM <= $greenRadius && $accuracyM <= 120) {
            if ($recentDeviceSwitch) {
                return array_merge([
                    'risk_level' => 'YELLOW',
                    'action' => 'ALLOW_FLAG',
                    'reason_code' => 'MULTI_DEVICE_SHORT_TIME',
                    'next_action' => 'NONE',
                    'user_message' => 'Hệ thống ghi nhận đổi thiết bị trong thời gian ngắn và vẫn cho phép chấm công.',
                    'distance_m' => round($distanceM, 1),
                    'requires_manager_review' => true,
                ], $riskMeta);
            }

            return array_merge([
                'risk_level' => 'GREEN',
                'action' => 'ALLOW',
                'reason_code' => 'OK_IN_ZONE',
                'next_action' => 'NONE',
                'user_message' => $baselineLabel !== null
                    ? sprintf('Bạn đang ở đúng khu vực làm việc %s. Bấm Đi làm để chấm công.', $baselineLabel)
                    : 'Bạn đang ở đúng khu vực làm việc. Bấm Đi làm để chấm công.',
                'distance_m' => round($distanceM, 1),
                'requires_manager_review' => false,
            ], $riskMeta);
        }

        if ($distanceM <= $yellowRadius || ($accuracyM > 120 && $distanceM <= 1000.0)) {
            $reasonCode = $accuracyM > 120 ? 'SIGNAL_WEAK' : 'GPS_DRIFT_MINOR';
            if ($recentDeviceSwitch) {
                $reasonCode = 'MULTI_DEVICE_SHORT_TIME';
            }

            return array_merge([
                'risk_level' => 'YELLOW',
                'action' => 'ALLOW_FLAG',
                'reason_code' => $reasonCode,
                'next_action' => 'NONE',
                'user_message' => 'Vị trí đang lệch nhẹ, hệ thống vẫn ghi nhận chấm công.',
                'distance_m' => round($distanceM, 1),
                'requires_manager_review' => true,
            ], $riskMeta);
        }

        return array_merge([
            'risk_level' => 'RED',
            'action' => 'BLOCK',
            'reason_code' => 'OUT_OF_GEOFENCE',
            'next_action' => 'RETRY',
            'user_message' => 'Bạn đang ngoài khu vực làm việc. Vui lòng đến đúng vị trí rồi bấm Thử lại.',
            'distance_m' => round($distanceM, 1),
            'requires_manager_review' => true,
        ], $riskMeta);
    }

    /**
     * @param array<string, mixed>|null $trustedDeviceRow
     * @return array<string, mixed>
     */
    private function buildUnregisteredDeviceRisk(float $lat, float $lng, ?array $trustedDeviceRow = null): array
    {
        $geoPolicy = $this->loadGeoPolicy();
        $distanceRef = $this->resolveDistanceReference($lat, $lng, $trustedDeviceRow, $geoPolicy);
        $distanceM = (float) ($distanceRef['distance_m'] ?? 0.0);

        return [
            'risk_level' => 'RED',
            'action' => 'BLOCK',
            'reason_code' => 'DEVICE_NOT_TRUSTED',
            'next_action' => 'REVERIFY_DEVICE',
            'user_message' => 'Đây là điện thoại mới. Vui lòng xác minh để tiếp tục.',
            'distance_m' => round($distanceM, 1),
            'requires_manager_review' => true,
            'company_anchor_label' => $distanceRef['label'] ?? null,
            'company_anchor_distance_m' => round($distanceM, 1),
            'company_anchor_count' => (int) ($distanceRef['anchor_count'] ?? 0),
            'company_geo_lock_enabled' => (bool) ($distanceRef['geo_lock_enabled'] ?? false),
        ];
    }

    private function autoRegisterDevice(
        int $employeeId,
        string $deviceId,
        string $platform,
        float $lat,
        float $lng,
        float $accuracyM
    ): ?array {
        $baselineDevice = $this->trustedDevices->findLatestActiveByEmployee($employeeId);
        $geoPolicy = $this->loadGeoPolicy();

        $originLat = $lat;
        $originLng = $lng;
        $workingArea = null;

        $nearestAnchor = null;
        if ((bool) ($geoPolicy['geo_lock_enabled'] ?? true)) {
            $nearestAnchor = $this->resolveNearestCompanyAnchor($lat, $lng, $geoPolicy['anchors'] ?? []);
        }

        if ($nearestAnchor !== null) {
            $originLat = (float) $nearestAnchor['lat'];
            $originLng = (float) $nearestAnchor['lng'];
            $workingArea = isset($nearestAnchor['label']) ? (string) $nearestAnchor['label'] : null;
        } elseif ($baselineDevice !== null) {
            $baselineLatRaw = $baselineDevice['origin_lat'] ?? null;
            $baselineLngRaw = $baselineDevice['origin_lng'] ?? null;
            if ($baselineLatRaw !== null && $baselineLngRaw !== null) {
                $originLat = (float) $baselineLatRaw;
                $originLng = (float) $baselineLngRaw;
            }
            if (isset($baselineDevice['working_area_label'])) {
                $workingArea = (string) $baselineDevice['working_area_label'];
            }
        }

        $this->trustedDevices->activateForEmployee(
            $employeeId,
            $deviceId,
            $platform,
            $originLat,
            $originLng,
            $accuracyM,
            $workingArea,
            false
        );

        return $this->trustedDevices->findActiveByEmployeeAndDevice($employeeId, $deviceId);
    }

    /**
     * @param array<string, mixed>|null $trustedDeviceRow
     * @return array{0:float,1:float,2:?string}
     */
    private function resolveBaseline(?array $trustedDeviceRow): array
    {
        if ($trustedDeviceRow !== null) {
            $originLatRaw = $trustedDeviceRow['origin_lat'] ?? null;
            $originLngRaw = $trustedDeviceRow['origin_lng'] ?? null;
            if ($originLatRaw !== null && $originLngRaw !== null) {
                return [
                    (float) $originLatRaw,
                    (float) $originLngRaw,
                    isset($trustedDeviceRow['working_area_label'])
                        ? (string) $trustedDeviceRow['working_area_label']
                        : null,
                ];
            }
        }

        $geoPolicy = $this->loadGeoPolicy();
        $anchors = $geoPolicy['anchors'] ?? [];
        if (is_array($anchors) && $anchors !== []) {
            $first = $anchors[0];
            return [
                (float) $first['lat'],
                (float) $first['lng'],
                isset($first['label']) ? (string) $first['label'] : null,
            ];
        }

        return [self::DEFAULT_OFFICE_LAT, self::DEFAULT_OFFICE_LNG, null];
    }

    /**
     * @param array<string, mixed> $geoPolicy
     * @param array<string, mixed>|null $trustedDeviceRow
     * @return array{distance_m:float,label:?string,anchor_count:int,geo_lock_enabled:bool}
     */
    private function resolveDistanceReference(float $lat, float $lng, ?array $trustedDeviceRow, array $geoPolicy): array
    {
        $anchors = is_array($geoPolicy['anchors'] ?? null) ? $geoPolicy['anchors'] : [];
        $geoLockEnabled = (bool) ($geoPolicy['geo_lock_enabled'] ?? true);
        if ($geoLockEnabled && $anchors !== []) {
            $nearestAnchor = $this->resolveNearestCompanyAnchor($lat, $lng, $anchors);
            if ($nearestAnchor !== null) {
                return [
                    'distance_m' => (float) $nearestAnchor['distance_m'],
                    'label' => isset($nearestAnchor['label']) ? (string) $nearestAnchor['label'] : null,
                    'anchor_count' => count($anchors),
                    'geo_lock_enabled' => true,
                ];
            }
        }

        [$baselineLat, $baselineLng, $baselineLabel] = $this->resolveBaseline($trustedDeviceRow);
        $distanceM = $this->haversineMeters($lat, $lng, $baselineLat, $baselineLng);

        return [
            'distance_m' => $distanceM,
            'label' => $baselineLabel,
            'anchor_count' => count($anchors),
            'geo_lock_enabled' => $geoLockEnabled,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadGeoPolicy(): array
    {
        if ($this->geoPolicyCache !== null) {
            return $this->geoPolicyCache;
        }

        $rows = $this->systemConfigs->findByKeys([
            self::CONFIG_GEO_LOCK_ENABLED_KEY,
            self::CONFIG_ANCHORS_JSON_KEY,
            self::CONFIG_GREEN_RADIUS_KEY,
            self::CONFIG_YELLOW_RADIUS_KEY,
        ]);

        $geoLockRaw = (string) ($rows[self::CONFIG_GEO_LOCK_ENABLED_KEY]['config_value']
            ?? env('ATTENDANCE_COMPANY_GEO_LOCK_ENABLED', '1'));
        $anchorsRaw = (string) ($rows[self::CONFIG_ANCHORS_JSON_KEY]['config_value']
            ?? env('ATTENDANCE_COMPANY_ANCHORS_JSON', ''));
        $greenRaw = $rows[self::CONFIG_GREEN_RADIUS_KEY]['config_value']
            ?? env('ATTENDANCE_GREEN_RADIUS_M', (string) self::GREEN_RADIUS_M);
        $yellowRaw = $rows[self::CONFIG_YELLOW_RADIUS_KEY]['config_value']
            ?? env('ATTENDANCE_YELLOW_RADIUS_M', (string) self::YELLOW_RADIUS_M);

        $anchors = $this->parseCompanyAnchors($anchorsRaw);
        if ($anchors === []) {
            $anchors = $this->parseCompanyAnchors(json_encode(self::DEFAULT_COMPANY_ANCHORS, JSON_UNESCAPED_UNICODE) ?: '');
        }

        $greenRadius = max(10.0, (float) $greenRaw);
        $yellowRadius = max($greenRadius, (float) $yellowRaw);

        $this->geoPolicyCache = [
            'geo_lock_enabled' => $this->toBoolFlag($geoLockRaw, true),
            'anchors' => $anchors,
            'green_radius_m' => $greenRadius,
            'yellow_radius_m' => $yellowRadius,
        ];

        return $this->geoPolicyCache;
    }

    /**
     * @param array<int, array<string, mixed>> $anchors
     * @return array<string, mixed>|null
     */
    private function resolveNearestCompanyAnchor(float $lat, float $lng, array $anchors): ?array
    {
        if ($anchors === []) {
            return null;
        }

        $nearest = null;
        foreach ($anchors as $anchor) {
            $anchorLat = isset($anchor['lat']) ? (float) $anchor['lat'] : null;
            $anchorLng = isset($anchor['lng']) ? (float) $anchor['lng'] : null;
            if ($anchorLat === null || $anchorLng === null) {
                continue;
            }

            $distanceM = $this->haversineMeters($lat, $lng, $anchorLat, $anchorLng);
            if ($nearest === null || $distanceM < (float) $nearest['distance_m']) {
                $nearest = [
                    'lat' => $anchorLat,
                    'lng' => $anchorLng,
                    'label' => isset($anchor['label']) ? (string) $anchor['label'] : null,
                    'distance_m' => $distanceM,
                ];
            }
        }

        return $nearest;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseCompanyAnchors(string $rawJson): array
    {
        $json = trim($rawJson);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $anchors = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!isset($item['lat'], $item['lng']) || !is_numeric($item['lat']) || !is_numeric($item['lng'])) {
                continue;
            }

            $label = isset($item['label']) ? trim((string) $item['label']) : '';
            if ($label === '') {
                $label = isset($item['address']) ? trim((string) $item['address']) : '';
            }
            if ($label === '') {
                $label = 'Mốc công ty';
            }

            $anchors[] = [
                'label' => $label,
                'address' => isset($item['address']) ? trim((string) $item['address']) : null,
                'lat' => (float) $item['lat'],
                'lng' => (float) $item['lng'],
            ];
        }

        return $anchors;
    }

    private function toBoolFlag(string $value, bool $default = false): bool
    {
        $normalized = strtoupper(trim($value));
        if ($normalized === '') {
            return $default;
        }

        return in_array($normalized, ['1', 'TRUE', 'YES', 'ON'], true);
    }

    /**
     * @param array<string, mixed> $risk
     */
    private function mapPrecheckStatus(array $risk): string
    {
        $reason = strtoupper((string) ($risk['reason_code'] ?? ''));
        $riskLevel = strtoupper((string) ($risk['risk_level'] ?? ''));
        $action = strtoupper((string) ($risk['action'] ?? 'BLOCK'));

        if ($reason === 'DEVICE_NOT_TRUSTED') {
            return 'unregistered_device';
        }

        if ($action === 'BLOCK') {
            return 'blocked_outside_zone';
        }

        if ($riskLevel === 'GREEN') {
            return 'green';
        }
        if ($riskLevel === 'YELLOW') {
            return 'yellow';
        }
        return 'red';
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

    private function supportsAttendanceSecondSlots(): bool
    {
        if ($this->attendanceSecondSlotsSupported !== null) {
            return $this->attendanceSecondSlotsSupported;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'attendances'
                  AND column_name IN ('check_in_time_2', 'check_out_time_2')";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute();
        $total = (int) (($stmt->fetch()['total'] ?? 0));

        $this->attendanceSecondSlotsSupported = $total >= 2;
        return $this->attendanceSecondSlotsSupported;
    }

    private function resolveSupportedAttendanceMethod(string $column): string
    {
        if (isset($this->attendanceMethodCache[$column])) {
            return $this->attendanceMethodCache[$column];
        }

        $fallback = 'MOBILE';
        if (!in_array($column, ['check_in_method', 'check_out_method'], true)) {
            $this->attendanceMethodCache[$column] = $fallback;
            return $fallback;
        }

        $sql = "SELECT data_type, column_type
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                  AND table_name = 'attendances'
                  AND column_name = :column_name
                LIMIT 1";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['column_name' => $column]);
        $meta = $stmt->fetch();
        if ($meta === false) {
            $this->attendanceMethodCache[$column] = $fallback;
            return $fallback;
        }

        $dataType = strtolower((string) ($meta['data_type'] ?? ''));
        if ($dataType !== 'enum') {
            $this->attendanceMethodCache[$column] = $fallback;
            return $fallback;
        }

        $columnType = (string) ($meta['column_type'] ?? '');
        $enumValues = $this->parseEnumValuesFromColumnType($columnType);
        if ($enumValues === []) {
            $this->attendanceMethodCache[$column] = $fallback;
            return $fallback;
        }

        foreach (['MOBILE', 'APP', 'GPS', 'MANUAL', 'MÁY_QUÉT', 'MAY_QUET'] as $candidate) {
            if (in_array($candidate, $enumValues, true)) {
                $this->attendanceMethodCache[$column] = $candidate;
                return $candidate;
            }
        }

        $this->attendanceMethodCache[$column] = (string) $enumValues[0];
        return $this->attendanceMethodCache[$column];
    }

    /**
     * @return array<int, string>
     */
    private function parseEnumValuesFromColumnType(string $columnType): array
    {
        if (!preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/u", $columnType, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn(string $value): string => stripcslashes($value),
            $matches[1] ?? []
        ), static fn(string $value): bool => $value !== ''));
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
