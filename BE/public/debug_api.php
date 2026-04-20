<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== BE Runtime Debug ===\n\n";

require_once __DIR__ . '/../bootstrap.php';

echo "1. Current .env variables:\n";
echo "DB_HOST: " . env('DB_HOST') . "\n";
echo "DB_DATABASE: " . env('DB_DATABASE') . "\n";
echo "DB_USERNAME: " . env('DB_USERNAME') . "\n";

echo "\n2. PDO MySQL connection test:\n";
try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s', env('DB_HOST'), env('DB_PORT'), env('DB_DATABASE'));
    $pdo = new PDO($dsn, env('DB_USERNAME'), env('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "✅ Success: Connected to " . env('DB_HOST') . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM employees");
    $count = $stmt->fetchColumn();
    echo "✅ Success: Found $count employees.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n3. PHP Info summary:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
