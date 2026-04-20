<?php
require_once __DIR__ . '/bootstrap.php';
$db = \App\Core\Database::connection();
$sql = "
CREATE TABLE IF NOT EXISTS attendance_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    shift_id INT NULL,
    event_type VARCHAR(50) NOT NULL,
    source_type VARCHAR(50) DEFAULT 'face_quick_web',
    event_time DATETIME NOT NULL,
    gps_lat DECIMAL(10, 8) NULL,
    gps_lng DECIMAL(11, 8) NULL,
    gps_accuracy DECIMAL(8, 2) NULL,
    geofence_status VARCHAR(20) NULL,
    geofence_distance DECIMAL(10, 2) NULL,
    face_match_score DECIMAL(5, 2) NULL,
    result_code VARCHAR(50) NOT NULL,
    warning_code VARCHAR(50) NULL,
    late_minutes INT DEFAULT 0,
    early_checkout_minutes INT DEFAULT 0,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    note TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
";
$db->exec($sql);
echo "attendance_events table created or exists.\n";
