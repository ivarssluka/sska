<?php
declare(strict_types=1);

$scriptDir = __DIR__;

require $scriptDir . '/index.php';

file_put_contents(
    $scriptDir . '/cron_log.txt', 
    date('Y-m-d H:i:s') . " - Cron job executed\n", 
    FILE_APPEND
);