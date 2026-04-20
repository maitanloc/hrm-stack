<?php
require __DIR__ . '/BE/vendor/autoload.php';
$db = \App\Core\Database::connection();
echo "--- TABLES ---\n";
foreach($db->query('SHOW TABLES') as $r) {
    $table = array_values($r)[0];
    echo "- $table\n";
    if ($table === 'employees' || str_contains($table, 'face') || str_contains($table, 'vector')) {
        echo "  COLUMNS:\n";
        foreach($db->query("DESCRIBE $table") as $col) {
            echo "    " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
}
