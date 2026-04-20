<?php
require_once __DIR__ . '/../includes/auth.php';
require_staff();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Dashboard — Staff';
$activePage = 'dashboard';

$pendingOrders = $badges['pending_orders'];
$pendingAccounts = $badges['pending_accounts'];

// Recent 5 pending orders
$recentOrders = [];
try {
    $stmt = $pdo->query(
        "SELECT o.*, u.full_name AS user_name
         FROM orders o LEFT JOIN users u ON o.user_id = u.id
         WHERE o.status = 'pending'
         ORDER BY o.created_at DESC LIMIT 5"
    );
    $recentOrders = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Welcome back, <?= htmlspecialchars($user['full_name']) ?>!</h1>
  <p class="subtitle">Staff Dashboard</p>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="stat-card">
    <div class="stat-icon">&#128230;</div>
    <div class="stat-info">
      <div class="stat-value"><?= $pendingOrders ?></div>
      <div class="stat-label">Pending Orders</div>
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

<h3 class="mb-3">Quick Actions</h3>
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px,1fr)); gap:0.75rem; margin-bottom:1.5rem;">
  <a href="manage_orders.php" class="action-card"><span class="action-icon">&#128230;</span><span class="action-label">Manage Orders</span></a>
  <a href="pending_accounts.php" class="action-card"><span class="action-icon">&#128336;</span><span class="action-label">Pending Accounts</span></a>
</div>

<h3 class="mb-3">Recent Pending Orders</h3>
<div class="table-wrapper">
  <table class="data-table">
    <thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Date</th></tr></thead>
    <tbody>
      <?php if (empty($recentOrders)): ?>
        <tr><td colspan="4" class="text-center text-muted" style="padding:2rem">No pending orders</td></tr>
      <?php else: foreach ($recentOrders as $o): ?>
        <tr>
          <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
          <td><?= htmlspecialchars($o['user_name'] ?? $o['guest_name'] ?? 'Guest') ?></td>
          <td><?= format_price($o['total_price']) ?></td>
          <td><?= date('M d, h:i A', strtotime($o['created_at'])) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
