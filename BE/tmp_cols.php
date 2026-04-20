<?php
require_once __DIR__ . '/bootstrap.php';
$db = \App\Core\Database::connection();
$stmt = $db->query("DESCRIBE attendance_prechecks");
$cols = $stmt->fetchAll(\PDO::FETCH_ASSOC);
echo json_encode($cols);
