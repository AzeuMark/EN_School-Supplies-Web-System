<?php
/**
 * API: Edit User (Admin only)
 * POST — { user_id, full_name, email, phone, role, password? }
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/aes.php';
require_once __DIR__ . '/../../includes/logger.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!is_logged_in() || current_user_role() !== 'admin') {
    json_response(['success' => false, 'message' => 'Access denied.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$userId   = (int)($input['user_id'] ?? 0);
$fullName = sanitize($input['full_name'] ?? '');
$email    = sanitize($input['email'] ?? '');
$phone    = sanitize($input['phone'] ?? '');
$role     = $input['role'] ?? '';
$password = $input['password'] ?? '';

if (!$userId || empty($fullName) || empty($email)) {
    json_response(['success' => false, 'message' => 'Invalid parameters.'], 400);
}

try {
    // Check duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Email already in use.'], 409);
    }

    if (!empty($password)) {
        $enc = aes_encrypt($password);
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, password=? WHERE id=?");
        $stmt->execute([$fullName, $email, $phone, $role ?: 'customer', $enc, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=? WHERE id=?");
        $stmt->execute([$fullName, $email, $phone, $role ?: 'customer', $userId]);
    }

    log_info('User edited by admin', ['user_id' => $userId], current_user_id());
    json_response(['success' => true, 'message' => 'User updated.']);

} catch (Exception $e) {
    log_error('Edit user error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'Failed to update user.'], 500);
}
