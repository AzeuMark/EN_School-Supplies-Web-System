<?php
require_once __DIR__ . '/../includes/auth.php';
require_staff();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Pending Accounts — Staff';
$activePage = 'pending';

try {
    $stmt = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC");
    $pending = $stmt->fetchAll();
} catch (Exception $e) { $pending = []; }

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Pending Accounts</h1>
  <p class="subtitle"><?= count($pending) ?> accounts awaiting approval</p>
</div>

<div class="table-wrapper">
  <table class="data-table">
    <thead>
      <tr><th>ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Registered</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php if (empty($pending)): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:2rem">No pending accounts</td></tr>
      <?php else: ?>
        <?php foreach ($pending as $p): ?>
          <tr>
            <td><?= $p['id'] ?></td>
            <td><strong><?= htmlspecialchars($p['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
            <td><?= htmlspecialchars($p['phone']) ?></td>
            <td><?= date('M d, Y h:i A', strtotime($p['created_at'])) ?></td>
            <td>
              <button class="btn btn-sm btn-primary approve-btn" data-id="<?= $p['id'] ?>">&#10004; Approve</button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
document.querySelectorAll('.approve-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!preventDoubleClick(btn)) return;
    try {
      const data = await apiFetch('api/users/approve_user.php', {
        body: JSON.stringify({ user_id: btn.dataset.id, csrf_token: getCSRFToken() })
      });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
