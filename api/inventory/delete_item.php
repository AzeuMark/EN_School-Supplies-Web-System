<?php
/**
 * API: Delete Inventory Item
 * POST — { item_id }
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
$itemId = (int)($input['item_id'] ?? 0);

if (!$itemId) {
    json_response(['success' => false, 'message' => 'Invalid item.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? LIMIT 1");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch();

    if (!$item) {
        json_response(['success' => false, 'message' => 'Item not found.'], 404);
    }

    // Delete image file
    if ($item['item_image'] && file_exists(BASE_PATH . $item['item_image'])) {
        unlink(BASE_PATH . $item['item_image']);
    }

    $pdo->prepare("DELETE FROM inventory WHERE id = ?")->execute([$itemId]);

    log_info('Inventory item deleted', ['item_id' => $itemId, 'name' => $item['item_name']], current_user_id());
    json_response(['success' => true, 'message' => 'Item deleted.']);

} catch (Exception $e) {
    log_error('Delete item error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'Failed to delete item.'], 500);
}
