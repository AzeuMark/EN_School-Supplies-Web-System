<?php
/**
 * API: Flag / Unflag User (Admin only)
 * POST — { user_id, action: 'flag'|'unflag', reason? }
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
$action = $input['action'] ?? '';
$reason = sanitize($input['reason'] ?? '');

if (!$userId || !in_array($action, ['flag', 'unflag'])) {
    json_response(['success' => false, 'message' => 'Invalid parameters.'], 400);
}

if ($userId === current_user_id()) {
    json_response(['success' => false, 'message' => 'Cannot flag yourself.'], 400);
}

try {
    if ($action === 'flag') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'flagged', flag_reason = ? WHERE id = ? AND role != 'admin'");
        $stmt->execute([$reason ?: null, $userId]);
        log_info('User flagged', ['user_id' => $userId, 'reason' => $reason], current_user_id());
        json_response(['success' => true, 'message' => 'User flagged.']);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active', flag_reason = NULL WHERE id = ?");
        $stmt->execute([$userId]);
        log_info('User unflagged', ['user_id' => $userId], current_user_id());
        json_response(['success' => true, 'message' => 'Flag removed. User is now active.']);
    }
} catch (Exception $e) {
    log_error('Flag user error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'An error occurred.'], 500);
}
