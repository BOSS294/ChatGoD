<?php
/**
 * Connectors/connector.php
 *
 * ChatGoD Connector — Secure DB connector & advanced logger (email removed)
 *
 * ™ ChatGoD — All rights reserved.
 * Author: Mayank Chawdhari / Team
 * Version: 1.1
 *
 * Purpose:
 *  - Provide a secure PDO-based DB connection using credentials from Modules/secrets.env.
 *  - Provide an advanced logger that writes to the DB logs table and an on-disk log file,
 *    collects IP/user-agent/URL/method, captures backtrace/stack, and can send alerts via webhook.
 *
 * NOTE: Email alerts have been removed per project requirements. Alerting is done only via webhook
 *       (ALERT_WEBHOOK_URL in Modules/secrets.env). If no webhook is set, alerts will be skipped.
 *
 * Security recommendations:
 *  - Place Modules/secrets.env outside public webroot or ensure webserver denies direct access.
 *  - Set file permissions (chmod 600) to limit access.
 *  - Use an application account with minimal DB privileges (INSERT/SELECT/UPDATE on logs and required tables).
 *
 * Usage:
 *  require_once __DIR__ . '/connector.php';
 *  $pdo = db_connect(); // returns PDO
 *  log_event('ERROR', 'Something failed', ['exception' => $e], true);
 *
 */

declare(strict_types=1);

namespace ChatGoD\Connector;

use \PDO;
use \PDOException;

// ----------------------------- Configuration / env loader ----------------------------- //

/**
 * Load a simple .env file (KEY=VALUE lines). Comments (#) and empty lines are ignored.
 * Returns associative array.
 */
function load_env(string $path): array {
    $env = [];
    if (!is_readable($path)) return $env;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $eqPos = strpos($line, '=');
        if ($eqPos === false) continue;
        $key = trim(substr($line, 0, $eqPos));
        $val = trim(substr($line, $eqPos + 1));
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
            $val = substr($val, 1, -1);
        }
        $env[$key] = $val;
    }
    return $env;
}

/**
 * Convenience accessor to environment from Modules/secrets.env near this file.
 */
function env(string $key, $default = null) {
    static $loaded = null;
    if ($loaded === null) {
        $envPath = __DIR__ . '/../Modules/secrets.env';
        $loaded = load_env($envPath);
    }
    return array_key_exists($key, $loaded) ? $loaded[$key] : $default;
}

// ----------------------------- DB connection (PDO) ----------------------------- //

/**
 * Create/return a singleton PDO connection using credentials from env.
 * Throws PDOException on failure.
 *
 * @return PDO
 * @throws PDOException
 */
function db_connect(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = env('DB_HOST', '127.0.0.1');
    $port = env('DB_PORT', '3306');
    $db   = env('DB_NAME', '');
    $user = env('DB_USER', '');
    $pass = env('DB_PASS', '');
    $charset = env('DB_CHARSET', 'utf8mb4');

    if (!$db) {
        throw new PDOException("DB_NAME not configured in env");
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
    ];

    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

// ----------------------------- Utility helpers ----------------------------- //

/** Generate a v4 UUID (string) */
function uuid_v4(): string {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/** Get client IP (handles common proxy headers but do NOT trust blindly) */
function client_ip(): string {
    $headers = [
        'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    foreach ($headers as $h) {
        if (!empty($_SERVER[$h])) {
            $parts = explode(',', $_SERVER[$h]);
            $ip = trim($parts[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/** Capture a plain-text stack trace (safe for logs) */
function capture_stack_trace(int $offset = 0): string {
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $out = [];
    for ($i = $offset + 1; $i < count($bt); $i++) {
        $frame = $bt[$i];
        $file = $frame['file'] ?? '(internal)';
        $line = $frame['line'] ?? 0;
        $func = $frame['function'] ?? '';
        $class = $frame['class'] ?? '';
        $out[] = sprintf('%s%s() — %s:%d', $class ? $class . '::' : '', $func, $file, $line);
        if (count($out) > 50) break;
    }
    return implode("\n", $out);
}

// ----------------------------- Webhook Alerting helper (no email) ----------------------------- //

/**
 * send_alert_via_webhook
 *
 * Send alert only via webhook (ALERT_WEBHOOK_URL). Returns true on 2xx HTTP response.
 */
function send_alert_via_webhook(string $subject, string $body, array $context = []): bool {
    $webhook = env('ALERT_WEBHOOK_URL', '');
    if (!$webhook) return false;

    $payload = [
        'subject' => $subject,
        'body'    => $body,
        'context' => $context,
        'timestamp'=> gmdate('c'),
    ];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    try {
        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode >= 200 && $httpCode < 300);
    } catch (\Throwable $e) {
        return false;
    }
}

// ----------------------------- Advanced Logger ----------------------------- //

/**
 * log_event
 *
 * Writes an event to DB `logs` table and appends to a file.
 *
 * @param string $level  e.g. DEBUG, INFO, WARNING, ERROR, CRITICAL
 * @param string $message short textual message
 * @param array  $context optional context (will be JSON-encoded)
 * @param bool   $forceAlert if true, attempt to send alert immediately (respect ALERT_LEVELS)
 *
 * Returns associative array with DB insert id and file write status.
 */
function log_event(string $level, string $message, array $context = [], bool $forceAlert = false): array {
    $result = ['db' => false, 'file' => false, 'alert' => false, 'id' => null];

    // gather context
    $ip = client_ip();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $url = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http'))
           . '://' . ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '')) . ($_SERVER['REQUEST_URI'] ?? '');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
    $referer = $_SERVER['HTTP_REFERER'] ?? null;
    $serverName = $_SERVER['SERVER_NAME'] ?? null;

    $shortMessage = mb_substr($message, 0, 2000);
    $logId = uuid_v4();
    $timestamp = date('Y-m-d H:i:s');
    $safeContext = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stack = capture_stack_trace(1);

    // DB insert (best effort)
    try {
        $pdo = db_connect();
        $sql = "INSERT INTO `logs` (
            `LOG_ID`,`LOG_TIMESTAMP`,`LOG_LEVEL`,`LOG_MESSAGE`,`LOG_CONTEXT`,
            `CLIENT_IP`,`USER_AGENT`,`REQUEST_URL`,`HTTP_METHOD`,`REFERER`,`SERVER_NAME`,
            `STACK_TRACE`,`ALERT_SENT`
        ) VALUES (
            :logid, :ts, :lvl, :msg, :ctx, :ip, :ua, :url, :method, :referer, :server, :stack, :alert_sent
        )";
        $stmt = $pdo->prepare($sql);
        $alertSentFlag = 0;
        $stmt->execute([
            ':logid' => $logId,
            ':ts' => $timestamp,
            ':lvl' => $level,
            ':msg' => $shortMessage,
            ':ctx' => $safeContext,
            ':ip' => $ip,
            ':ua' => $ua,
            ':url' => $url,
            ':method' => $method,
            ':referer' => $referer,
            ':server' => $serverName,
            ':stack' => $stack,
            ':alert_sent' => $alertSentFlag
        ]);
        $result['db'] = true;
        $result['id'] = $logId;
    } catch (PDOException $e) {
        $result['db'] = false;
    } catch (\Throwable $t) {
        $result['db'] = false;
    }

    // File logging (append)
    try {
        $logFile = env('LOG_FILE_PATH', __DIR__ . '/../logs/app.log');
        if (!str_starts_with($logFile, '/') && !preg_match('/^[A-Za-z]:\\\\/', $logFile)) {
            $logFile = realpath(__DIR__ . '/../') . '/' . ltrim($logFile, '/');
        }
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0750, true);
        }
        $entry = [
            'id' => $logId,
            'ts' => $timestamp,
            'level' => $level,
            'msg' => $message,
            'ctx' => $context,
            'ip' => $ip,
            'ua' => $ua,
            'url' => $url,
            'method' => $method
        ];
        $line = '[' . $timestamp . '] ' . strtoupper($level) . ' ' . $logId . ' ' . $message . ' | ' . json_encode($entry, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $written = @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
        if ($written !== false) $result['file'] = true;

        $maxBytes = (int)env('LOG_FILE_MAX_BYTES', 5242880);
        if (file_exists($logFile) && filesize($logFile) > $maxBytes) {
            $rotName = $logFile . '.' . date('YmdHis');
            @rename($logFile, $rotName);
        }
    } catch (\Throwable $t) {
        $result['file'] = false;
    }

    // Auto-alert logic: trigger if forced OR level listed in ALERT_LEVELS env
    $alertLevelsStr = env('ALERT_LEVELS', '');
    $alertLevels = array_filter(array_map('trim', explode(',', $alertLevelsStr)));
    $shouldAlert = $forceAlert || in_array(strtoupper($level), array_map('strtoupper', $alertLevels), true);

    if ($shouldAlert) {
        $subject = "[ChatGoD] {$level}: " . mb_substr($message, 0, 140);
        $body = "Timestamp: {$timestamp}\nLevel: {$level}\nMessage: {$message}\nID: {$logId}\nIP: {$ip}\nURL: {$url}\n\nContext:\n" . $safeContext . "\n\nStack:\n" . $stack;
        $sent = send_alert_via_webhook($subject, $body, ['id' => $logId, 'level' => $level, 'ip' => $ip]);
        $result['alert'] = (bool)$sent;
        if ($sent && $result['db']) {
            try {
                $pdo = db_connect();
                $u = $pdo->prepare("UPDATE `logs` SET `ALERT_SENT` = 1, `ALERT_CHANNEL` = :channel WHERE `LOG_ID` = :id");
                $channel = env('ALERT_WEBHOOK_URL') ? 'webhook' : null;
                $u->execute([':channel' => $channel, ':id' => $logId]);
            } catch (\Throwable $t) {
                // ignore
            }
        }
    }

    return $result;
}


?>
