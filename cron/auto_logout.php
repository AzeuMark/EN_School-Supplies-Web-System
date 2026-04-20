<?php
/**
 * Cron: Auto-Logout Stale Staff Sessions
 *
 * Run every 15 minutes via system scheduler or manually:
 *   php cron/auto_logout.php
 *
 * Marks sessions exceeding the auto_logout_hours threshold as suspicious.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/logger.php';

$threshold = (int)get_setting('auto_logout_hours', '8');

try {
    $stmt = $pdo->prepare(
        "SELECT ss.id, ss.user_id, ss.login_time
         FROM staff_sessions ss
         WHERE ss.logout_time IS NULL
           AND ss.login_time < DATE_SUB(NOW(), INTERVAL ? HOUR)"
    );
    $stmt->execute([$threshold]);
    $stale = $stmt->fetchAll();

    $closed = 0;
    foreach ($stale as $session) {
        $pdo->prepare(
            "UPDATE staff_sessions
             SET logout_time = NOW(),
                 logout_type = 'system',
                 is_suspicious = 1,
                 duration_minutes = TIMESTAMPDIFF(MINUTE, login_time, NOW())
             WHERE id = ?"
        )->execute([$session['id']]);
        $closed++;
    }

    if ($closed > 0) {
        log_info("Auto-logout: closed $closed stale staff sessions", ['threshold_hours' => $threshold]);
    }

    echo "Auto-logout complete. Closed $closed sessions.\n";

} catch (Exception $e) {
    log_error('Auto-logout cron error', ['error' => $e->getMessage()]);
    echo "Error: " . $e->getMessage() . "\n";
}
