<?php
/**
 * API: Register (AJAX endpoint)
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

$fullName = sanitize($input['full_name'] ?? '');
$email    = sanitize($input['email'] ?? '');
$phone    = sanitize($input['phone'] ?? '');
$password = $input['password'] ?? '';
$confirm  = $input['confirm_password'] ?? '';

if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
    json_response(['success' => false, 'message' => 'All fields are required.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email address.'], 400);
}

if (strlen($password) < 4) {
    json_response(['success' => false, 'message' => 'Password must be at least 4 characters.'], 400);
}

if ($password !== $confirm) {
    json_response(['success' => false, 'message' => 'Passwords do not match.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Email already registered.'], 409);
    }

    $encPassword = aes_encrypt($password);
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, phone, password, role, status)
         VALUES (?, ?, ?, ?, 'customer', 'pending')"
    );
    $stmt->execute([$fullName, $email, $phone, $encPassword]);

    log_info('New customer registration via API (pending)', ['email' => $email]);

    json_response(['success' => true, 'message' => 'Registration successful! Your account is pending approval.']);

} catch (Exception $e) {
    log_error('API registration error', ['error' => $e->getMessage()]);
    json_response(['success' => false, 'message' => 'An error occurred.'], 500);
}
