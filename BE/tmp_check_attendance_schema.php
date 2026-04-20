<?php
require_once __DIR__ . '/bootstrap.php';
use App\Core\Database;
$db = Database::connection();
$stmt = $db->query("DESCRIBE attendances");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($columns, JSON_PRETTY_PRINT);
