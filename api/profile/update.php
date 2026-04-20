<?php
/**
 * API: Update Profile
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

if (!is_logged_in()) {
    json_response(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$input  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId = current_user_id();

// Handle theme preference update (quick update, no CSRF needed for this simple toggle)
if (isset($input['theme_preference']) && count($input) === 1) {
    $theme = $input['theme_preference'];
    if (in_array($theme, ['light', 'dark', 'auto'])) {
        $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
        $stmt->execute([$theme, $userId]);
        json_response(['success' => true, 'message' => 'Theme updated.']);
    }
    json_response(['success' => false, 'message' => 'Invalid theme.'], 400);
}

// Full profile update — requires CSRF
if (!csrf_validate()) {
    json_response(['success' => false, 'message' => 'Invalid request.'], 403);
}

$fullName = sanitize($input['full_name'] ?? '');
$email    = sanitize($input['email'] ?? '');
$phone    = sanitize($input['phone'] ?? '');
$password = $input['password'] ?? '';

if (empty($fullName) || empty($email)) {
    json_response(['success' => false, 'message' => 'Name and email are required.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Invalid email address.'], 400);
}

try {
    // Check duplicate email (exclude self)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        json_response(['success' => false, 'message' => 'Email already in use.'], 409);
    }

    if (!empty($password)) {
        if (strlen($password) < 4) {
            json_response(['success' => false, 'message' => 'Password must be at least 4 characters.'], 400);
        }
        $encPassword = aes_encrypt($password);
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $phone, $encPassword, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $email, $phone, $userId]);
    }

    $_SESSION['user_name'] = $fullName;

    log_info('Profile updated', ['user_id' => $userId], $userId);
    json_response(['success' => true, 'message' => 'Profile updated successfully.']);

} catch (Exception $e) {
    log_error('Profile update error', ['error' => $e->getMessage()], $userId);
    json_response(['success' => false, 'message' => 'An error occurred.'], 500);
}
