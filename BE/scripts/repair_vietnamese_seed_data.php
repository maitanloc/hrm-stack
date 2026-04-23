<?php
declare(strict_types=1);

fwrite(
    STDERR,
    "[deprecated] Use BE/scripts/repair_vietnamese_data.php instead. Forwarding to the new recovery workflow." . PHP_EOL
);

require __DIR__ . '/repair_vietnamese_data.php';
