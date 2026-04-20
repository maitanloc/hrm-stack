<?php
require_once __DIR__ . '/BE/bootstrap.php';
try {
    $db = \App\Core\Database::connection();
    echo "✅ Kết nối thành công!\n";
    $stmt = $db->query("SHOW DATABASES");
    echo "Danh sách các Database hiện có:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . array_values($row)[0] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}
