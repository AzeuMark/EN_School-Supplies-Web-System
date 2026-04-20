<?php
/**
 * API: Create Order
 * POST — handles both guest (kiosk) and logged-in customer orders
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/logger.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$userId    = current_user_id();
$guestName  = sanitize($input['guest_name'] ?? '');
$guestPhone = sanitize($input['guest_phone'] ?? '');
$guestNote  = sanitize($input['guest_note'] ?? '');
$items      = $input['items'] ?? [];

// Determine if guest or logged-in
$isGuest = !$userId;

if ($isGuest) {
    if (empty($guestName) || empty($guestPhone)) {
        json_response(['success' => false, 'message' => 'Name and phone are required.'], 400);
    }
    // Check if no-login orders disabled
    if (get_setting('disable_nologin_orders', '0') === '1') {
        json_response(['success' => false, 'message' => 'No-login ordering is disabled.'], 403);
    }
}

if (empty($items) || !is_array($items)) {
    json_response(['success' => false, 'message' => 'No items selected.'], 400);
}

try {
    $pdo->beginTransaction();

    $orderCode = generate_order_code();
    $totalPrice = 0;
    $orderItems = [];

    // Validate and lock inventory rows
    foreach ($items as $entry) {
        $itemId = (int)($entry['item_id'] ?? 0);
        $qty    = (int)($entry['quantity'] ?? 0);

        if ($itemId <= 0 || $qty <= 0) continue;

        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ? FOR UPDATE");
        $stmt->execute([$itemId]);
        $inv = $stmt->fetch();

        if (!$inv) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => "Item ID $itemId not found."], 400);
        }

        if ($inv['stock_count'] < $qty) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => "Not enough stock for '{$inv['item_name']}'. Available: {$inv['stock_count']}"], 400);
        }

        $maxQty = min((int)$inv['max_order_qty'], (int)$inv['stock_count']);
        if ($qty > $maxQty) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => "Max order quantity for '{$inv['item_name']}' is $maxQty."], 400);
        }

        $subtotal = $qty * (float)$inv['price'];
        $totalPrice += $subtotal;

        $orderItems[] = [
            'item_id'            => $itemId,
            'item_name_snapshot' => $inv['item_name'],
            'quantity'           => $qty,
            'unit_price'         => $inv['price'],
        ];

        // Deduct stock
        $pdo->prepare("UPDATE inventory SET stock_count = stock_count - ? WHERE id = ?")->execute([$qty, $itemId]);
    }

    if (empty($orderItems)) {
        $pdo->rollBack();
        json_response(['success' => false, 'message' => 'No valid items.'], 400);
    }

    // Insert order
    $stmt = $pdo->prepare(
        "INSERT INTO orders (order_code, user_id, guest_name, guest_phone, guest_note, status, total_price)
         VALUES (?, ?, ?, ?, ?, 'pending', ?)"
    );
    $stmt->execute([
        $orderCode,
        $userId ?: null,
        $isGuest ? $guestName : null,
        $isGuest ? $guestPhone : null,
        $isGuest ? ($guestNote ?: null) : null,
        $totalPrice
    ]);
    $orderId = $pdo->lastInsertId();

    // Insert order items
    $stmtOI = $pdo->prepare(
        "INSERT INTO order_items (order_id, item_id, item_name_snapshot, quantity, unit_price)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($orderItems as $oi) {
        $stmtOI->execute([$orderId, $oi['item_id'], $oi['item_name_snapshot'], $oi['quantity'], $oi['unit_price']]);
    }

    $pdo->commit();

    log_info('Order created', [
        'order_id' => $orderId,
        'order_code' => $orderCode,
        'guest' => $isGuest,
        'total' => $totalPrice
    ], $userId);

    // Return order data for receipt
    json_response([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order'   => [
            'id'          => $orderId,
            'order_code'  => $orderCode,
            'guest_name'  => $isGuest ? $guestName : null,
            'total_price' => $totalPrice,
            'status'      => 'pending',
            'items'       => $orderItems,
            'created_at'  => date('M d, Y h:i A')
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    log_error('Order creation error', ['error' => $e->getMessage()], $userId);
    json_response(['success' => false, 'message' => 'Failed to create order.'], 500);
}
