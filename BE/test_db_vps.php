<?php
try {
    $pdo = new PDO("mysql:host=mysql;dbname=HRM_SYSTEM;charset=utf8mb4", "hrm_user", "hrm_pass_2026_secure");
    echo "SUCCESS";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage();
}
