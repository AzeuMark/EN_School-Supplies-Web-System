<?php
require_once __DIR__ . '/../includes/auth.php';
require_customer();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Order History — Customer';
$activePage = 'order_history';

$userId = $user['id'];
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where = ["o.user_id = ?"];
$params = [$userId];
if ($statusFilter !== 'all' && in_array($statusFilter, ['pending','ready','claimed','cancelled'])) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare(
        "SELECT o.* FROM orders o $whereSQL ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) { $orders = []; $total = 0; $totalPages = 1; }

// Fetch order items
$orderIds = array_column($orders, 'id');
$oiMap = [];
if ($orderIds) {
    $ph = implode(',', array_fill(0, count($orderIds), '?'));
    $oiStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($ph)");
    $oiStmt->execute($orderIds);
    foreach ($oiStmt->fetchAll() as $oi) $oiMap[$oi['order_id']][] = $oi;
}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Order History</h1>
  <p class="subtitle"><?= $total ?> orders</p>
</div>

<div class="toolbar">
  <div class="status-tabs">
    <?php foreach (['all'=>'All','pending'=>'Pending','ready'=>'Ready','claimed'=>'Claimed','cancelled'=>'Cancelled'] as $key => $label): ?>
      <a class="status-tab <?= $statusFilter === $key ? 'active' : '' ?>" href="?status=<?= $key ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (empty($orders)): ?>
  <div class="empty-state" style="margin-top:2rem">
    <div class="empty-icon">&#128196;</div>
    <div class="empty-text">No orders found</div>
    <a href="make_order.php" class="btn btn-primary mt-3">Place an Order</a>
  </div>
<?php else: ?>
  <?php foreach ($orders as $o): ?>
    <div class="card mb-3">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <div>
          <strong><?= htmlspecialchars($o['order_code']) ?></strong>
          <span class="text-muted" style="font-size:0.8rem;margin-left:0.5rem"><?= date('M d, Y h:i A', strtotime($o['created_at'])) ?></span>
        </div>
        <span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
      </div>
      <div class="card-body">
        <table style="width:100%;font-size:0.85rem">
          <thead><tr><th style="text-align:left">Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Subtotal</th></tr></thead>
          <tbody>
            <?php foreach ($oiMap[$o['id']] ?? [] as $oi): ?>
              <tr>
                <td><?= htmlspecialchars($oi['item_name_snapshot']) ?></td>
                <td style="text-align:center"><?= $oi['quantity'] ?></td>
                <td style="text-align:right"><?= format_price($oi['unit_price']) ?></td>
                <td style="text-align:right"><?= format_price($oi['quantity'] * $oi['unit_price']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="text-align:right;margin-top:0.75rem;font-size:1rem;font-weight:700;color:var(--primary)">
          Total: <?= format_price($o['total_price']) ?>
        </div>
      </div>
      <div class="card-footer" style="display:flex;gap:0.5rem;justify-content:flex-end">
        <?php if ($o['status'] === 'pending'): ?>
          <button class="btn btn-sm btn-danger cancel-order-btn" data-id="<?= $o['id'] ?>">Cancel Order</button>
        <?php endif; ?>
        <a href="../receipt.php?order_id=<?= $o['id'] ?>&format=html" target="_blank" class="btn btn-sm btn-outline">&#128424; Receipt</a>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="?status=<?= $statusFilter ?>&page=<?= $p ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>

<script>
document.querySelectorAll('.cancel-order-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('Cancel this order?')) return;
    if (!preventDoubleClick(btn)) return;
    try {
      const data = await apiFetch('api/orders/cancel.php', {
        body: JSON.stringify({ order_id: btn.dataset.id, csrf_token: getCSRFToken() })
      });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
