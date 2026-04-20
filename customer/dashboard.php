<?php
require_once __DIR__ . '/../includes/auth.php';
require_customer();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Dashboard — Customer';
$activePage = 'dashboard';

$userId = $user['id'];

// Customer stats
$totalOrders = 0;
$totalSpent = 0;
$pendingOrders = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalOrders = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE user_id = ? AND status != 'cancelled'");
    $stmt->execute([$userId]);
    $totalSpent = (float)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $pendingOrders = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Recent 5 orders
$recentOrders = [];
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
    );
    $stmt->execute([$userId]);
    $recentOrders = $stmt->fetchAll();
} catch (Exception $e) {}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?>!</h1>
  <p class="subtitle">Your order summary</p>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:1rem; margin-bottom:1.5rem;">
  <div class="stat-card">
    <div class="stat-icon">&#128230;</div>
    <div class="stat-info">
      <div class="stat-value"><?= $totalOrders ?></div>
      <div class="stat-label">Total Orders</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">&#128176;</div>
    <div class="stat-info">
      <div class="stat-value"><?= format_price($totalSpent) ?></div>
      <div class="stat-label">Total Spent</div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">&#128336;</div>
    <div class="stat-info">
      <div class="stat-value"><?= $pendingOrders ?></div>
      <div class="stat-label">Pending Orders</div>
    </div>
  </div>
</div>

<h3 class="mb-3">Quick Actions</h3>
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(140px,1fr)); gap:0.75rem; margin-bottom:1.5rem;">
  <a href="make_order.php" class="action-card"><span class="action-icon">&#128722;</span><span class="action-label">Make Order</span></a>
  <a href="order_history.php" class="action-card"><span class="action-icon">&#128196;</span><span class="action-label">Order History</span></a>
  <a href="profile.php" class="action-card"><span class="action-icon">&#128100;</span><span class="action-label">My Profile</span></a>
</div>

<h3 class="mb-3">Recent Orders</h3>
<div class="table-wrapper">
  <table class="data-table">
    <thead><tr><th>Order ID</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
      <?php if (empty($recentOrders)): ?>
        <tr><td colspan="4" class="text-center text-muted" style="padding:2rem">No orders yet. <a href="make_order.php">Place your first order!</a></td></tr>
      <?php else: foreach ($recentOrders as $o): ?>
        <tr>
          <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
          <td><?= format_price($o['total_price']) ?></td>
          <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
          <td><?= date('M d, h:i A', strtotime($o['created_at'])) ?></td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
