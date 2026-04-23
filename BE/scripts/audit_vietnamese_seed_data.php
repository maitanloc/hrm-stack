<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$repoRoot = dirname(__DIR__, 2);
$beRoot = dirname(__DIR__);

$runtimeRows = $pdo->query(
    "SELECT
        @@character_set_server AS character_set_server,
        @@collation_server AS collation_server,
        @@character_set_database AS character_set_database,
        @@collation_database AS collation_database,
        @@character_set_connection AS character_set_connection,
        @@character_set_client AS character_set_client,
        @@character_set_results AS character_set_results"
)->fetch(\PDO::FETCH_ASSOC);

$sourceFiles = [
    $repoRoot . DIRECTORY_SEPARATOR . 'SQL_hackathon v4.sql',
    $repoRoot . DIRECTORY_SEPARATOR . 'data.sql',
    $beRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'test_accounts.tsv',
    $beRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'test_accounts_by_role.md',
    $beRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'seed_test.sql',
];

$targets = [
    ['table' => 'employees', 'column' => 'full_name'],
    ['table' => 'employees', 'column' => 'status'],
    ['table' => 'departments', 'column' => 'department_name'],
    ['table' => 'positions', 'column' => 'position_name'],
    ['table' => 'roles', 'column' => 'role_name'],
    ['table' => 'roles', 'column' => 'description'],
    ['table' => 'request_types', 'column' => 'request_type_name'],
    ['table' => 'request_types', 'column' => 'description'],
    ['table' => 'request_types', 'column' => 'category'],
    ['table' => 'requests', 'column' => 'status'],
    ['table' => 'attendances', 'column' => 'check_in_method'],
    ['table' => 'attendances', 'column' => 'check_out_method'],
    ['table' => 'attendances', 'column' => 'work_type'],
    ['table' => 'shift_assignments', 'column' => 'status'],
    ['table' => 'contracts', 'column' => 'status'],
    ['table' => 'leave_requests', 'column' => 'from_session'],
    ['table' => 'leave_requests', 'column' => 'to_session'],
    ['table' => 'overtime_requests', 'column' => 'status'],
];

echo "[runtime]\n";
foreach ($runtimeRows ?: [] as $key => $value) {
    echo sprintf("%s=%s\n", $key, (string) $value);
}

echo "\n[source_files]\n";
foreach ($sourceFiles as $path) {
    $label = relativePath($repoRoot, $path);
    if (!is_file($path)) {
        echo sprintf("%s | missing\n", $label);
        continue;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        echo sprintf("%s | unreadable\n", $label);
        continue;
    }

    $utf8Ok = mb_check_encoding($raw, 'UTF-8') ? 'yes' : 'no';
    $questionMarks = substr_count($raw, '?');
    $mojibakeMatches = preg_match_all('/(Ã|Â|â|Æ|Ð|Ñ|áº|á»|Ä|Å|Ä‘|Äƒ|�)/u', $raw);

    echo sprintf(
        "%s | utf8=%s | bytes=%d | question_marks=%d | mojibake_markers=%d\n",
        $label,
        $utf8Ok,
        strlen($raw),
        $questionMarks,
        is_int($mojibakeMatches) ? $mojibakeMatches : 0
    );
}

echo "\n[corruption_scope]\n";
foreach ($targets as $target) {
    $table = $target['table'];
    $column = $target['column'];

    $countStmt = $pdo->query(
        sprintf(
            "SELECT COUNT(*) AS affected
             FROM `%s`
             WHERE `%s` IS NOT NULL AND `%s` LIKE '%%?%%'",
            $table,
            $column,
            $column
        )
    );
    $count = (int) (($countStmt?->fetch(\PDO::FETCH_ASSOC)['affected'] ?? 0));

    $sampleStmt = $pdo->query(
        sprintf(
            "SELECT DISTINCT `%s`
             FROM `%s`
             WHERE `%s` IS NOT NULL AND `%s` LIKE '%%?%%'
             ORDER BY `%s`
             LIMIT 3",
            $column,
            $table,
            $column,
            $column,
            $column
        )
    );
    $samples = $sampleStmt ? $sampleStmt->fetchAll(\PDO::FETCH_COLUMN) : [];

    echo sprintf(
        "%s.%s | affected_rows=%d | samples=%s\n",
        $table,
        $column,
        $count,
        $samples === [] ? '[]' : json_encode($samples, JSON_UNESCAPED_UNICODE)
    );
}

function relativePath(string $repoRoot, string $path): string
{
    if (str_starts_with($path, $repoRoot . DIRECTORY_SEPARATOR)) {
        return str_replace($repoRoot . DIRECTORY_SEPARATOR, '', $path);
    }

    return $path;
}
