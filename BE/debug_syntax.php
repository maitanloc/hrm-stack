<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    require_once 'bootstrap.php';
    echo "Bootstrap OK\n";

    $controllers = [
        'App\Controllers\Api\V1\WorkforceController',
        'App\Controllers\Api\V1\EmployeeController',
        'App\Controllers\Api\V1\DepartmentController',
        'App\Controllers\Api\V1\AttendanceController',
        'App\Controllers\Api\V1\NotificationController'
    ];

    foreach ($controllers as $c) {
        if (class_exists($c)) {
            echo "Class $c exists\n";
        } else {
            echo "Failed to load $c\n";
        }
    }

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
