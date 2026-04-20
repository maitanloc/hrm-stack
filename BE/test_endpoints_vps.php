<?php
require_once 'bootstrap.php';
use App\Controllers\Api\V1\WorkforceController;
use App\Core\Request;

try {
    $request = new Request("GET", "/api/v1/team-schedule", [], [], []);
    $request->setAttribute('auth_user', [
        'employee_id' => 1,
        'hierarchy_employee_ids' => [1],
        'roles' => ['ADMIN']
    ]);
    $_GET['department_id'] = '1';
    $_GET['from_date'] = '2026-04-19';
    $_GET['to_date'] = '2026-04-25';

    $controller = new WorkforceController();
    
    echo "TESTING teamSchedule...\n";
    $res1 = $controller->teamSchedule($request);
    echo "teamSchedule STATUS: " . ($res1['status'] ?? 'UNKNOWN') . "\n";
    
    echo "TESTING teamScheduleWarnings...\n";
    $res2 = $controller->teamScheduleWarnings($request);
    echo "teamScheduleWarnings STATUS: " . ($res2['status'] ?? 'UNKNOWN') . "\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
