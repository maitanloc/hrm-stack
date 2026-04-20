<?php
require_once __DIR__ . '/bootstrap.php';
$db = \App\Core\Database::connection();
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
echo json_encode($tables);
