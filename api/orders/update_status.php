<?php
/**
 * API: Update Order Status
 * POST — { order_id, status }
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

$role = current_user_role();
if ($role !== 'admin' && $role !== 'staff') {
    json_response(['success' => false, 'message' => 'Access denied.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$orderId  = (int)($input['order_id'] ?? 0);
$newStatus = $input['status'] ?? '';

$allowed = ['pending', 'ready', 'claimed', 'cancelled'];
if (!$orderId || !in_array($newStatus, $allowed)) {
    json_response(['success' => false, 'message' => 'Invalid parameters.'], 400);
}

try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        json_response(['success' => false, 'message' => 'Order not found.'], 404);
    }

    // Validate status flow: pending → ready → claimed (+ cancelled from any non-claimed)
    $current = $order['status'];
    $validTransitions = [
        'pending'   => ['ready', 'cancelled'],
        'ready'     => ['claimed', 'cancelled'],
        'claimed'   => [],
        'cancelled' => [],
    ];

    if (!in_array($newStatus, $validTransitions[$current] ?? [])) {
        json_response(['success' => false, 'message' => "Cannot change from '$current' to '$newStatus'."], 400);
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, processed_by = ? WHERE id = ?");
    $stmt->execute([$newStatus, current_user_id(), $orderId]);

    // If cancelling, restore stock
    if ($newStatus === 'cancelled') {
        $itemStmt = $pdo->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
        $itemStmt->execute([$orderId]);
        $items = $itemStmt->fetchAll();

        foreach ($items as $item) {
            $pdo->prepare("UPDATE inventory SET stock_count = stock_count + ? WHERE id = ?")
                ->execute([$item['quantity'], $item['item_id']]);
        }
    }

    log_info('Order status updated', [
        'order_id' => $orderId,
        'from' => $current,
        'to' => $newStatus
    ], current_user_id());

    json_response(['success' => true, 'message' => "Order marked as $newStatus."]);

} catch (Exception $e) {
    log_error('Order status update error', ['error' => $e->getMessage()], current_user_id());
    json_response(['success' => false, 'message' => 'An error occurred.'], 500);
}
