<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$sqlFile = __DIR__ . '/seed_test.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Missing seed_test.sql\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Cannot read seed_test.sql\n");
    exit(1);
}

if (!mb_check_encoding($sql, 'UTF-8')) {
    fwrite(STDERR, "seed_test.sql is not valid UTF-8\n");
    exit(1);
}

$db = Database::connection();
try {
    $db->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if ($statement === '') {
            continue;
        }
        $db->exec($statement);
    }
    fwrite(STDOUT, "seed_test.sql executed successfully.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Seed failed: " . $exception->getMessage() . "\n");
    exit(1);
}
