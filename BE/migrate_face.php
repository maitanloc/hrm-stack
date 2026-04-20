<?php
require 'vendor/autoload.php';
$db = \App\Core\Database::connection();
$sql = "CREATE TABLE IF NOT EXISTS employee_face_vectors (
    vector_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    vector JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (employee_id),
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $db->exec($sql);
    echo "Migration successful: employee_face_vectors table created.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
unlink(__FILE__); // Self-destruct for security
