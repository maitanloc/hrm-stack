<?php
declare(strict_types=1);

$bootstrapPath = __DIR__ . '/bootstrap.php';
if (!is_file($bootstrapPath)) {
    $bootstrapPath = __DIR__ . '/BE/bootstrap.php';
}
require $bootstrapPath;

$attendance = new App\Models\Attendance();
$reflection = new ReflectionClass($attendance);

$method = $reflection->getMethod('statusEnumValues');
$method->setAccessible(true);
$enumValues = $method->invoke($attendance);

$normalize = $reflection->getMethod('normalizeStatus');
$normalize->setAccessible(true);
$normalized = $normalize->invoke($attendance, 'CHECKIN_OK');

$dbRef = $reflection->getProperty('db');
$dbRef->setAccessible(true);
/** @var PDO $pdo */
$pdo = $dbRef->getValue($attendance);
$currentDb = (string) ($pdo->query('SELECT DATABASE()')->fetchColumn() ?: '');
$colCount = (int) ($pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'attendances' AND column_name = 'status'")->fetchColumn() ?: 0);
$columnType = (string) ($pdo->query("SELECT column_type FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'attendances' AND column_name = 'status' LIMIT 1")->fetchColumn() ?: '');

echo "ENUM_VALUES:\n";
var_export($enumValues);
echo "\nNORMALIZED_CHECKIN_OK:\n";
var_export($normalized);
echo "\nCURRENT_DB:\n";
var_export($currentDb);
echo "\nINFO_SCHEMA_STATUS_COL_COUNT:\n";
var_export($colCount);
echo "\nCOLUMN_TYPE:\n";
var_export($columnType);
echo "\n";
