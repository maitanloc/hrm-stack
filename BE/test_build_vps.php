<?php
require_once 'bootstrap.php';
try {
    $service = new App\Services\PlanningContextService();
    $res = $service->build(1, date('Y-m-d'));
    echo "BUILD SUCCESS: " . json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo "BUILD FAILED: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
