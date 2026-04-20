<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Manage Users — Admin';
$activePage = 'users';

try {
    $stmt = $pdo->query(
        "SELECT * FROM users WHERE status != 'pending' ORDER BY created_at DESC"
    );
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Manage Users</h1>
  <p class="subtitle"><?= count($users) ?> users</p>
</div>

<div class="toolbar">
  <div></div>
  <button class="btn btn-primary" onclick="openModal('add-user-modal')">+ Add User</button>
</div>

<div class="table-wrapper">
  <table class="data-table">
    <thead>
      <tr><th>ID</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
        <tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No users</td></tr>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['phone']) ?></td>
            <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'claimed' : 'ready' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
            <td>
              <div class="actions">
                <?php if ($u['id'] !== $user['id']): ?>
                  <button class="btn btn-icon btn-sm btn-ghost edit-user-btn"
                    data-id="<?= $u['id'] ?>"
                    data-name="<?= htmlspecialchars($u['full_name']) ?>"
                    data-email="<?= htmlspecialchars($u['email']) ?>"
                    data-phone="<?= htmlspecialchars($u['phone']) ?>"
                    data-role="<?= $u['role'] ?>"
                    title="Edit">&#9998;</button>
                  <?php if ($u['status'] !== 'flagged'): ?>
                    <button class="btn btn-icon btn-sm btn-ghost flag-user-btn" data-id="<?= $u['id'] ?>" title="Flag">&#9873;</button>
                  <?php endif; ?>
                  <button class="btn btn-icon btn-sm btn-ghost text-danger delete-user-btn" data-id="<?= $u['id'] ?>" title="Delete">&#128465;</button>
                <?php else: ?>
                  <span class="text-muted" style="font-size:0.8rem">You</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="add-user-modal">
  <div class="modal">
    <div class="modal-header"><h3>Add User</h3><button class="modal-close" onclick="closeModal('add-user-modal')">&times;</button></div>
    <div class="modal-body">
      <form id="add-user-form">
        <div class="form-group"><label class="form-label">Full Name</label><input class="form-input" name="full_name" required></div>
        <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" required></div>
        <div class="form-group"><label class="form-label">Phone</label><input class="form-input" name="phone"></div>
        <div class="form-group"><label class="form-label">Password</label><input class="form-input" type="password" name="password" required></div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-select" name="role">
            <option value="staff">Staff</option>
            <option value="customer">Customer</option>
          </select>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('add-user-modal')">Cancel</button>
      <button class="btn btn-primary" id="add-user-submit">Add User</button>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="edit-user-modal">
  <div class="modal">
    <div class="modal-header"><h3>Edit User</h3><button class="modal-close" onclick="closeModal('edit-user-modal')">&times;</button></div>
    <div class="modal-body">
      <form id="edit-user-form">
        <input type="hidden" name="user_id" id="edit-user-id">
        <div class="form-group"><label class="form-label">Full Name</label><input class="form-input" name="full_name" id="edit-full-name" required></div>
        <div class="form-group"><label class="form-label">Email</label><input class="form-input" type="email" name="email" id="edit-email" required></div>
        <div class="form-group"><label class="form-label">Phone</label><input class="form-input" name="phone" id="edit-phone"></div>
        <div class="form-group"><label class="form-label">New Password <span class="text-muted" style="font-weight:400">(leave blank to keep)</span></label><input class="form-input" type="password" name="password"></div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select class="form-select" name="role" id="edit-role">
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
            <option value="customer">Customer</option>
          </select>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('edit-user-modal')">Cancel</button>
      <button class="btn btn-primary" id="edit-user-submit">Save</button>
    </div>
  </div>
</div>

<!-- Flag User Modal -->
<div class="modal-overlay" id="flag-user-modal">
  <div class="modal">
    <div class="modal-header"><h3>Flag User</h3><button class="modal-close" onclick="closeModal('flag-user-modal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="flag-user-id">
      <div class="form-group">
        <label class="form-label">Reason for flagging</label>
        <textarea class="form-textarea" id="flag-reason" rows="3" placeholder="Enter reason..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('flag-user-modal')">Cancel</button>
      <button class="btn btn-danger" id="flag-user-submit">Flag User</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Add User
  document.getElementById('add-user-submit').addEventListener('click', async () => {
    const form = document.getElementById('add-user-form');
    const fd = new FormData(form);
    const payload = Object.fromEntries(fd);
    payload.csrf_token = getCSRFToken();
    try {
      const data = await apiFetch('api/users/add_user.php', { body: JSON.stringify(payload) });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });

  // Edit User - open modal
  document.querySelectorAll('.edit-user-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('edit-user-id').value = btn.dataset.id;
      document.getElementById('edit-full-name').value = btn.dataset.name;
      document.getElementById('edit-email').value = btn.dataset.email;
      document.getElementById('edit-phone').value = btn.dataset.phone;
      document.getElementById('edit-role').value = btn.dataset.role;
      openModal('edit-user-modal');
    });
  });

  // Edit User - submit
  document.getElementById('edit-user-submit').addEventListener('click', async () => {
    const form = document.getElementById('edit-user-form');
    const fd = new FormData(form);
    const payload = Object.fromEntries(fd);
    payload.csrf_token = getCSRFToken();
    try {
      const data = await apiFetch('api/users/edit_user.php', { body: JSON.stringify(payload) });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });

  // Flag User
  document.querySelectorAll('.flag-user-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('flag-user-id').value = btn.dataset.id;
      document.getElementById('flag-reason').value = '';
      openModal('flag-user-modal');
    });
  });

  document.getElementById('flag-user-submit').addEventListener('click', async () => {
    const userId = document.getElementById('flag-user-id').value;
    const reason = document.getElementById('flag-reason').value;
    try {
      const data = await apiFetch('api/users/flag_user.php', {
        body: JSON.stringify({ user_id: userId, action: 'flag', reason, csrf_token: getCSRFToken() })
      });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });

  // Delete User
  document.querySelectorAll('.delete-user-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (!confirm('Delete this user permanently?')) return;
      try {
        const data = await apiFetch('api/users/delete_user.php', {
          body: JSON.stringify({ user_id: btn.dataset.id, csrf_token: getCSRFToken() })
        });
        if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
      } catch(e) {}
    });
  });
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
