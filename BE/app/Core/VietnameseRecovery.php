<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

final class VietnameseRecovery
{
    private const MOJIBAKE_PATTERNS = [
        '/Ã[\x{0080}-\x{024F}\x{1E00}-\x{1EFF}]/u',
        '/Ä[\x{0080}-\x{024F}\x{1E00}-\x{1EFF}]/u',
        '/Å[\x{0080}-\x{024F}\x{1E00}-\x{1EFF}]/u',
        '/Æ[\x{0080}-\x{024F}\x{1E00}-\x{1EFF}]/u',
        '/Ð[\x{0080}-\x{024F}\x{1E00}-\x{1EFF}]/u',
        '/Ñ[\x{0080}-\x{024F}\x{1E00}-\x{1EFF}]/u',
        '/áº./u',
        '/á»./u',
        '/â€./u',
        '/â€™/u',
        '/â€œ/u',
        '/â€“/u',
        '/â€”/u',
        '/â€¦/u',
        '/�/u',
    ];
    private const VIETNAMESE_RE = '/[À-ỹĐđ]/u';
    private const CP1252_UNICODE_TO_BYTE = [
        8364 => 0x80,
        8218 => 0x82,
        402 => 0x83,
        8222 => 0x84,
        8230 => 0x85,
        8224 => 0x86,
        8225 => 0x87,
        710 => 0x88,
        8240 => 0x89,
        352 => 0x8a,
        8249 => 0x8b,
        338 => 0x8c,
        381 => 0x8e,
        8216 => 0x91,
        8217 => 0x92,
        8220 => 0x93,
        8221 => 0x94,
        8226 => 0x95,
        8211 => 0x96,
        8212 => 0x97,
        732 => 0x98,
        8482 => 0x99,
        353 => 0x9a,
        8250 => 0x9b,
        339 => 0x9c,
        382 => 0x9e,
        376 => 0x9f,
    ];
    private const DECODER_FIXTURES = [
        'LÃª Há»“ng Quang' => 'Lê Hồng Quang',
        'Trá»‹nh Quá»‘c Äáº¡i' => 'Trịnh Quốc Đại',
        'Phiáº¿u lÆ°Æ¡ng thÃ¡ng 03/2024' => 'Phiếu lương tháng 03/2024',
        'ThÃ´ng bÃ¡o lá»‹ch nghá»‰ lá»… 30/04 vÃ  01/05' => 'Thông báo lịch nghỉ lễ 30/04 và 01/05',
    ];

    private function __construct()
    {
    }

    public static function beRoot(): string
    {
        return rtrim(base_path(''), DIRECTORY_SEPARATOR);
    }

    public static function repoRoot(): string
    {
        $beRoot = self::beRoot();
        $parent = dirname($beRoot);

        if (
            is_dir($parent . DIRECTORY_SEPARATOR . 'BE')
            || is_file($parent . DIRECTORY_SEPARATOR . 'docker-compose.yml')
            || is_file($parent . DIRECTORY_SEPARATOR . 'import-db.sh')
        ) {
            return $parent;
        }

        return $beRoot;
    }

    public static function defaultArchivePath(): string
    {
        return self::repoRoot() . DIRECTORY_SEPARATOR . 'hrm-stack.tar.gz';
    }

    public static function timestamp(): string
    {
        return date('Ymd_His');
    }

    /**
     * @return array{ok: bool, results: array<int, array<string, mixed>>}
     */
    public static function runDecoderFixtures(): array
    {
        $results = [];
        $ok = true;

        foreach (self::DECODER_FIXTURES as $input => $expected) {
            $actual = self::decodeArchiveMojibake($input);
            $caseOk = $actual === $expected;
            $ok = $ok && $caseOk;
            $results[] = [
                'input' => $input,
                'expected' => $expected,
                'actual' => $actual,
                'ok' => $caseOk,
            ];
        }

        return [
            'ok' => $ok,
            'results' => $results,
        ];
    }

    public static function assertDecoderFixtures(): void
    {
        $fixtures = self::runDecoderFixtures();
        if ($fixtures['ok']) {
            return;
        }

        $failures = array_values(array_filter(
            $fixtures['results'],
            static fn (array $case): bool => !($case['ok'] ?? false)
        ));

        $messages = array_map(
            static fn (array $case): string => sprintf(
                'input=%s expected=%s actual=%s',
                (string) $case['input'],
                (string) $case['expected'],
                (string) $case['actual']
            ),
            $failures
        );

        throw new \RuntimeException('Vietnamese decoder fixtures failed: ' . implode(' | ', $messages));
    }

    public static function decodeArchiveMojibake(string $value): string
    {
        if ($value === '' || !self::containsMojibakeMarkers($value)) {
            return $value;
        }

        $best = $value;
        $bestScore = self::scoreTextQuality($value);
        $candidate = $value;

        for ($attempt = 0; $attempt < 4; $attempt += 1) {
            $decoded = self::decodeOnePass($candidate);
            if ($decoded === null || $decoded === '' || $decoded === $candidate) {
                break;
            }

            $decoded = self::normalizeDecodedText($decoded);
            $score = self::scoreTextQuality($decoded);

            if ($score > $bestScore) {
                $best = $decoded;
                $bestScore = $score;
            }

            $candidate = $decoded;
            if (!self::containsMojibakeMarkers($candidate)) {
                break;
            }
        }

        return $best;
    }

    public static function containsLiteralQuestionMark(?string $value): bool
    {
        return is_string($value) && str_contains($value, '?');
    }

    public static function containsMojibakeMarkers(?string $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        foreach (self::MOJIBAKE_PATTERNS as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function detectCorruptionKind(?string $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        if (self::containsLiteralQuestionMark($value) && self::containsMojibakeMarkers($value)) {
            return 'literal_question_mark_and_mojibake';
        }

        if (self::containsLiteralQuestionMark($value)) {
            return 'literal_question_mark';
        }

        if (self::containsMojibakeMarkers($value)) {
            return 'mojibake';
        }

        return null;
    }

    /**
     * @return array{utf8_ok: bool, question_marks: int, mojibake_markers: int, status: string}
     */
    public static function inspectText(string $raw): array
    {
        $utf8Ok = mb_check_encoding($raw, 'UTF-8');
        $questionMarks = substr_count($raw, '?');
        $mojibakeMarkers = self::countMojibakeMarkers($raw);
        $status = 'clean_utf8';

        if (!$utf8Ok) {
            $status = 'unsafe_for_reseed';
        } elseif ($questionMarks > 0 || $mojibakeMarkers > 0) {
            $status = 'utf8_but_text_corrupted';
        }

        return [
            'utf8_ok' => $utf8Ok,
            'question_marks' => $questionMarks,
            'mojibake_markers' => $mojibakeMarkers,
            'status' => $status,
        ];
    }

    /**
     * @return array{path: string, raw: string}
     */
    public static function readArchiveEntry(string $archivePath, array $candidates): array
    {
        if (!is_file($archivePath)) {
            throw new \RuntimeException("Archive not found: {$archivePath}");
        }

        foreach ($candidates as $candidate) {
            $command = sprintf(
                'tar -xOzf %s %s',
                escapeshellarg($archivePath),
                escapeshellarg($candidate)
            );
            $result = self::runCommand($command, self::repoRoot());
            if (($result['exit_code'] ?? 1) === 0 && ($result['stdout'] ?? '') !== '') {
                return [
                    'path' => $candidate,
                    'raw' => (string) $result['stdout'],
                ];
            }
        }

        throw new \RuntimeException(
            sprintf(
                'Cannot find archive entry in %s. Tried: %s',
                $archivePath,
                implode(', ', $candidates)
            )
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function parseInsertRows(string $sql, string $table, array $columns): array
    {
        $pattern = sprintf('/INSERT INTO\\s+`?%s`?\\s+VALUES\\s*(.+?);/is', preg_quote($table, '/'));
        if (!preg_match_all($pattern, $sql, $matches)) {
            return [];
        }

        $rows = [];
        foreach ($matches[1] as $valuesBlock) {
            foreach (self::splitSqlTuples((string) $valuesBlock) as $tuple) {
                $values = self::splitSqlTupleValues($tuple);
                if (count($values) !== count($columns)) {
                    continue;
                }

                $row = [];
                foreach ($columns as $index => $column) {
                    $row[$column] = self::parseSqlValue($values[$index]);
                }
                $rows[] = $row;
            }
        }

        return $rows;
    }

    public static function loadPrimaryKey(PDO $pdo, string $table): ?string
    {
        $stmt = $pdo->prepare(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
               AND COLUMN_KEY = 'PRI'
             ORDER BY ORDINAL_POSITION
             LIMIT 1"
        );
        $stmt->execute(['table' => $table]);
        $pk = $stmt->fetchColumn();

        return is_string($pk) && $pk !== '' ? $pk : null;
    }

    /**
     * @return string[]
     */
    public static function loadTableColumns(PDO $pdo, string $table): array
    {
        $stmt = $pdo->prepare(
            "SELECT COLUMN_NAME
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table
             ORDER BY ORDINAL_POSITION"
        );
        $stmt->execute(['table' => $table]);
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_values(array_filter($columns, static fn ($value): bool => is_string($value) && $value !== ''));
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadDatabaseConfig(): array
    {
        /** @var array<string, mixed> $config */
        $config = require base_path('config/database.php');
        return $config;
    }

    /**
     * @return array{pdo: PDO, connection: array<string, mixed>}
     */
    public static function connectPdoWithFallback(): array
    {
        $config = self::loadDatabaseConfig();
        $attempts = [[
            'label' => 'config',
            'host' => (string) ($config['host'] ?? '127.0.0.1'),
            'port' => (int) ($config['port'] ?? 3306),
        ]];

        if (($config['host'] ?? '') === 'mysql') {
            $dockerIp = self::detectDockerMysqlIp();
            if ($dockerIp !== null) {
                $attempts[] = [
                    'label' => 'docker_container_ip',
                    'host' => $dockerIp,
                    'port' => (int) ($config['port'] ?? 3306),
                ];
            }
        }

        $lastException = null;
        foreach ($attempts as $attempt) {
            try {
                $dsn = sprintf(
                    '%s:host=%s;port=%d;dbname=%s;charset=%s',
                    (string) ($config['driver'] ?? 'mysql'),
                    $attempt['host'],
                    $attempt['port'],
                    (string) ($config['database'] ?? 'hrm_db'),
                    (string) ($config['charset'] ?? 'utf8mb4')
                );

                $pdo = new PDO(
                    $dsn,
                    (string) ($config['username'] ?? ''),
                    (string) ($config['password'] ?? ''),
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                $pdo->exec(sprintf(
                    "SET NAMES '%s' COLLATE '%s'",
                    (string) ($config['charset'] ?? 'utf8mb4'),
                    (string) ($config['collation'] ?? 'utf8mb4_unicode_ci')
                ));
                $pdo->exec("SET CHARACTER SET utf8mb4");
                $pdo->exec("SET character_set_connection=utf8mb4");

                return [
                    'pdo' => $pdo,
                    'connection' => $attempt,
                ];
            } catch (\Throwable $exception) {
                $lastException = $exception;
            }
        }

        throw new \RuntimeException(
            'Cannot connect to MySQL for Vietnamese recovery: ' . ($lastException?->getMessage() ?? 'unknown error')
        );
    }

    /**
     * @return array{exit_code: int, stdout: string, stderr: string}
     */
    public static function runCommand(string $command, ?string $cwd = null): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $cwd ?? self::repoRoot());
        if (!is_resource($process)) {
            throw new \RuntimeException('Cannot start command: ' . $command);
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exit_code' => is_int($exitCode) ? $exitCode : 1,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    public static function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException('Cannot create directory: ' . $path);
        }
    }

    public static function writeJson(string $path, mixed $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            throw new \RuntimeException('Cannot encode JSON for ' . $path);
        }

        if (file_put_contents($path, $json . PHP_EOL) === false) {
            throw new \RuntimeException('Cannot write JSON file: ' . $path);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public static function writeCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open CSV file for writing: ' . $path);
        }

        if ($rows === []) {
            fclose($handle);
            return;
        }

        $headers = array_keys($rows[0]);
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $values = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? null;
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                } elseif ($value === null) {
                    $value = '';
                } elseif (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $values[] = $value;
            }
            fputcsv($handle, $values);
        }

        fclose($handle);
    }

    public static function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private static function decodeOnePass(string $value): ?string
    {
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($chars)) {
            return null;
        }

        $bytes = '';
        foreach ($chars as $char) {
            $code = mb_ord($char, 'UTF-8');
            if ($code <= 0xff) {
                $bytes .= chr($code);
                continue;
            }

            $mapped = self::CP1252_UNICODE_TO_BYTE[$code] ?? null;
            if ($mapped === null) {
                return null;
            }
            $bytes .= chr($mapped);
        }

        if (!mb_check_encoding($bytes, 'UTF-8')) {
            return null;
        }

        return $bytes;
    }

    private static function normalizeDecodedText(string $value): string
    {
        $value = str_replace("\u{00A0}", ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private static function scoreTextQuality(string $value): int
    {
        $vietnameseCount = preg_match_all(self::VIETNAMESE_RE, $value);
        $replacementChars = substr_count($value, '�');
        $questionMarks = substr_count($value, '?');
        $mojibakeMarkers = self::countMojibakeMarkers($value);

        return ((int) $vietnameseCount * 5)
            - ($replacementChars * 25)
            - ($questionMarks * 15)
            - ($mojibakeMarkers * 7);
    }

    private static function countMojibakeMarkers(string $value): int
    {
        $count = 0;
        foreach (self::MOJIBAKE_PATTERNS as $pattern) {
            $matches = preg_match_all($pattern, $value, $buffer);
            if ($matches !== false) {
                $count += $matches;
            }
        }

        return $count;
    }

    /**
     * @return string[]
     */
    private static function splitSqlTuples(string $valuesBlock): array
    {
        $tuples = [];
        $depth = 0;
        $buffer = '';
        $inQuote = false;
        $escaped = false;

        $length = strlen($valuesBlock);
        for ($i = 0; $i < $length; $i += 1) {
            $char = $valuesBlock[$i];

            if ($escaped) {
                $buffer .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $buffer .= $char;
                $escaped = true;
                continue;
            }

            if ($char === "'") {
                $buffer .= $char;
                $inQuote = !$inQuote;
                continue;
            }

            if (!$inQuote && $char === '(') {
                if ($depth > 0) {
                    $buffer .= $char;
                }
                $depth++;
                continue;
            }

            if (!$inQuote && $char === ')') {
                $depth--;
                if ($depth === 0) {
                    $tuples[] = $buffer;
                    $buffer = '';
                    continue;
                }

                $buffer .= $char;
                continue;
            }

            if ($depth > 0) {
                $buffer .= $char;
            }
        }

        return $tuples;
    }

    /**
     * @return string[]
     */
    private static function splitSqlTupleValues(string $tuple): array
    {
        $values = [];
        $buffer = '';
        $inQuote = false;
        $escaped = false;
        $length = strlen($tuple);

        for ($i = 0; $i < $length; $i += 1) {
            $char = $tuple[$i];

            if ($escaped) {
                $buffer .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $buffer .= $char;
                $escaped = true;
                continue;
            }

            if ($char === "'") {
                $buffer .= $char;
                $inQuote = !$inQuote;
                continue;
            }

            if (!$inQuote && $char === ',') {
                $values[] = trim($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $values[] = trim($buffer);

        return $values;
    }

    private static function parseSqlValue(string $token): mixed
    {
        if (strcasecmp($token, 'NULL') === 0) {
            return null;
        }

        if (strlen($token) >= 2 && $token[0] === "'" && $token[strlen($token) - 1] === "'") {
            $inner = substr($token, 1, -1);
            $inner = str_replace(["\\'", '\\"', '\\\\', '\\n', '\\r', '\\t'], ["'", '"', '\\', "\n", "\r", "\t"], $inner);
            return $inner;
        }

        if (is_numeric($token)) {
            return str_contains($token, '.') ? (float) $token : (int) $token;
        }

        return $token;
    }

    private static function detectDockerMysqlIp(): ?string
    {
        $commands = [
            'docker inspect -f "{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}" hrm-mysql',
            'docker inspect -f "{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}" $(docker compose ps -q mysql)',
        ];

        foreach ($commands as $command) {
            $result = self::runCommand($command, self::repoRoot());
            $output = trim((string) ($result['stdout'] ?? ''));
            if (($result['exit_code'] ?? 1) === 0 && $output !== '') {
                return $output;
            }
        }

        return null;
    }
}
