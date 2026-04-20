<?php
/**
 * API: Login (AJAX endpoint)
 * POST — returns JSON
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/aes.php';
require_once __DIR__ . '/../../includes/logger.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$email    = sanitize($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    json_response(['success' => false, 'message' => 'Email and password are required.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        log_warning('API: Failed login — user not found', ['email' => $email]);
        json_response(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    if ($user['status'] === 'flagged') {
        $phone = get_setting('store_phone', 'the store');
        json_response(['success' => false, 'message' => "Your account has been flagged. Contact us at $phone or visit the store."], 403);
    }

    if ($user['status'] === 'pending') {
        json_response(['success' => false, 'message' => 'Your account is pending approval.'], 403);
    }

    $decrypted = aes_decrypt($user['password']);
    if ($decrypted !== $password) {
        log_warning('API: Failed login — wrong password', ['email' => $email]);
        json_response(['success' => false, 'message' => 'Invalid email or password.'], 401);
    }

    $sysStatus = get_setting('system_status', 'online');
    if ($user['role'] !== 'admin' && $sysStatus === 'offline') {
        json_response(['success' => false, 'message' => 'System is currently offline.'], 503);
    }

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = $user['full_name'];

    if ($user['role'] === 'staff') {
        $pdo->prepare("INSERT INTO staff_sessions (user_id, login_time) VALUES (?, NOW())")->execute([$user['id']]);
        $_SESSION['staff_session_id'] = $pdo->lastInsertId();
    }

    log_info('User logged in via API', ['user_id' => $user['id'], 'role' => $user['role']], $user['id']);

    json_response([
        'success'  => true,
        'message'  => 'Login successful.',
        'redirect' => $user['role'] . '/dashboard.php'
    ]);

} catch (Exception $e) {
    log_error('API login error', ['error' => $e->getMessage()]);
    json_response(['success' => false, 'message' => 'An error occurred.'], 500);
}
