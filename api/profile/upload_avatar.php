<?php
/**
 * API: Upload Profile Avatar
 * POST (multipart/form-data) — returns JSON
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

if (!csrf_validate()) {
    json_response(['success' => false, 'message' => 'Invalid request.'], 403);
}

$userId = current_user_id();
$role   = current_user_role();

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'message' => 'No file uploaded or upload error.'], 400);
}

$file = $_FILES['avatar'];
$maxSize = 1 * 1024 * 1024; // 1MB

if ($file['size'] > $maxSize) {
    json_response(['success' => false, 'message' => 'File too large. Maximum size is 1MB.'], 400);
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    json_response(['success' => false, 'message' => 'Only JPG and PNG files are allowed.'], 400);
}

try {
    $uploadDir = BASE_PATH . "uploads/{$role}/profiles/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = $userId . '.jpg';
    $filepath = $uploadDir . $filename;
    $relativePath = "uploads/{$role}/profiles/{$filename}";

    // Delete old file if exists
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        json_response(['success' => false, 'message' => 'Failed to save file.'], 500);
    }

    // Update DB
    $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    $stmt->execute([$relativePath, $userId]);

    log_info('Avatar uploaded', ['user_id' => $userId, 'path' => $relativePath], $userId);

    json_response([
        'success' => true,
        'message' => 'Avatar updated.',
        'path'    => $relativePath
    ]);

} catch (Exception $e) {
    log_error('Avatar upload error', ['error' => $e->getMessage()], $userId);
    json_response(['success' => false, 'message' => 'An error occurred.'], 500);
}
