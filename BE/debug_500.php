<?php
require_once 'BE/bootstrap.php';
use App\Controllers\Api\V1\WorkforceController;
use App\Core\Request;

// Mock Request
$request = new Request();
// Mock User (Admin)
$authUser = [
    'employee_id' => 1,
    'hierarchy_employee_ids' => [1, 2, 3],
    'roles' => ['ADMIN']
];
$request->setAttribute('auth_user', $authUser);
// Mock Query Params
$_GET['department_id'] = '1';
$_GET['from_date'] = '2026-04-19';
$_GET['to_date'] = '2026-04-25';

$controller = new WorkforceController();

echo "Testing /team-schedule...\n";
try {
    $res = $controller->teamSchedule($request);
    echo "SUCCESS: " . json_encode($res) . "\n";
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nTesting /team-schedule/warnings...\n";
try {
    $res = $controller->teamScheduleWarnings($request);
    echo "SUCCESS: " . json_encode($res) . "\n";
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
