<?php
/**
 * API: Add Inventory Item
 * POST (multipart for image) — returns JSON
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

$itemName    = sanitize($_POST['item_name'] ?? '');
$categoryId  = (int)($_POST['category_id'] ?? 0);
$price       = (float)($_POST['price'] ?? 0);
$stockCount  = (int)($_POST['stock_count'] ?? 0);
$maxOrderQty = (int)($_POST['max_order_qty'] ?? 1);

if (empty($itemName) || $price <= 0) {
    json_response(['success' => false, 'message' => 'Item name and valid price are required.'], 400);
}

$imagePath = null;

// Handle image upload
if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['item_image'];
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        json_response(['success' => false, 'message' => 'Only JPG/PNG images allowed.'], 400);
    }

    $ext = ($mime === 'image/png') ? 'png' : 'jpg';
    $filename = uniqid('item_') . '.' . $ext;
    $uploadDir = BASE_PATH . 'uploads/inventory/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        $imagePath = 'uploads/inventory/' . $filename;
    }
}

try {
    $stmt = $pdo->prepare(
        "INSERT INTO inventory (item_name, category_id, price, stock_count, max_order_qty, item_image)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $itemName,
        $categoryId ?: null,
        $price,
        max(0, $stockCount),
        max(1, $maxOrderQty),
        $imagePath
    ]);

    log_info('Inventory item added', ['item' => $itemName], current_user_id());
    json_response(['success' => true, 'message' => 'Item added.', 'id' => $pdo->lastInsertId()]);

} catch (Exception $e) {
    log_error('Add item error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'Failed to add item.'], 500);
}
