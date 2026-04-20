<?php
/**
 * API: Edit Inventory Item
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

$itemId      = (int)($_POST['item_id'] ?? 0);
$itemName    = sanitize($_POST['item_name'] ?? '');
$categoryId  = (int)($_POST['category_id'] ?? 0);
$price       = (float)($_POST['price'] ?? 0);
$stockCount  = (int)($_POST['stock_count'] ?? 0);
$maxOrderQty = (int)($_POST['max_order_qty'] ?? 1);

if (!$itemId || empty($itemName) || $price <= 0) {
    json_response(['success' => false, 'message' => 'Invalid parameters.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? LIMIT 1");
    $stmt->execute([$itemId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        json_response(['success' => false, 'message' => 'Item not found.'], 404);
    }

    $imagePath = $existing['item_image'];

    // Handle new image upload
    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['item_image'];
        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (in_array($mime, $allowed)) {
            // Delete old image
            if ($existing['item_image'] && file_exists(BASE_PATH . $existing['item_image'])) {
                unlink(BASE_PATH . $existing['item_image']);
            }
            $ext = ($mime === 'image/png') ? 'png' : 'jpg';
            $filename = uniqid('item_') . '.' . $ext;
            $uploadDir = BASE_PATH . 'uploads/inventory/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $imagePath = 'uploads/inventory/' . $filename;
            }
        }
    }

    $stmt = $pdo->prepare(
        "UPDATE inventory SET item_name = ?, category_id = ?, price = ?, stock_count = ?, max_order_qty = ?, item_image = ?
         WHERE id = ?"
    );
    $stmt->execute([
        $itemName,
        $categoryId ?: null,
        $price,
        max(0, $stockCount),
        max(1, $maxOrderQty),
        $imagePath,
        $itemId
    ]);

    log_info('Inventory item edited', ['item_id' => $itemId], current_user_id());
    json_response(['success' => true, 'message' => 'Item updated.']);

} catch (Exception $e) {
    log_error('Edit item error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'Failed to update item.'], 500);
}
