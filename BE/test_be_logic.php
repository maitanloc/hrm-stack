<?php
require_once 'BE/bootstrap.php';
use App\Services\PlanningContextService;

try {
    $service = new PlanningContextService();
    $res = $service->build(1, date('Y-m-d'));
    echo "SUCCESS: " . json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
    echo "\nTRACE:\n" . $e->getTraceAsString();
}
