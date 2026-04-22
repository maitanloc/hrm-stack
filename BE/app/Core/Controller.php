<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function ok(mixed $data = null, string $message = 'Success', array $meta = []): array
    {
        $response = [
            'status' => 200,
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
        
        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return $response;
    }

    protected function created(mixed $data = null, string $message = 'Created'): array
    {
        return [
            'status' => 201,
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function error(string $message = 'Error', int $status = 400, string $errorCode = 'error', mixed $data = null): array
    {
        return [
            'status' => $status,
            'success' => false,
            'error' => $errorCode,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected function noContent(string $message = 'No content'): array
    {
        return [
            'status' => 204,
            'success' => true,
            'message' => $message,
            'data' => null,
        ];
    }
}
