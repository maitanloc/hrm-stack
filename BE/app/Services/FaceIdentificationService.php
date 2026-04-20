<?php
declare(strict_types=1);

namespace App\Services;

use Exception;

/**
 * Service to identify employees via Facial Recognition AI engine.
 * Communicates with a Dockerized AI service over Tailscale.
 */
class FaceIdentificationService
{
    private string $aiUrl;
    private float $threshold;

    public function __construct()
    {
        // Default to the user-provided Tailscale IP and assuming port 6868
        $this->aiUrl = (string) env('KIOSK_AI_ENGINE_URL', 'http://100.126.50.2:6868');
        $this->threshold = (float) env('KIOSK_AI_IDENTIFY_THRESHOLD', 0.4);
    }

    /**
     * Identify an employee from a base64 encoded image frame.
     * 
     * @param string $base64Image The image data (without data:image/... prefix)
     * @return array{success: bool, employee_code: ?string, confidence: float, error: ?string}
     */
    public function identify(string $base64Image): array
    {
        try {
            $response = $this->callAiEngine('/search', [
                'image' => $base64Image,
                'threshold' => $this->threshold
            ]);

            if (empty($response['match']) || !isset($response['employee_code'])) {
                return [
                    'success' => false,
                    'employee_code' => null,
                    'confidence' => (float) ($response['confidence'] ?? 0.0),
                    'error' => $response['message'] ?? 'Identification failed'
                ];
            }

            return [
                'success' => true,
                'employee_code' => (string) $response['employee_code'],
                'confidence' => (float) ($response['confidence'] ?? 1.0),
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'employee_code' => null,
                'confidence' => 0.0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract face embeddings from multiple images in one call.
     * 
     * @param array $base64Images Array of image data (without prefix)
     * @return array{success: bool, embeddings_list: array, error: ?string}
     */
    public function extractMultiple(array $base64Images): array
    {
        try {
            $response = $this->callAiEngine('/extract', [
                'images' => $base64Images
            ]);

            if (empty($response['embeddings'])) {
                return [
                    'success' => false,
                    'embeddings_list' => [],
                    'error' => 'No faces detected in images'
                ];
            }

            return [
                'success' => true,
                'embeddings_list' => $response['embeddings'],
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'embeddings_list' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract face embeddings from an image.
     * 
     * @param string $base64Image The image data (without prefix)
     * @return array{success: bool, embeddings: array, error: ?string}
     */
    public function extract(string $base64Image): array
    {
        try {
            $response = $this->callAiEngine('/extract', [
                'images' => [$base64Image] // Backend expects a list
            ]);

            if (empty($response['embeddings'])) {
                return [
                    'success' => false,
                    'embeddings' => [],
                    'error' => 'No face detected in image'
                ];
            }

            return [
                'success' => true,
                'embeddings' => $response['embeddings'][0],
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'embeddings' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Trigger a bulk sync of embeddings to the AI service.
     */
    public function triggerSync(array $payload): bool
    {
        try {
            $this->callAiEngine('/sync', $payload);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Helper to call the AI engine via cURL.
     */
    private function callAiEngine(string $endpoint, array $payload): array
    {
        $url = rtrim($this->aiUrl, '/') . $endpoint;
        $ch = curl_init($url);
        
        $jsonData = json_encode($payload);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new Exception("AI Engine connection failed: $error");
        }

        if ($httpCode >= 400) {
            $msg = "AI Engine returned HTTP $httpCode";
            if ($httpCode === 404) {
                $msg .= " (Endpoint Not Found)";
            }
            throw new Exception("$msg: $result");
        }

        $decoded = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("AI Engine returned invalid JSON: " . json_last_error_msg());
        }

        return $decoded;
    }
}
