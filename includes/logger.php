<?php
/**
 * Logging helpers.
 * Writes to both logs/system.log file AND system_logs DB table.
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

/**
 * Core log writer.
 *
 * @param string      $level   'info' | 'warning' | 'error'
 * @param string      $message Human-readable message
 * @param array|null  $context Extra data (stored as JSON)
 * @param int|null    $userId  ID of the user who triggered the event
 */
function write_log(string $level, string $message, ?array $context = null, ?int $userId = null): void {
    global $pdo;

    // ── File log ──
    $logDir  = BASE_PATH . 'logs';
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'system.log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp  = date('Y-m-d H:i:s');
    $ip         = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $contextStr = $context ? json_encode($context) : '';
    $line       = sprintf("[%s] [%s] [IP:%s] [User:%s] %s %s\n",
        $timestamp,
        strtoupper($level),
        $ip,
        $userId ?? 'N/A',
        $message,
        $contextStr
    );

    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

    // ── Database log ──
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO system_logs (level, message, context, user_id, ip_address)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $level,
            $message,
            $context ? json_encode($context) : null,
            $userId,
            $ip,
        ]);
    } catch (Exception $e) {
        // If DB insert fails, we already have the file log
    }
}

function log_info(string $message, ?array $context = null, ?int $userId = null): void {
    write_log('info', $message, $context, $userId);
}

function log_warning(string $message, ?array $context = null, ?int $userId = null): void {
    write_log('warning', $message, $context, $userId);
}

function log_error(string $message, ?array $context = null, ?int $userId = null): void {
    write_log('error', $message, $context, $userId);
}
