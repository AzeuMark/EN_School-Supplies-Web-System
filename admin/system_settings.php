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

<link rel="stylesheet" href="<?= $basePath ?>assets/css/pages/system_settings.css">

<div class="page-header">
  <h1>System Settings</h1>
  <p class="subtitle">Configure your store preferences, branding, and system behavior</p>
</div>

<form id="settings-form">

  <!-- Card 1: Store Information -->
  <div class="ss-card">
    <div class="ss-card-body">
      <div class="ss-heading">Store Information</div>

      <div class="ss-grid-store">
        <div class="ss-logo-row">
          <div class="ss-logo-preview">
            <img id="logo-preview" src="<?= $basePath . ($s['logo_path'] ?: 'assets/images/logo.png') ?>" alt="Logo" onerror="this.style.display='none'">
          </div>
          <div class="ss-logo-actions">
            <input type="file" name="logo" id="logo-input" accept="image/jpeg,image/png" style="display:none">
            <label for="logo-input" class="ss-upload-btn">&#128247; Upload Logo</label>
            <span class="ss-upload-hint">PNG or JPG, max 1 MB</span>
          </div>
        </div>
        <div class="ss-field">
          <label class="ss-label" for="store_name">Store Name</label>
          <input class="form-input" id="store_name" name="store_name" value="<?= htmlspecialchars($s['store_name']) ?>" placeholder="e.g. E&N School Supplies">
        </div>
        <div class="ss-field">
          <label class="ss-label" for="store_phone">Store Phone</label>
          <input class="form-input" id="store_phone" name="store_phone" value="<?= htmlspecialchars($s['store_phone']) ?>" placeholder="e.g. +63 912 345 6789">
        </div>
        <div class="ss-field">
          <label class="ss-label" for="store_email">Store Email</label>
          <input class="form-input" id="store_email" type="email" name="store_email" value="<?= htmlspecialchars($s['store_email']) ?>" placeholder="e.g. store@example.com">
        </div>
      </div>
    </div>
  </div>

  <!-- Card 2: System Configuration -->
  <div class="ss-card">
    <div class="ss-card-body">
      <div class="ss-heading">System Configuration</div>

      <div class="ss-grid-3">
        <div class="ss-field">
          <label class="ss-label" for="timezone">Timezone</label>
          <select class="form-select" id="timezone" name="timezone">
            <?php foreach (['Asia/Manila','Asia/Singapore','Asia/Tokyo','Asia/Shanghai','America/New_York','Europe/London','UTC'] as $tz): ?>
              <option value="<?= $tz ?>" <?= $s['timezone'] === $tz ? 'selected' : '' ?>><?= $tz ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="ss-field">
          <label class="ss-label" for="system_status">System Status</label>
          <select class="form-select" id="system_status" name="system_status">
            <option value="online" <?= $s['system_status']==='online'?'selected':'' ?>>&#128994; Online</option>
            <option value="offline" <?= $s['system_status']==='offline'?'selected':'' ?>>&#128308; Offline</option>
            <option value="maintenance" <?= $s['system_status']==='maintenance'?'selected':'' ?>>&#128992; Maintenance</option>
          </select>
        </div>
        <div class="ss-field">
          <label class="ss-label" for="auto_logout_hours">Staff Auto-Logout</label>
          <div class="ss-inline">
            <input class="form-input" type="number" id="auto_logout_hours" name="auto_logout_hours" value="<?= (int)$s['auto_logout_hours'] ?>" min="1" max="24">
            <span class="ss-unit">hours</span>
          </div>
          <span class="ss-hint">Sessions exceeding this are flagged suspicious</span>
        </div>
      </div>

      <hr class="ss-divider">

      <div class="ss-toggle-row">
        <div class="ss-toggle-text">
          <span class="ss-toggle-name">Force Dark Mode</span>
          <span class="ss-toggle-desc">Override all user preferences and force dark mode system-wide</span>
        </div>
        <span class="toggle-switch"><input type="checkbox" name="force_dark_mode" value="1" <?= $s['force_dark_mode']==='1'?'checked':'' ?>><span class="toggle-slider"></span></span>
      </div>
      <div class="ss-toggle-row">
        <div class="ss-toggle-text">
          <span class="ss-toggle-name">Disable No-Login Orders</span>
          <span class="ss-toggle-desc">Prevent guest orders from the kiosk page</span>
        </div>
        <span class="toggle-switch"><input type="checkbox" name="disable_nologin_orders" value="1" <?= $s['disable_nologin_orders']==='1'?'checked':'' ?>><span class="toggle-slider"></span></span>
      </div>
      <div class="ss-toggle-row">
        <div class="ss-toggle-text">
          <span class="ss-toggle-name">Enable Online Payment</span>
          <span class="ss-toggle-desc">Placeholder for future payment integration</span>
        </div>
        <span class="toggle-switch"><input type="checkbox" name="online_payment" value="1" <?= $s['online_payment']==='1'?'checked':'' ?>><span class="toggle-slider"></span></span>
      </div>
    </div>
  </div>

  <!-- Card 3: Inventory Defaults -->
  <div class="ss-card">
    <div class="ss-card-body">
      <div class="ss-heading">Inventory Defaults</div>

      <div class="ss-defaults-grid">
        <div class="ss-defaults-col">
          <div class="ss-col-title">Item Categories</div>
          <div class="ss-tags-area">
            <div id="cat-tags" class="ss-tag-cloud">
              <?php foreach ($categories as $cat): ?>
                <span class="tag"><?= htmlspecialchars($cat['category_name']) ?><button type="button" class="tag-remove" onclick="this.parentElement.remove()">&times;</button></span>
              <?php endforeach; ?>
            </div>
            <div class="ss-tag-input">
              <input class="form-input" type="text" id="cat-input" placeholder="Add category...">
              <button type="button" class="btn btn-outline btn-sm" id="cat-add-btn">Add</button>
            </div>
          </div>
        </div>
        <div class="ss-defaults-col">
          <div class="ss-col-title">Item Names</div>
          <div class="ss-tags-area">
            <div id="name-tags" class="ss-tag-cloud">
              <?php foreach ($defaultNames as $dn): ?>
                <span class="tag"><?= htmlspecialchars($dn['item_name']) ?><button type="button" class="tag-remove" onclick="this.parentElement.remove()">&times;</button></span>
              <?php endforeach; ?>
            </div>
            <div class="ss-tag-input">
              <input class="form-input" type="text" id="name-input" placeholder="Add item name...">
              <button type="button" class="btn btn-outline btn-sm" id="name-add-btn">Add</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Save Bar -->
  <div class="ss-save-bar">
    <span class="ss-save-hint">Changes take effect immediately after saving</span>
    <button type="submit" class="btn btn-primary">Save Settings</button>
  </div>

</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const saveBar = document.querySelector('.ss-save-bar');
  const form = document.getElementById('settings-form');

  // Snapshot initial state
  function getFormSnapshot() {
    const data = {};
    form.querySelectorAll('input[type="text"], input[type="email"], input[type="number"], select').forEach(el => {
      if (el.name) data[el.name] = el.value;
    });
    form.querySelectorAll('input[type="checkbox"]').forEach(el => {
      if (el.name) data[el.name] = el.checked ? '1' : '0';
    });
    data._catTags = Array.from(document.querySelectorAll('#cat-tags .tag')).map(t => t.firstChild.textContent.trim()).join(',');
    data._nameTags = Array.from(document.querySelectorAll('#name-tags .tag')).map(t => t.firstChild.textContent.trim()).join(',');
    return JSON.stringify(data);
  }

  const initialSnapshot = getFormSnapshot();

  function checkChanges() {
    const changed = getFormSnapshot() !== initialSnapshot;
    saveBar.classList.toggle('visible', changed);
  }

  // Listen to form field changes
  form.querySelectorAll('input, select').forEach(el => {
    el.addEventListener('input', checkChanges);
    el.addEventListener('change', checkChanges);
  });

  // Observe tag additions/removals
  const tagObserver = new MutationObserver(checkChanges);
  tagObserver.observe(document.getElementById('cat-tags'), { childList: true });
  tagObserver.observe(document.getElementById('name-tags'), { childList: true });

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
    tag.innerHTML = `${escapeHtml(val)}<button type="button" class="tag-remove" onclick="this.parentElement.remove()">&times;</button>`;
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
