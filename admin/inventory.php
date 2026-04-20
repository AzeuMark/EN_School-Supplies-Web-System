<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Inventory — Admin';
$activePage = 'inventory';

// Fetch categories for dropdown
try {
    $categories = $pdo->query("SELECT * FROM item_categories ORDER BY category_name")->fetchAll();
} catch (Exception $e) { $categories = []; }

// Fetch default item names
try {
    $defaultNames = $pdo->query("SELECT * FROM default_item_names ORDER BY item_name")->fetchAll();
} catch (Exception $e) { $defaultNames = []; }

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Inventory</h1>
  <p class="subtitle">Manage your store items</p>
</div>

<div class="toolbar">
  <div class="d-flex gap-2 align-center flex-wrap">
    <div class="search-box">
      <span class="search-icon">&#128269;</span>
      <input type="text" id="inv-search" placeholder="Search items...">
    </div>
    <select class="form-select" id="inv-cat-filter" style="width:auto;min-width:160px">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <div class="status-tabs">
      <button class="status-tab active" data-status="">All</button>
      <button class="status-tab" data-status="on_stock">On Stock</button>
      <button class="status-tab" data-status="low_stock">Low Stock</button>
      <button class="status-tab" data-status="no_stock">No Stock</button>
    </div>
  </div>
  <button class="btn btn-primary" onclick="openModal('add-item-modal')">+ Add Item</button>
</div>

<div class="table-wrapper" style="position:relative">
  <div id="inv-loading" class="loading-overlay" style="display:none"><div class="spinner"></div></div>
  <table class="data-table">
    <thead>
      <tr><th>Image</th><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Max Qty</th><th>Status</th><th>Actions</th></tr>
    </thead>
    <tbody id="inv-tbody"></tbody>
  </table>
</div>
<div id="inv-pagination"></div>

<!-- Add Item Modal -->
<div class="modal-overlay" id="add-item-modal">
  <div class="modal">
    <div class="modal-header"><h3>Add Item</h3><button class="modal-close" onclick="closeModal('add-item-modal')">&times;</button></div>
    <div class="modal-body">
      <form id="add-item-form" enctype="multipart/form-data">
        <div class="form-group">
          <label class="form-label">Item Name</label>
          <select class="form-select" id="add-name-select">
            <option value="">-- Select or type custom --</option>
            <?php foreach ($defaultNames as $dn): ?>
              <option value="<?= htmlspecialchars($dn['item_name']) ?>"><?= htmlspecialchars($dn['item_name']) ?></option>
            <?php endforeach; ?>
            <option value="__custom__">Custom name...</option>
          </select>
          <input class="form-input mt-2" type="text" name="item_name" id="add-item-name" placeholder="Item name" required style="display:none">
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select class="form-select" name="category_id">
            <option value="">None</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem">
          <div class="form-group"><label class="form-label">Price (₱)</label><input class="form-input" type="number" name="price" step="0.01" min="0.01" required></div>
          <div class="form-group"><label class="form-label">Stock</label><input class="form-input" type="number" name="stock_count" min="0" value="0" required></div>
          <div class="form-group"><label class="form-label">Max Order Qty</label><input class="form-input" type="number" name="max_order_qty" min="1" value="10" required></div>
        </div>
        <div class="form-group">
          <label class="form-label">Image</label>
          <input class="form-input" type="file" name="item_image" accept="image/jpeg,image/png">
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('add-item-modal')">Cancel</button>
      <button class="btn btn-primary" id="add-item-submit">Add Item</button>
    </div>
  </div>
</div>

<!-- Edit Item Modal -->
<div class="modal-overlay" id="edit-item-modal">
  <div class="modal">
    <div class="modal-header"><h3>Edit Item</h3><button class="modal-close" onclick="closeModal('edit-item-modal')">&times;</button></div>
    <div class="modal-body">
      <form id="edit-item-form" enctype="multipart/form-data">
        <input type="hidden" name="item_id" id="edit-item-id">
        <div class="form-group"><label class="form-label">Item Name</label><input class="form-input" name="item_name" id="edit-item-name" required></div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <select class="form-select" name="category_id" id="edit-category">
            <option value="">None</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem">
          <div class="form-group"><label class="form-label">Price (₱)</label><input class="form-input" type="number" name="price" id="edit-price" step="0.01" min="0.01" required></div>
          <div class="form-group"><label class="form-label">Stock</label><input class="form-input" type="number" name="stock_count" id="edit-stock" min="0" required></div>
          <div class="form-group"><label class="form-label">Max Order Qty</label><input class="form-input" type="number" name="max_order_qty" id="edit-max-qty" min="1" required></div>
        </div>
        <div class="form-group"><label class="form-label">New Image</label><input class="form-input" type="file" name="item_image" accept="image/jpeg,image/png"></div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('edit-item-modal')">Cancel</button>
      <button class="btn btn-primary" id="edit-item-submit">Save</button>
    </div>
  </div>
</div>

<!-- Add Stock Modal -->
<div class="modal-overlay" id="add-stock-modal">
  <div class="modal" style="max-width:360px">
    <div class="modal-header"><h3>Add Stock</h3><button class="modal-close" onclick="closeModal('add-stock-modal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="stock-item-id">
      <p class="mb-3" id="stock-item-name" style="font-weight:600"></p>
      <div class="form-group"><label class="form-label">Quantity to add</label><input class="form-input" type="number" id="stock-qty" min="1" value="1"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('add-stock-modal')">Cancel</button>
      <button class="btn btn-primary" id="add-stock-submit">Add</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let currentPage = 1;
  let currentSearch = '';
  let currentCategory = '';
  let currentStatus = '';

  async function loadItems() {
    document.getElementById('inv-loading').style.display = 'flex';
    const params = new URLSearchParams({ page: currentPage, per_page: 15, search: currentSearch, category: currentCategory, status: currentStatus });
    try {
      const data = await apiFetch('api/inventory/get_items.php?' + params, { method: 'GET' });
      renderTable(data.items || []);
      Pagination.render({ container: '#inv-pagination', currentPage: data.page, totalPages: data.total_pages, onPageChange: (p) => { currentPage = p; loadItems(); } });
    } catch(e) {}
    document.getElementById('inv-loading').style.display = 'none';
  }

  function renderTable(items) {
    const tbody = document.getElementById('inv-tbody');
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted" style="padding:2rem">No items found</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(i => {
      const statusClass = i.item_status === 'No Stock' ? 'no-stock' : i.item_status === 'Low Stock' ? 'low-stock' : 'on-stock';
      return `<tr>
        <td>${i.item_image ? `<img src="${getBasePath() + i.item_image}" style="width:40px;height:40px;object-fit:cover;border-radius:6px">` : '<span style="font-size:1.5rem">&#128218;</span>'}</td>
        <td>${i.id}</td>
        <td><strong>${escapeHtml(i.item_name)}</strong></td>
        <td>${escapeHtml(i.category_name || '—')}</td>
        <td>${formatPrice(i.price)}</td>
        <td>${i.stock_count}</td>
        <td>${i.max_order_qty}</td>
        <td><span class="badge badge-${statusClass}">${i.item_status}</span></td>
        <td><div class="actions">
          <button class="btn btn-icon btn-sm btn-ghost edit-inv" data-item='${JSON.stringify(i).replace(/'/g,"&#39;")}' title="Edit">&#9998;</button>
          <button class="btn btn-icon btn-sm btn-ghost stock-inv" data-id="${i.id}" data-name="${escapeHtml(i.item_name)}" title="Add Stock">&#10133;</button>
          <button class="btn btn-icon btn-sm btn-ghost text-danger del-inv" data-id="${i.id}" title="Delete">&#128465;</button>
        </div></td>
      </tr>`;
    }).join('');
    bindTableActions();
  }

  function bindTableActions() {
    document.querySelectorAll('.edit-inv').forEach(btn => {
      btn.addEventListener('click', () => {
        const i = JSON.parse(btn.dataset.item);
        document.getElementById('edit-item-id').value = i.id;
        document.getElementById('edit-item-name').value = i.item_name;
        document.getElementById('edit-category').value = i.category_id || '';
        document.getElementById('edit-price').value = i.price;
        document.getElementById('edit-stock').value = i.stock_count;
        document.getElementById('edit-max-qty').value = i.max_order_qty;
        openModal('edit-item-modal');
      });
    });
    document.querySelectorAll('.stock-inv').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('stock-item-id').value = btn.dataset.id;
        document.getElementById('stock-item-name').textContent = btn.dataset.name;
        document.getElementById('stock-qty').value = 1;
        openModal('add-stock-modal');
      });
    });
    document.querySelectorAll('.del-inv').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm('Delete this item?')) return;
        try {
          const data = await apiFetch('api/inventory/delete_item.php', { body: JSON.stringify({ item_id: btn.dataset.id, csrf_token: getCSRFToken() }) });
          if (data.success) { Toast.success(data.message); loadItems(); }
        } catch(e) {}
      });
    });
  }

  // Search & filters
  let searchTimer;
  document.getElementById('inv-search').addEventListener('input', (e) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentSearch = e.target.value; currentPage = 1; loadItems(); }, 300);
  });
  document.getElementById('inv-cat-filter').addEventListener('change', (e) => { currentCategory = e.target.value; currentPage = 1; loadItems(); });
  document.querySelectorAll('.status-tabs .status-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.status-tabs .status-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      currentStatus = tab.dataset.status;
      currentPage = 1;
      loadItems();
    });
  });

  // Add item name select
  const nameSelect = document.getElementById('add-name-select');
  const nameInput = document.getElementById('add-item-name');
  nameSelect.addEventListener('change', () => {
    if (nameSelect.value === '__custom__') {
      nameInput.style.display = '';
      nameInput.value = '';
      nameInput.focus();
    } else {
      nameInput.style.display = 'none';
      nameInput.value = nameSelect.value;
    }
  });

  // Add Item
  document.getElementById('add-item-submit').addEventListener('click', async () => {
    if (nameSelect.value && nameSelect.value !== '__custom__') nameInput.value = nameSelect.value;
    const form = document.getElementById('add-item-form');
    const fd = new FormData(form);
    fd.append('csrf_token', getCSRFToken());
    try {
      const data = await apiFetch('api/inventory/add_item.php', { body: fd });
      if (data.success) { Toast.success(data.message); closeModal('add-item-modal'); form.reset(); nameInput.style.display = 'none'; loadItems(); }
    } catch(e) {}
  });

  // Edit Item
  document.getElementById('edit-item-submit').addEventListener('click', async () => {
    const form = document.getElementById('edit-item-form');
    const fd = new FormData(form);
    fd.append('csrf_token', getCSRFToken());
    try {
      const data = await apiFetch('api/inventory/edit_item.php', { body: fd });
      if (data.success) { Toast.success(data.message); closeModal('edit-item-modal'); loadItems(); }
    } catch(e) {}
  });

  // Add Stock
  document.getElementById('add-stock-submit').addEventListener('click', async () => {
    const itemId = document.getElementById('stock-item-id').value;
    const qty = document.getElementById('stock-qty').value;
    try {
      const data = await apiFetch('api/inventory/add_stock.php', { body: JSON.stringify({ item_id: itemId, quantity: parseInt(qty), csrf_token: getCSRFToken() }) });
      if (data.success) { Toast.success(data.message); closeModal('add-stock-modal'); loadItems(); }
    } catch(e) {}
  });

  loadItems();
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
