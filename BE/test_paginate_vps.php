<?php
require_once 'bootstrap.php';
try {
    $emp = new App\Models\Employee();
    $res = $emp->paginateList(0, 1000, null, null, 1);
    echo "PAGINATE SUCCESS: " . count($res['items']) . " employees found";
} catch (Throwable $e) {
    echo "PAGINATE FAILED: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
