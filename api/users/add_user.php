<?php
/**
 * API: Add User (Admin only)
 * POST — { full_name, email, phone, password, role }
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

$fullName = sanitize($input['full_name'] ?? '');
$email    = sanitize($input['email'] ?? '');
$phone    = sanitize($input['phone'] ?? '');
$password = $input['password'] ?? '';
$role     = $input['role'] ?? '';

if (empty($fullName) || empty($email) || empty($password)) {
    json_response(['success' => false, 'message' => 'Name, email, and password are required.'], 400);
}

if (!in_array($role, ['staff', 'customer'])) {
    json_response(['success' => false, 'message' => 'Invalid role.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Email already exists.'], 409);
    }

    $encPassword = aes_encrypt($password);
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, phone, password, role, status, created_by)
         VALUES (?, ?, ?, ?, ?, 'active', ?)"
    );
    $stmt->execute([$fullName, $email, $phone, $encPassword, $role, current_user_id()]);

    log_info('User created by admin', ['email' => $email, 'role' => $role], current_user_id());
    json_response(['success' => true, 'message' => 'User created (auto-approved).']);

} catch (Exception $e) {
    log_error('Add user error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'Failed to create user.'], 500);
}
