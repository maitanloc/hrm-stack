<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Database Connection Test ===\n\n";

// Test 1: Check if PDO extension is loaded
echo "1. PDO Extension: " . (extension_loaded('pdo') ? "LOADED\n" : "NOT LOADED\n");
echo "2. PDO MySQL: " . (extension_loaded('pdo_mysql') ? "LOADED\n" : "NOT LOADED\n");

// Test 2: Check config
echo "\n3. Configuration:\n";
echo "   DB_HOST (from env): " . (getenv('DB_HOST') ?: 'NOT SET') . "\n";
echo "   DB_PORT (from env): " . (getenv('DB_PORT') ?: '3306') . "\n";
echo "   DB_DATABASE (from env): " . (getenv('DB_DATABASE') ?: 'NOT SET') . "\n";
echo "   DB_USERNAME (from env): " . (getenv('DB_USERNAME') ?: 'NOT SET') . "\n\n";

// Test 3: Try to connect
echo "4. Connection Test:\n";
try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'test';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: '';
    
    echo "   Attempting: mysql://$username@$host:$port/$database\n";
    
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    echo "   Result: ✓ CONNECTION SUCCESSFUL\n";
    
    // Test 4: Run a query
    echo "\n5. Query Test:\n";
    $result = $pdo->query("SELECT 1 AS test")->fetch();
    echo "   SELECT 1: " . json_encode($result) . "\n";
    
} catch (PDOException $e) {
    echo "   Result: ✗ CONNECTION FAILED\n\n";
    echo "   Error Code: " . $e->getCode() . "\n";
    echo "   Error Message: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), '2002') !== false) {
        echo "\n   Diagnosis: [2002] Connection refused\n";
        echo "   Possible causes:\n";
        echo "   - MySQL not running\n";
        echo "   - Wrong hostname/IP\n";
        echo "   - Port not listening\n";
        echo "   - Firewall blocking\n";
    }
}
?>
