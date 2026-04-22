<?php
declare(strict_types=1);

namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Core\HttpException;
use App\Models\Employee;
use App\Services\FaceIdentificationService;
use PDO;

class FaceEnrollmentController extends Controller
{
    private Employee $employees;
    private FaceIdentificationService $faceService;

    public function __construct()
    {
        $this->employees = new Employee();
        $this->faceService = new FaceIdentificationService();
    }

    /**
     * Enroll faces for a specific employee (requires 3 views).
     * POST /api/v1/employees/{id}/enroll-face
     */
    public function enroll(Request $request, array $params): array
    {
        $employeeId = (int) ($params['id'] ?? 0);
        error_log("FaceEnrollment: Starting enrollment for employee ID: " . $employeeId);
        
        $employee = $this->employees->findWithDepartment($employeeId);

        if (!$employee) {
            error_log("FaceEnrollment: Employee not found ID: " . $employeeId);
            throw new HttpException('Employee not found', 404);
        }

        $payload = Validator::validate($request->all(), [
            'images' => ['required', 'array'], // Array of Base64 strings
        ]);

        $images = array_values(array_filter(
            $payload['images'],
            static fn($image): bool => is_string($image) && trim($image) !== ''
        ));

        if (count($images) < 3) {
            throw new HttpException('At least 3 valid face images are required', 422, 'validation_error');
        }

        $rawImages = array_map([$this, 'stripBase64Prefix'], $images);

        // 1. Extract Embeddings from AI Service
        $extractResult = $this->faceService->extractMultiple($rawImages);

        if (!$extractResult['success']) {
            throw new HttpException($extractResult['error'] ?? 'Failed to extract face features', 422);
        }

        $embeddings = $extractResult['embeddings_list']; // Array of arrays

        if (empty($embeddings)) {
            throw new HttpException('No face detected in any of the images', 422);
        }

        // 2. Average the vectors for a robust template
        $startTime = microtime(true);
        error_log("FaceEnrollment: Calling AI service for encoding...");
        $meanVector = $this->calculateMeanVector($embeddings);
        $duration = round(microtime(true) - $startTime, 2);
        error_log("FaceEnrollment: AI processing finished in {$duration}s");

        // 3. Save to Database
        $this->saveToDb($employeeId, $meanVector);

        // 4. Trigger AI Service Sync
        $this->syncAiService();

        return $this->ok([
            'employee_id' => $employeeId,
            'employee_code' => $employee['employee_code'],
            'message' => 'Face enrolled successfully with 3 viewpoints'
        ]);
    }

    private function calculateMeanVector(array $embeddings): array
    {
        $count = count($embeddings);
        $size = count($embeddings[0]);
        $mean = array_fill(0, $size, 0.0);

        foreach ($embeddings as $vector) {
            foreach ($vector as $i => $value) {
                $mean[$i] += $value;
            }
        }

        foreach ($mean as $i => $value) {
            $mean[$i] /= $count;
        }

        return $mean;
    }

    private function saveToDb(int $employeeId, array $vector): void
    {
        $db = \App\Core\Database::connection();
        
        // Delete old vectors for this employee (for now, 1 vector per employee)
        $stmt = $db->prepare("DELETE FROM employee_face_vectors WHERE employee_id = ?");
        $stmt->execute([$employeeId]);

        // Insert new vector
        $stmt = $db->prepare("INSERT INTO employee_face_vectors (employee_id, vector) VALUES (?, ?)");
        $stmt->execute([
            $employeeId,
            json_encode($vector)
        ]);
    }

    private function syncAiService(): void
    {
        $db = \App\Core\Database::connection();
        $sql = "SELECT e.employee_code, efv.vector 
                FROM employee_face_vectors efv
                JOIN employees e ON e.employee_id = efv.employee_id";
        
        $stmt = $db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $payload = [];
        foreach ($rows as $row) {
            $payload[] = [
                'employee_code' => $row['employee_code'],
                'vector' => json_decode($row['vector'], true)
            ];
        }

        $this->faceService->triggerSync($payload);
    }

    private function stripBase64Prefix(string $data): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
        }
        return $data;
    }
}
