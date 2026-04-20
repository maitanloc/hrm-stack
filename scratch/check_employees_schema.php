<?php
require_once __DIR__ . '/../BE/bootstrap.php';
$db = \App\Core\Database::connection();
$stmt = $db->query("DESCRIBE employees");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($columns, JSON_PRETTY_PRINT);
