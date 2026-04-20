<?php
/**
 * E&N School Supplies — Logout Handler (POST only, PRG)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

if (!csrf_validate()) {
    redirect('login.php');
}

$userId = current_user_id();
$role   = current_user_role();

// Close staff session
if ($role === 'staff' && !empty($_SESSION['staff_session_id'])) {
    try {
        $sessionId = $_SESSION['staff_session_id'];
        $stmt = $pdo->prepare(
            "UPDATE staff_sessions
             SET logout_time = NOW(),
                 logout_type = 'manual',
                 duration_minutes = TIMESTAMPDIFF(MINUTE, login_time, NOW())
             WHERE id = ? AND logout_time IS NULL"
        );
        $stmt->execute([$sessionId]);
    } catch (Exception $e) {
        // Silent fail
    }
}

if ($userId) {
    log_info('User logged out', ['user_id' => $userId, 'role' => $role], $userId);
}

// Destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Start new session for flash
session_start();
set_flash('success', 'You have been logged out.');
redirect('login.php');
