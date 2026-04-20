<?php
/**
 * API: Update System Settings
 * POST — { settings: { key: value, ... } }
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

// Handle both JSON and form-data (for logo upload)
$isFormData = isset($_FILES['logo']);
$settings = [];

if ($isFormData) {
    // Logo upload
    if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            json_response(['success' => false, 'message' => 'Only JPG/PNG allowed for logo.'], 400);
        }

        $dest = BASE_PATH . 'assets/images/logo.png';
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            json_response(['success' => false, 'message' => 'Failed to save logo.'], 500);
        }

        set_setting('logo_path', 'assets/images/logo.png');
        log_info('System logo updated', null, current_user_id());
        json_response(['success' => true, 'message' => 'Logo updated.']);
    }
    json_response(['success' => false, 'message' => 'No logo file provided.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$settings = $input['settings'] ?? $input;

if (empty($settings) || !is_array($settings)) {
    json_response(['success' => false, 'message' => 'No settings provided.'], 400);
}

$allowed_keys = [
    'store_name', 'store_phone', 'store_email', 'timezone', 'force_dark_mode',
    'disable_nologin_orders', 'online_payment', 'system_status', 'auto_logout_hours'
];

try {
    $updated = 0;
    foreach ($settings as $key => $value) {
        if (in_array($key, $allowed_keys)) {
            set_setting($key, $value);
            $updated++;
        }
    }

    // Handle categories and default names if provided
    if (isset($input['categories']) && is_array($input['categories'])) {
        $pdo->exec("DELETE FROM item_categories");
        $stmt = $pdo->prepare("INSERT INTO item_categories (category_name) VALUES (?)");
        foreach ($input['categories'] as $cat) {
            $cat = trim($cat);
            if ($cat) $stmt->execute([$cat]);
        }
    }

    if (isset($input['default_names']) && is_array($input['default_names'])) {
        $pdo->exec("DELETE FROM default_item_names");
        $stmt = $pdo->prepare("INSERT INTO default_item_names (item_name) VALUES (?)");
        foreach ($input['default_names'] as $name) {
            $name = trim($name);
            if ($name) $stmt->execute([$name]);
        }
    }

    log_info('System settings updated', ['keys' => array_keys($settings)], current_user_id());
    json_response(['success' => true, 'message' => "Settings saved ($updated updated)."]);

} catch (Exception $e) {
    log_error('Settings update error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'Failed to save settings.'], 500);
}
