<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Manage Orders — Admin';
$activePage = 'orders';

// Pagination & filter
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

    $sql = "SELECT o.*, u.full_name AS user_name, u.role AS user_role
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            $whereSQL
            ORDER BY o.created_at DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
    $total = 0;
    $totalPages = 1;
}

// Fetch order items for expandable view
$orderIds = array_column($orders, 'id');
$orderItemsMap = [];
if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $oiStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders)");
    $oiStmt->execute($orderIds);
    foreach ($oiStmt->fetchAll() as $oi) {
        $orderItemsMap[$oi['order_id']][] = $oi;
    }
}

include __DIR__ . '/../includes/layout_header.php';
?>

<style>
  @media (max-width: 700px) {
    .status-tabs { gap: 0.3rem; }
    .status-tab  { font-size: 0.78rem; padding: 0.35rem 0.7rem; }

    .table-wrapper {
      border: none;
      background: transparent;
      overflow: visible;
    }
    .data-table thead { display: none; }
    .data-table tbody { display: flex; flex-direction: column; gap: 0.85rem; }
    .data-table tr {
      display: block;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 0.85rem 1rem;
      box-shadow: var(--shadow-sm);
    }
    .data-table tr:hover td { background: transparent; }
    .data-table td {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 0.75rem;
      padding: 0.45rem 0;
      border-bottom: 1px solid var(--border);
      font-size: 0.85rem;
      border-radius: 0;
    }
    .data-table td:last-child { border-bottom: none; padding-bottom: 0; }
    .data-table td:first-child { padding-top: 0; }
    .data-table td::before {
      content: attr(data-label);
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      white-space: nowrap;
      min-width: 80px;
      padding-top: 0.1rem;
      flex-shrink: 0;
    }
    .data-table td .actions { flex-wrap: wrap; gap: 0.4rem; }
    .data-table td[data-label="Actions"] { align-items: center; }
    .data-table td[colspan] {
      justify-content: center;
      border-bottom: none;
    }
    .data-table td[colspan]::before { display: none; }
  }
</style>

<div class="page-header">
  <h1>Manage Orders</h1>
  <p class="subtitle"><?= $total ?> total orders</p>
</div>

<!-- Status Tabs -->
<div class="toolbar">
  <div class="status-tabs">
    <?php foreach (['all'=>'All','pending'=>'Pending','ready'=>'Ready','claimed'=>'Claimed','cancelled'=>'Cancelled'] as $key => $label): ?>
      <a class="status-tab <?= $statusFilter === $key ? 'active' : '' ?>" href="?status=<?= $key ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="table-wrapper">
  <table class="data-table">
    <thead>
      <tr>
        <th>Order ID</th><th>Ordered By</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($orders)): ?>
        <tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No orders found</td></tr>
      <?php else: ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td data-label="Order ID"><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
            <td data-label="Ordered By">
              <span>
                <?= htmlspecialchars($o['user_name'] ?? $o['guest_name'] ?? 'Guest') ?>
                <br><span class="text-muted" style="font-size:0.75rem"><?= $o['user_id'] ? ucfirst($o['user_role'] ?? '') : 'Guest' ?></span>
              </span>
            </td>
            <td data-label="Date" style="white-space:nowrap"><?= date('M d, Y h:i A', strtotime($o['created_at'])) ?></td>
            <td data-label="Items">
              <span>
                <?php $items = $orderItemsMap[$o['id']] ?? []; ?>
                <?php foreach ($items as $oi): ?>
                  <div style="font-size:0.82rem"><?= htmlspecialchars($oi['item_name_snapshot']) ?> × <?= $oi['quantity'] ?></div>
                <?php endforeach; ?>
              </span>
            </td>
            <td data-label="Total"><strong><?= format_price($o['total_price']) ?></strong></td>
            <td data-label="Status"><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td data-label="Actions">
              <div class="actions">
                <?php if ($o['status'] === 'pending'): ?>
                  <button class="btn btn-sm btn-primary order-action" data-id="<?= $o['id'] ?>" data-status="ready" title="Mark Ready">&#10004; Ready</button>
                  <button class="btn btn-sm btn-danger order-action" data-id="<?= $o['id'] ?>" data-status="cancelled" title="Cancel">&#10006;</button>
                <?php elseif ($o['status'] === 'ready'): ?>
                  <button class="btn btn-sm btn-accent order-action" data-id="<?= $o['id'] ?>" data-status="claimed" title="Mark Claimed">&#128077; Claimed</button>
                  <button class="btn btn-sm btn-danger order-action" data-id="<?= $o['id'] ?>" data-status="cancelled" title="Cancel">&#10006;</button>
                <?php else: ?>
                  <span class="text-muted" style="font-size:0.8rem">—</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
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
    const msgs = { ready: 'Mark this order as Ready for pick-up?', claimed: 'Mark this order as Claimed?', cancelled: 'Cancel this order? This cannot be undone.' };
    const titles = { ready: 'Mark as Ready', claimed: 'Mark as Claimed', cancelled: 'Cancel Order' };
    const s = btn.dataset.status;
    const confirmed = s === 'cancelled'
      ? await Dialog.danger(msgs[s] || 'Are you sure?', titles[s] || 'Confirm', 'Cancel Order')
      : await Dialog.confirm(msgs[s] || 'Are you sure?', titles[s] || 'Confirm', 'Confirm');
    if (!confirmed) return;
    if (!preventDoubleClick(btn)) return;
    try {
      const data = await apiFetch('api/orders/update_status.php', {
        body: JSON.stringify({
          order_id: btn.dataset.id,
          status: btn.dataset.status,
          csrf_token: getCSRFToken()
        })
      });
      if (data.success) {
        Toast.success(data.message);
        setTimeout(() => location.reload(), 600);
      }
    } catch(e) {}
  });
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
