<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function send(array $payload): void
    {
        if (($payload['response_type'] ?? null) === 'binary') {
            $status = (int) ($payload['status'] ?? 200);
            http_response_code($status);

            $headers = $payload['headers'] ?? [];
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    header((string) $name . ': ' . (string) $value);
                }
            }

            $body = $payload['body'] ?? '';
            if (is_string($body)) {
                echo $body;
            }
            return;
        }

        $status = (int) ($payload['status'] ?? 200);
        $success = $status >= 200 && $status < 400;
        $message = (string) TextEncoding::fixMojibake($payload['message'] ?? ($success ? 'OK' : 'Request failed'));
        $data = TextEncoding::deepFixMojibake($payload['data'] ?? null);
        $meta = TextEncoding::deepFixMojibake($payload['meta'] ?? null);
        $error = TextEncoding::deepFixMojibake($payload['error'] ?? null);

        http_response_code($status);
        if ($status === 204) {
            return;
        }
        header('Content-Type: application/json; charset=utf-8');

        $body = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $body['meta'] = $meta;
        }
        if ($error !== null) {
            $body['error'] = $error;
        }

        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log('JSON ENCODE ERROR: ' . json_last_error_msg());
            $errorBody = [
                'success' => false,
                'error' => 'json_encode_error',
                'message' => 'Lỗi định dạng dữ liệu (JSON Encode Error): ' . json_last_error_msg()
            ];
            echo json_encode($errorBody, JSON_UNESCAPED_UNICODE);
        } else {
            echo $json;
        }
    }
}
