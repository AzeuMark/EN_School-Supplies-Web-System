<?php
/**
 * API: Approve Pending User (Admin or Staff)
 * POST — { user_id }
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/logger.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$role = current_user_role();
if ($role !== 'admin' && $role !== 'staff') {
    json_response(['success' => false, 'message' => 'Access denied.'], 403);
}

$input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId = (int)($input['user_id'] ?? 0);

if (!$userId) {
    json_response(['success' => false, 'message' => 'Invalid user.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['success' => false, 'message' => 'User not found or already approved.'], 404);
    }

    $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$userId]);

    log_info('User approved', ['user_id' => $userId, 'email' => $user['email']], current_user_id());
    json_response(['success' => true, 'message' => 'User approved.']);

} catch (Exception $e) {
    log_error('Approve user error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'An error occurred.'], 500);
}
