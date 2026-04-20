<?php
echo "=== Environment Variable Check ===\n\n";

echo "Checking all DB_* variables:\n";
$env_vars = explode(' ', 'DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD DB_DRIVER DB_CHARSET DB_COLLATION');
foreach ($env_vars as $var) {
    $getenv_val = getenv($var);
    $env_val = $_ENV[$var] ?? 'NOT SET';
    $server_val = $_SERVER[$var] ?? 'NOT SET';
    
    echo "\n$var:\n";
    echo "  getenv(): " . ($getenv_val !== false ? $getenv_val : 'NOT SET') . "\n";
    echo "  \$_ENV: " . $env_val . "\n";
    echo "  \$_SERVER: " . $server_val . "\n";
}

echo "\n\n=== Checking be.env file ===\n";
$be_env_path = '/var/www/html/be.env';
if (file_exists($be_env_path)) {
    echo "File exists: YES\n";
    echo "File readable: " . (is_readable($be_env_path) ? 'YES' : 'NO') . "\n";
    echo "File size: " . filesize($be_env_path) . " bytes\n";
    
    echo "\nFirst 10 lines:\n";
    $lines = file($be_env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $count = 0;
    foreach ($lines as $line) {
        if ($count < 10) {
            echo "  " . $line . "\n";
            $count++;
        }
    }
} else {
    echo "File exists: NO\n";
}

echo "\n\n=== Current Working Directory ===\n";
echo "cwd: " . getcwd() . "\n";

echo "\n\n=== Check .env and be.env in cwd ===\n";
foreach (['.env', 'be.env'] as $file) {
    $path = getcwd() . '/' . $file;
    echo "$file: " . (file_exists($path) ? "EXISTS" : "NOT FOUND") . "\n";
}
?>
