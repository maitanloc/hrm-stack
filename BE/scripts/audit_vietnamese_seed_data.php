<?php
declare(strict_types=1);

fwrite(
    STDERR,
    "[deprecated] Use BE/scripts/audit_vietnamese_data.php instead. Forwarding to the new system-wide audit." . PHP_EOL
);

require __DIR__ . '/audit_vietnamese_data.php';
