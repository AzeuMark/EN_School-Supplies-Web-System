<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'System Settings — Admin';
$activePage = 'settings';

// Load current settings
$s = [];
$keys = ['store_name','store_phone','store_email','timezone','force_dark_mode',
         'disable_nologin_orders','online_payment','system_status','auto_logout_hours','logo_path'];
foreach ($keys as $k) {
    $s[$k] = get_setting($k, '');
}

// Fetch categories & default names
try {
    $categories = $pdo->query("SELECT * FROM item_categories ORDER BY category_name")->fetchAll();
    $defaultNames = $pdo->query("SELECT * FROM default_item_names ORDER BY item_name")->fetchAll();
} catch (Exception $e) { $categories = []; $defaultNames = []; }

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>System Settings</h1>
  <p class="subtitle">Configure your store</p>
</div>

<div style="max-width:800px">

  <!-- Logo -->
  <div class="card mb-4">
    <div class="card-header">Store Logo</div>
    <div class="card-body d-flex align-center gap-3">
      <img id="logo-preview" src="<?= $basePath . ($s['logo_path'] ?: 'assets/images/logo.png') ?>" alt="Logo" style="width:64px;height:64px;border-radius:8px;object-fit:contain;background:var(--background)" onerror="this.style.display='none'">
      <form id="logo-form" enctype="multipart/form-data">
        <input type="file" name="logo" id="logo-input" accept="image/jpeg,image/png" style="display:none">
        <label for="logo-input" class="btn btn-outline btn-sm" style="cursor:pointer">Upload New Logo</label>
        <p class="text-muted" style="font-size:0.72rem;margin-top:0.3rem">PNG or JPG. Saved as assets/images/logo.png</p>
      </form>
    </div>
  </div>

  <!-- General -->
  <div class="card mb-4">
    <div class="card-header">General</div>
    <div class="card-body">
      <form id="settings-form">
        <div class="form-group"><label class="form-label">Store Name</label><input class="form-input" name="store_name" value="<?= htmlspecialchars($s['store_name']) ?>"></div>
        <div class="settings-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="form-group"><label class="form-label">Store Phone</label><input class="form-input" name="store_phone" value="<?= htmlspecialchars($s['store_phone']) ?>"></div>
          <div class="form-group"><label class="form-label">Store Email</label><input class="form-input" type="email" name="store_email" value="<?= htmlspecialchars($s['store_email']) ?>"></div>
        </div>
        <div class="settings-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div class="form-group">
            <label class="form-label">System Timezone</label>
            <select class="form-select" name="timezone">
              <?php foreach (['Asia/Manila','Asia/Singapore','Asia/Tokyo','Asia/Shanghai','America/New_York','Europe/London','UTC'] as $tz): ?>
                <option value="<?= $tz ?>" <?= $s['timezone'] === $tz ? 'selected' : '' ?>><?= $tz ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">System Status</label>
            <select class="form-select" name="system_status">
              <option value="online" <?= $s['system_status']==='online'?'selected':'' ?>>Online</option>
              <option value="offline" <?= $s['system_status']==='offline'?'selected':'' ?>>Offline</option>
              <option value="maintenance" <?= $s['system_status']==='maintenance'?'selected':'' ?>>Maintenance</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Staff Auto-Logout Threshold (hours)</label>
          <input class="form-input" type="number" name="auto_logout_hours" value="<?= (int)$s['auto_logout_hours'] ?>" min="1" max="24" style="max-width:120px">
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:1rem 0">
        <h4 class="mb-3">Toggles</h4>

        <div style="display:flex;flex-direction:column;gap:0.75rem">
          <label class="d-flex align-center gap-2" style="cursor:pointer">
            <span class="toggle-switch"><input type="checkbox" name="force_dark_mode" value="1" <?= $s['force_dark_mode']==='1'?'checked':'' ?>><span class="toggle-slider"></span></span>
            Force Dark Mode (overrides all users)
          </label>
          <label class="d-flex align-center gap-2" style="cursor:pointer">
            <span class="toggle-switch"><input type="checkbox" name="disable_nologin_orders" value="1" <?= $s['disable_nologin_orders']==='1'?'checked':'' ?>><span class="toggle-slider"></span></span>
            Disable No-Login Orders (kiosk)
          </label>
          <label class="d-flex align-center gap-2" style="cursor:pointer">
            <span class="toggle-switch"><input type="checkbox" name="online_payment" value="1" <?= $s['online_payment']==='1'?'checked':'' ?>><span class="toggle-slider"></span></span>
            Enable Online Payment (placeholder)
          </label>
        </div>

        <button type="submit" class="btn btn-primary mt-4">Save Settings</button>
      </form>
    </div>
  </div>

  <!-- Categories Tag Manager -->
  <div class="card mb-4">
    <div class="card-header">Default Item Categories</div>
    <div class="card-body">
      <div id="cat-tags" class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($categories as $cat): ?>
          <span class="tag"><?= htmlspecialchars($cat['category_name']) ?><button class="tag-remove" onclick="this.parentElement.remove()">&times;</button></span>
        <?php endforeach; ?>
      </div>
      <div class="d-flex gap-2">
        <input class="form-input" type="text" id="cat-input" placeholder="Add category..." style="max-width:250px">
        <button class="btn btn-outline btn-sm" id="cat-add-btn">Add</button>
      </div>
    </div>
  </div>

  <!-- Default Names Tag Manager -->
  <div class="card mb-4">
    <div class="card-header">Default Item Names</div>
    <div class="card-body">
      <div id="name-tags" class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($defaultNames as $dn): ?>
          <span class="tag"><?= htmlspecialchars($dn['item_name']) ?><button class="tag-remove" onclick="this.parentElement.remove()">&times;</button></span>
        <?php endforeach; ?>
      </div>
      <div class="d-flex gap-2">
        <input class="form-input" type="text" id="name-input" placeholder="Add item name..." style="max-width:250px">
        <button class="btn btn-outline btn-sm" id="name-add-btn">Add</button>
      </div>
    </div>
  </div>

</div>

<style>
  @media (max-width: 600px) {
    .settings-grid-2 { grid-template-columns: 1fr !important; }
    div[style*="max-width:800px"] { padding: 0; }
    .card-body .d-flex.align-center.gap-3 { flex-wrap: wrap; }
    #cat-input, #name-input { max-width: 100% !important; flex: 1; }
    .d-flex.gap-2:has(#cat-input), .d-flex.gap-2:has(#name-input) { flex-wrap: wrap; }
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Logo upload
  document.getElementById('logo-input').addEventListener('change', async function() {
    if (!this.files[0]) return;
    const fd = new FormData();
    fd.append('logo', this.files[0]);
    fd.append('csrf_token', getCSRFToken());
    try {
      const data = await apiFetch('api/settings/update.php', { body: fd });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 600); }
    } catch(e) {}
  });

  // Tag add helpers
  function addTag(containerId, inputId) {
    const input = document.getElementById(inputId);
    const val = input.value.trim();
    if (!val) return;
    const container = document.getElementById(containerId);
    const tag = document.createElement('span');
    tag.className = 'tag';
    tag.innerHTML = `${escapeHtml(val)}<button class="tag-remove" onclick="this.parentElement.remove()">&times;</button>`;
    container.appendChild(tag);
    input.value = '';
  }

  document.getElementById('cat-add-btn').addEventListener('click', () => addTag('cat-tags', 'cat-input'));
  document.getElementById('cat-input').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); addTag('cat-tags', 'cat-input'); } });
  document.getElementById('name-add-btn').addEventListener('click', () => addTag('name-tags', 'name-input'));
  document.getElementById('name-input').addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); addTag('name-tags', 'name-input'); } });

  // Save settings
  document.getElementById('settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const settings = {};

    // Text/select inputs
    ['store_name','store_phone','store_email','timezone','system_status','auto_logout_hours'].forEach(k => {
      const el = form.querySelector(`[name="${k}"]`);
      if (el) settings[k] = el.value;
    });

    // Checkboxes (send '0' if unchecked)
    ['force_dark_mode','disable_nologin_orders','online_payment'].forEach(k => {
      const el = form.querySelector(`[name="${k}"]`);
      settings[k] = el && el.checked ? '1' : '0';
    });

    // Collect tags
    const categories = Array.from(document.querySelectorAll('#cat-tags .tag')).map(t => t.firstChild.textContent.trim());
    const defaultNames = Array.from(document.querySelectorAll('#name-tags .tag')).map(t => t.firstChild.textContent.trim());

    try {
      const data = await apiFetch('api/settings/update.php', {
        body: JSON.stringify({ settings, categories, default_names: defaultNames, csrf_token: getCSRFToken() })
      });
      if (data.success) { Toast.success(data.message); setTimeout(() => location.reload(), 800); }
    } catch(e) {}
  });
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
