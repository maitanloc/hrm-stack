<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Validator;
use App\Models\Employee;
use App\Models\SystemConfig;
use App\Services\FaceIdentificationService;

class FaceController extends Controller
{
    private const PUBLIC_GENERAL_KEYS = [
        'attendance_company_geo_lock_enabled',
        'attendance_company_anchor_points_json',
        'attendance_green_radius_m',
        'attendance_yellow_radius_m',
    ];

    private Employee $employees;
    private FaceIdentificationService $faceService;
    private SystemConfig $systemConfigs;

    public function __construct()
    {
        $this->employees = new Employee();
        $this->faceService = new FaceIdentificationService();
        $this->systemConfigs = new SystemConfig();
    }

    public function register(Request $request): array
    {
        $authUser = $request->attribute('auth_user', []);
        $employeeId = (int) ($authUser['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            throw new HttpException('Missing authenticated employee.', 401, 'unauthorized');
        }

        return (new FaceEnrollmentController())->enroll($request, ['id' => $employeeId]);
    }

    public function recognizeAndCheckIn(Request $request): array
    {
        $payload = Validator::validate($request->all(), [
            'image' => ['required', 'string'],
        ]);

        $hasAttendancePayload =
            is_numeric($request->input('lat')) &&
            is_numeric($request->input('lng')) &&
            is_numeric($request->input('accuracy_m'));

        if ($hasAttendancePayload) {
            return (new KioskController())->faceAttendance($request);
        }

        $identifyResult = $this->faceService->identify($this->stripBase64Prefix($payload['image']));
        if (!$identifyResult['success'] || empty($identifyResult['employee_code'])) {
            throw new HttpException(
                $identifyResult['error'] ?? 'Không thể nhận diện khuôn mặt.',
                403,
                'face_not_recognized'
            );
        }

        $employee = $this->employees->findByCode((string) $identifyResult['employee_code']);
        if (!$employee) {
            throw new HttpException('Khuôn mặt chưa được đăng ký trong hệ thống.', 404, 'employee_not_found');
        }

        return $this->ok([
            'employee_id' => (int) ($employee['employee_id'] ?? 0),
            'employee_code' => (string) ($employee['employee_code'] ?? ''),
            'full_name' => (string) ($employee['full_name'] ?? ''),
            'department_name' => (string) ($employee['department_name'] ?? ''),
            'confidence' => (float) ($identifyResult['confidence'] ?? 0.0),
        ], 'Recognized');
    }

    public function embeddings(Request $request): array
    {
        return (new KioskController())->syncEmbeddings($request);
    }

    public function publicGeneralSettings(Request $request): array
    {
        $rows = $this->systemConfigs->findByKeys(self::PUBLIC_GENERAL_KEYS);

        return $this->ok([
            'attendance_company_geo_lock_enabled' => (int) ($rows['attendance_company_geo_lock_enabled']['config_value'] ?? 1) === 1,
            'attendance_company_anchor_points_json' => (string) ($rows['attendance_company_anchor_points_json']['config_value'] ?? '[]'),
            'attendance_green_radius_m' => (string) ($rows['attendance_green_radius_m']['config_value'] ?? '120'),
            'attendance_yellow_radius_m' => (string) ($rows['attendance_yellow_radius_m']['config_value'] ?? '250'),
        ], 'Public general setting');
    }

    private function stripBase64Prefix(string $data): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $data) === 1) {
            return substr($data, strpos($data, ',') + 1);
        }

        return $data;
    }
}
