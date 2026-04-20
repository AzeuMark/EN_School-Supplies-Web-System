<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Dashboard — Admin';
$activePage = 'dashboard';

// Mini analytics
$ordersToday = 0;
$revenueToday = 0;
$pendingAccounts = $badges['pending_accounts'];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
    $ordersToday = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
    $revenueToday = (float)$stmt->fetchColumn();
} catch (Exception $e) {}

// Recent 5 orders
$recentOrders = [];
try {
    $stmt = $pdo->query(
        "SELECT o.*, u.full_name AS user_name
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.id
         ORDER BY o.created_at DESC LIMIT 5"
    );
    $recentOrders = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h1>
  <p class="subtitle">Here's your store overview for today</p>
</div>

<!-- Stat Cards -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="stat-card">
    <div class="stat-icon">&#128230;</div>
    <div class="stat-info">
      <div class="stat-value"><?= $ordersToday ?></div>
      <div class="stat-label">Orders Today</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">&#128176;</div>
    <div class="stat-info">
      <div class="stat-value"><?= format_price($revenueToday) ?></div>
      <div class="stat-label">Revenue Today</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">&#128336;</div>
    <div class="stat-info">
      <div class="stat-value"><?= $pendingAccounts ?></div>
      <div class="stat-label">Pending Accounts</div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<h3 class="mb-3">Quick Actions</h3>
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px,1fr)); gap:0.75rem; margin-bottom:1.5rem;">
  <a href="manage_orders.php" class="action-card"><span class="action-icon">&#128230;</span><span class="action-label">Manage Orders</span></a>
  <a href="manage_users.php" class="action-card"><span class="action-icon">&#128101;</span><span class="action-label">Manage Users</span></a>
  <a href="pending_accounts.php" class="action-card"><span class="action-icon">&#128336;</span><span class="action-label">Pending Accounts</span></a>
  <a href="inventory.php" class="action-card"><span class="action-icon">&#128451;</span><span class="action-label">Inventory</span></a>
  <a href="analytics.php" class="action-card"><span class="action-icon">&#128200;</span><span class="action-label">Analytics</span></a>
  <a href="system_settings.php" class="action-card"><span class="action-icon">&#9881;</span><span class="action-label">Settings</span></a>
</div>

<!-- Recent Orders -->
<h3 class="mb-3">Recent Orders</h3>
<div class="table-wrapper">
  <table class="data-table">
    <thead>
      <tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr>
    </thead>
    <tbody>
      <?php if (empty($recentOrders)): ?>
        <tr><td colspan="5" class="text-center text-muted" style="padding:2rem">No orders yet</td></tr>
      <?php else: ?>
        <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
            <td><?= htmlspecialchars($o['user_name'] ?? $o['guest_name'] ?? 'Guest') ?></td>
            <td><?= format_price($o['total_price']) ?></td>
            <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td><?= date('M d, h:i A', strtotime($o['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
