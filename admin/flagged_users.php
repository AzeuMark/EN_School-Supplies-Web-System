<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Flagged Users — Admin';
$activePage = 'flagged';

try {
    $stmt = $pdo->query("SELECT * FROM users WHERE status = 'flagged' ORDER BY updated_at DESC");
    $flagged = $stmt->fetchAll();
} catch (Exception $e) { $flagged = []; }

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Flagged Users</h1>
  <p class="subtitle"><?= count($flagged) ?> flagged accounts</p>
</div>

<div class="table-wrapper">
  <table class="data-table">
    <thead>
      <tr><th>ID</th><th>Full Name</th><th>Email</th><th>Role</th><th>Flag Reason</th><th>Flagged At</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php if (empty($flagged)): ?>
        <tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No flagged users</td></tr>
      <?php else: ?>
        <?php foreach ($flagged as $f): ?>
          <tr>
            <td><?= $f['id'] ?></td>
            <td><strong><?= htmlspecialchars($f['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($f['email']) ?></td>
            <td><?= ucfirst($f['role']) ?></td>
            <td>
              <?php
              $reason = $f['flag_reason'] ?? 'No reason provided';
              if (strlen($reason) > 60): ?>
                <span><?= htmlspecialchars(substr($reason, 0, 60)) ?>...</span>
                <button class="btn btn-ghost btn-sm" onclick="Dialog.alert(`<?= htmlspecialchars(addslashes($reason)) ?>`, 'Flag Reason')">View</button>
              <?php else: ?>
                <?= htmlspecialchars($reason) ?>
              <?php endif; ?>
            </td>
            <td><?= date('M d, Y h:i A', strtotime($f['updated_at'])) ?></td>
            <td>
              <div class="actions">
                <button class="btn btn-sm btn-primary unflag-btn" data-id="<?= $f['id'] ?>">Remove Flag</button>
                <button class="btn btn-sm btn-danger del-flag-btn" data-id="<?= $f['id'] ?>">&#128465; Delete</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
document.querySelectorAll('.unflag-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!await Dialog.confirm('Remove the flag from this user and restore their access?', 'Remove Flag', 'Remove Flag')) return;
    try {
      const data = await apiFetch('api/users/flag_user.php', {
        body: JSON.stringify({ user_id: btn.dataset.id, action: 'unflag', csrf_token: getCSRFToken() })
      });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });
});

document.querySelectorAll('.del-flag-btn').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!await Dialog.danger('Permanently delete this user and all their data? This cannot be undone.', 'Delete User', 'Delete')) return;
    try {
      const data = await apiFetch('api/users/delete_user.php', {
        body: JSON.stringify({ user_id: btn.dataset.id, csrf_token: getCSRFToken() })
      });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
