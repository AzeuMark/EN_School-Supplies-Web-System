<?php
/**
 * API: Delete User (Admin only)
 * POST — { user_id }
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/logger.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!is_logged_in() || current_user_role() !== 'admin') {
    json_response(['success' => false, 'message' => 'Access denied.'], 403);
}

$input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId = (int)($input['user_id'] ?? 0);

if (!$userId) {
    json_response(['success' => false, 'message' => 'Invalid user.'], 400);
}

// Prevent self-delete
if ($userId === current_user_id()) {
    json_response(['success' => false, 'message' => 'Cannot delete yourself.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(['success' => false, 'message' => 'User not found.'], 404);
    }

    // Delete avatar
    if ($user['profile_image'] && file_exists(BASE_PATH . $user['profile_image'])) {
        unlink(BASE_PATH . $user['profile_image']);
    }

    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);

    log_info('User deleted', ['user_id' => $userId, 'email' => $user['email']], current_user_id());
    json_response(['success' => true, 'message' => 'User deleted.']);

} catch (Exception $e) {
    log_error('Delete user error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'Failed to delete user.'], 500);
}
