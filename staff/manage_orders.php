<?php
require_once __DIR__ . '/../includes/auth.php';
require_staff();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Manage Orders — Staff';
$activePage = 'orders';

$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = [];
$params = [];
if ($statusFilter !== 'all' && in_array($statusFilter, ['pending','ready','claimed','cancelled'])) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare(
        "SELECT o.*, u.full_name AS user_name, u.role AS user_role
         FROM orders o LEFT JOIN users u ON o.user_id = u.id
         $whereSQL ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset"
    );
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) { $orders = []; $total = 0; $totalPages = 1; }

$orderIds = array_column($orders, 'id');
$orderItemsMap = [];
if ($orderIds) {
    $ph = implode(',', array_fill(0, count($orderIds), '?'));
    $oiStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($ph)");
    $oiStmt->execute($orderIds);
    foreach ($oiStmt->fetchAll() as $oi) $orderItemsMap[$oi['order_id']][] = $oi;
}

$maintenanceMode = is_maintenance();

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Manage Orders</h1>
  <p class="subtitle"><?= $total ?> total orders</p>
</div>

<?php if ($maintenanceMode): ?>
  <div id="page-error" class="show" style="display:block">System is in maintenance mode. Order actions are disabled.</div>
<?php endif; ?>

<div class="toolbar">
  <div class="status-tabs">
    <?php foreach (['all'=>'All','pending'=>'Pending','ready'=>'Ready','claimed'=>'Claimed','cancelled'=>'Cancelled'] as $key => $label): ?>
      <a class="status-tab <?= $statusFilter === $key ? 'active' : '' ?>" href="?status=<?= $key ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="table-wrapper">
  <table class="data-table">
    <thead><tr><th>Order ID</th><th>Ordered By</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No orders</td></tr>
      <?php else: foreach ($orders as $o): ?>
        <tr>
          <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
          <td><?= htmlspecialchars($o['user_name'] ?? $o['guest_name'] ?? 'Guest') ?><br><span class="text-muted" style="font-size:0.75rem"><?= $o['user_id'] ? ucfirst($o['user_role'] ?? '') : 'Guest' ?></span></td>
          <td style="white-space:nowrap"><?= date('M d, Y h:i A', strtotime($o['created_at'])) ?></td>
          <td><?php foreach ($orderItemsMap[$o['id']] ?? [] as $oi): ?><div style="font-size:0.82rem"><?= htmlspecialchars($oi['item_name_snapshot']) ?> × <?= $oi['quantity'] ?></div><?php endforeach; ?></td>
          <td><strong><?= format_price($o['total_price']) ?></strong></td>
          <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
          <td>
            <?php if (!$maintenanceMode): ?>
              <div class="actions">
                <?php if ($o['status'] === 'pending'): ?>
                  <button class="btn btn-sm btn-primary order-action" data-id="<?= $o['id'] ?>" data-status="ready">&#10004; Ready</button>
                  <button class="btn btn-sm btn-danger order-action" data-id="<?= $o['id'] ?>" data-status="cancelled">&#10006;</button>
                <?php elseif ($o['status'] === 'ready'): ?>
                  <button class="btn btn-sm btn-accent order-action" data-id="<?= $o['id'] ?>" data-status="claimed">&#128077; Claimed</button>
                  <button class="btn btn-sm btn-danger order-action" data-id="<?= $o['id'] ?>" data-status="cancelled">&#10006;</button>
                <?php else: ?>
                  <span class="text-muted" style="font-size:0.8rem">—</span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination" style="margin-top:1rem">
  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="?status=<?= $statusFilter ?>&page=<?= $p ?>"><?= $p ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.order-action').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('Are you sure?')) return;
    if (!preventDoubleClick(btn)) return;
    try {
      const data = await apiFetch('api/orders/update_status.php', { body: JSON.stringify({ order_id: btn.dataset.id, status: btn.dataset.status, csrf_token: getCSRFToken() }) });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
