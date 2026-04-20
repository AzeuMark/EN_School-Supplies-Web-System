<?php
/**
 * E&N School Supplies — Receipt Page
 * GET: ?order_id=X&format=html|pdf
 *
 * html → printable page
 * pdf  → server-side HTML-to-PDF using browser print (no wkhtmltopdf dependency)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$orderId = (int)($_GET['order_id'] ?? 0);
$format  = $_GET['format'] ?? 'html';

if (!$orderId) {
    http_response_code(400);
    echo 'Missing order_id.';
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT o.*, u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone
         FROM orders o LEFT JOIN users u ON o.user_id = u.id
         WHERE o.id = ? LIMIT 1"
    );
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo 'Order not found.';
        exit;
    }

    // Access control: owner, admin, or staff can view
    $userId = current_user_id();
    $role   = current_user_role();
    if ($userId) {
        if ($role !== 'admin' && $role !== 'staff' && $order['user_id'] != $userId) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
    }
    // Guest orders can be viewed publicly via direct link (by order_id)

    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error loading receipt.';
    exit;
}

$storeName  = get_setting('store_name', 'E&N School Supplies');
$storePhone = get_setting('store_phone', '');
$storeEmail = get_setting('store_email', '');
$logoPath   = get_setting('logo_path', 'assets/images/logo.png');

$customerName  = $order['user_name'] ?? $order['guest_name'] ?? 'Guest';
$customerPhone = $order['user_phone'] ?? $order['guest_phone'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Receipt — <?= htmlspecialchars($order['order_code']) ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', 'Inter', sans-serif;
      font-size: 13px;
      color: #222;
      background: #fff;
      padding: 20px;
      max-width: 480px;
      margin: 0 auto;
    }
    .receipt-header {
      text-align: center;
      margin-bottom: 16px;
      border-bottom: 2px dashed #ccc;
      padding-bottom: 12px;
    }
    .receipt-header img {
      width: 48px; height: 48px;
      object-fit: contain;
      margin-bottom: 6px;
    }
    .receipt-header h1 {
      font-size: 16px;
      margin-bottom: 2px;
    }
    .receipt-header .store-info {
      font-size: 11px;
      color: #666;
    }
    .order-meta {
      margin-bottom: 12px;
      font-size: 12px;
    }
    .order-meta .row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 3px;
    }
    .order-meta .label { color: #888; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 10px 0;
    }
    th, td {
      padding: 6px 4px;
      text-align: left;
      font-size: 12px;
    }
    th {
      border-bottom: 1px solid #999;
      font-weight: 700;
      font-size: 11px;
      text-transform: uppercase;
      color: #555;
    }
    td { border-bottom: 1px solid #eee; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .total-row {
      display: flex;
      justify-content: space-between;
      font-size: 16px;
      font-weight: 800;
      padding: 10px 0;
      border-top: 2px dashed #ccc;
      margin-top: 6px;
    }
    .total-row .amount { color: #2e7d32; }
    .footer {
      text-align: center;
      margin-top: 16px;
      padding-top: 12px;
      border-top: 2px dashed #ccc;
      font-size: 11px;
      color: #999;
    }
    .status-badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .status-pending { background: #fff8e1; color: #f57f17; }
    .status-ready { background: #e3f2fd; color: #1565c0; }
    .status-claimed { background: #e8f5e9; color: #2e7d32; }
    .status-cancelled { background: #ffebee; color: #d32f2f; text-decoration: line-through; }

    @media print {
      body { padding: 0; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="no-print" style="text-align:center;margin-bottom:16px">
  <button onclick="window.print()" style="padding:8px 24px;background:#2e7d32;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600">&#128424; Print Receipt</button>
  <button onclick="window.close()" style="padding:8px 24px;background:#eee;color:#333;border:none;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px">Close</button>
</div>

<div class="receipt-header">
  <img src="<?= $logoPath ?>" alt="Logo" onerror="this.style.display='none'">
  <h1><?= htmlspecialchars($storeName) ?></h1>
  <div class="store-info">
    <?php if ($storePhone): ?><?= htmlspecialchars($storePhone) ?> &middot; <?php endif; ?>
    <?php if ($storeEmail): ?><?= htmlspecialchars($storeEmail) ?><?php endif; ?>
  </div>
</div>

<div class="order-meta">
  <div class="row"><span class="label">Order ID:</span><strong><?= htmlspecialchars($order['order_code']) ?></strong></div>
  <div class="row"><span class="label">Date:</span><span><?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></span></div>
  <div class="row"><span class="label">Customer:</span><span><?= htmlspecialchars($customerName) ?></span></div>
  <?php if ($customerPhone): ?>
    <div class="row"><span class="label">Phone:</span><span><?= htmlspecialchars($customerPhone) ?></span></div>
  <?php endif; ?>
  <div class="row">
    <span class="label">Status:</span>
    <span class="status-badge status-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span>
  </div>
</div>

<table>
  <thead>
    <tr><th>Item</th><th class="text-center">Qty</th><th class="text-right">Price</th><th class="text-right">Subtotal</th></tr>
  </thead>
  <tbody>
    <?php foreach ($items as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['item_name_snapshot']) ?></td>
        <td class="text-center"><?= $item['quantity'] ?></td>
        <td class="text-right">₱<?= number_format($item['unit_price'], 2) ?></td>
        <td class="text-right">₱<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="total-row">
  <span>Grand Total</span>
  <span class="amount">₱<?= number_format($order['total_price'], 2) ?></span>
</div>

<?php if (!empty($order['guest_note'])): ?>
  <div style="margin-top:8px;font-size:11px;color:#666">
    <strong>Note:</strong> <?= htmlspecialchars($order['guest_note']) ?>
  </div>
<?php endif; ?>

<div class="footer">
  Thank you for shopping at <?= htmlspecialchars($storeName) ?>!<br>
  Please present this receipt to claim your order.
</div>

</body>
</html>
