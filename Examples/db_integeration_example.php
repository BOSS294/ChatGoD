<?php
require_once __DIR__ . '/Connectors/connector.php';

use function ChatGoD\Connector\log_event;
use function ChatGoD\Connector\db_connect;

try {
    $pdo = db_connect(); // test connection
} catch (\Exception $e) {
    // fatal connect error — write local file
    file_put_contents(__DIR__ . '/logs/connector-fatal.log', $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
    exit("DB connection failed");
}

// Log a normal info
log_event('INFO', 'Processor started', ['script' => 'processor.php']);

// Log an error and force alert
try {
    // some risky code...
    throw new \Exception("Example failure");
} catch (\Throwable $err) {
    log_event('ERROR', $err->getMessage(), ['exception' => (string)$err], true);
}
?>