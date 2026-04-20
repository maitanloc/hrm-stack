<?php
declare(strict_types=1);

require_once __DIR__ . '/BE/bootstrap.php';

use App\Core\Auth;
use App\Core\TextEncoding;

try {
    $email = 'hai.do@company.com';
    $password = 'NV0009';
    
    echo "Attempting login for $email...\n";
    $result = Auth::attempt($email, $password);
    
    // Simulate Response::send formatting
    $status = 200;
    $success = $status >= 200 && $status < 400;
    $message = (string) TextEncoding::fixMojibake('Login successful');
    $data = TextEncoding::deepFixMojibake($result);

    $body = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ];

    echo "Response body structure:\n";
    echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($e instanceof \App\Core\HttpException) {
        echo "Code: " . $e->getErrorCode() . " Status: " . $e->getStatusCode() . "\n";
    }
}
