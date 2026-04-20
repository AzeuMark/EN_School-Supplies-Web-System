<?php
/**
 * API: Cancel Order (by customer)
 * POST — { order_id }
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

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$orderId = (int)($input['order_id'] ?? 0);
$userId  = current_user_id();
$role    = current_user_role();

if (!$orderId) {
    json_response(['success' => false, 'message' => 'Invalid order.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        json_response(['success' => false, 'message' => 'Order not found.'], 404);
    }

    // Customers can only cancel their own orders
    if ($role === 'customer' && $order['user_id'] != $userId) {
        json_response(['success' => false, 'message' => 'Access denied.'], 403);
    }

    // Can only cancel pending or ready orders
    if (!in_array($order['status'], ['pending', 'ready'])) {
        json_response(['success' => false, 'message' => 'This order cannot be cancelled.'], 400);
    }

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE orders SET status = 'cancelled', processed_by = ? WHERE id = ?")
        ->execute([$userId, $orderId]);

    // Restore stock
    $itemStmt = $pdo->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$orderId]);
    foreach ($itemStmt->fetchAll() as $item) {
        $pdo->prepare("UPDATE inventory SET stock_count = stock_count + ? WHERE id = ?")
            ->execute([$item['quantity'], $item['item_id']]);
    }

    $pdo->commit();

    log_info('Order cancelled', ['order_id' => $orderId, 'by' => $userId], $userId);
    json_response(['success' => true, 'message' => 'Order cancelled. Stock restored.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('Order cancel error', ['error' => $e->getMessage()], $userId);
    json_response(['success' => false, 'message' => 'An error occurred.'], 500);
}
