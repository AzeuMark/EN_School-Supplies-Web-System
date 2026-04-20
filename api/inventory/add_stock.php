<?php
/**
 * API: Add Stock to Inventory Item
 * POST — { item_id, quantity }
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
$qty    = (int)($input['quantity'] ?? 0);

if (!$itemId || $qty <= 0) {
    json_response(['success' => false, 'message' => 'Invalid parameters.'], 400);
}

try {
    $stmt = $pdo->prepare("UPDATE inventory SET stock_count = stock_count + ? WHERE id = ?");
    $stmt->execute([$qty, $itemId]);

    if ($stmt->rowCount() === 0) {
        json_response(['success' => false, 'message' => 'Item not found.'], 404);
    }

    log_info('Stock added', ['item_id' => $itemId, 'quantity' => $qty], current_user_id());
    json_response(['success' => true, 'message' => "$qty units added to stock."]);

} catch (Exception $e) {
    log_error('Add stock error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'Failed to add stock.'], 500);
}
