<?php
require_once __DIR__ . '/bootstrap.php';
use App\Core\Database;
$db = Database::connection();
$today = date('Y-m-d');
$stmt = $db->query("SELECT a.*, e.employee_code, e.full_name 
                    FROM attendances a 
                    JOIN employees e ON e.employee_id = a.employee_id 
                    ORDER BY a.attendance_id DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
