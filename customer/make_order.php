<?php
require_once __DIR__ . '/../includes/auth.php';
require_customer();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Make Order — Customer';
$activePage = 'make_order';
$extraCss = ['assets/css/kiosk.css'];

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Make Order</h1>
  <p class="subtitle">Browse and add items to your cart</p>
</div>

<?php if (is_maintenance()): ?>
  <div id="page-error" class="show" style="display:block">System is in maintenance mode. Ordering may be limited.</div>
<?php endif; ?>

<!-- Item Grid -->
<div id="order-loading" style="text-align:center;padding:2rem"><div class="spinner" style="margin:0 auto"></div></div>
<div class="kiosk-grid" id="order-grid" style="display:none"></div>

<!-- Cart Summary -->
<div style="position:sticky;bottom:0;background:var(--surface);border-top:1px solid var(--border);padding:1rem;display:flex;justify-content:space-between;align-items:center;margin-top:1rem;border-radius:var(--radius-md)">
  <div>
    Items: <strong id="co-total-items">0</strong> |
    Total: <strong style="color:var(--primary)" id="co-total-amount">₱0.00</strong>
  </div>
  <button class="btn btn-primary" id="co-place-btn" disabled>Place Order</button>
</div>

<!-- Confirm Modal -->
<div class="modal-overlay" id="co-confirm-modal">
  <div class="modal" style="max-width:540px">
    <div class="modal-header"><h3>Confirm Order</h3><button class="modal-close" onclick="closeModal('co-confirm-modal')">&times;</button></div>
    <div class="modal-body">
      <div class="kiosk-confirm-list" id="co-confirm-list"></div>
      <div class="kiosk-total-row">
        <span>Grand Total</span>
        <span class="total-value" id="co-confirm-total">₱0.00</span>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('co-confirm-modal')">Close</button>
      <button class="btn btn-primary" id="co-confirm-btn">Confirm Order</button>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="co-receipt-modal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header"><h3>&#9989; Order Placed!</h3><button class="modal-close" onclick="closeModal('co-receipt-modal'); location.reload();">&times;</button></div>
    <div class="modal-body" id="co-receipt-content"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" id="co-print-btn">&#128424; Print</button>
      <a class="btn btn-primary" id="co-pdf-btn" target="_blank">&#128196; PDF</a>
      <button class="btn btn-accent" onclick="closeModal('co-receipt-modal'); location.reload();">Done</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let allItems = [];
  let cart = {};

  async function loadItems() {
    try {
      const data = await apiFetch('api/inventory/get_items.php?all=1', { method: 'GET' });
      if (data.success) { allItems = data.items || []; renderGrid(); }
    } catch(e) {
      document.getElementById('order-loading').innerHTML = '<p class="text-muted">Failed to load items.</p>';
    }
  }

  function renderGrid() {
    document.getElementById('order-loading').style.display = 'none';
    const grid = document.getElementById('order-grid');
    grid.style.display = '';
    grid.innerHTML = '';

    if (!allItems.length) {
      grid.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128230;</div><div class="empty-text">No items available</div></div>';
      return;
    }

    allItems.forEach(item => {
      const inCart = cart[item.id];
      const qty = inCart ? inCart.qty : 0;
      const noStock = parseInt(item.stock_count) <= 0;

      const card = document.createElement('div');
      card.className = 'kiosk-item' + (qty > 0 ? ' selected' : '') + (noStock ? ' no-stock' : '');
      card.dataset.id = item.id;
      card.innerHTML = `
        <div class="item-image">${item.item_image ? `<img src="${getBasePath() + item.item_image}" style="width:100%;height:100%;object-fit:cover">` : '&#128218;'}</div>
        ${noStock ? '<span class="no-stock-label">Out of Stock</span>' : ''}
        <div class="item-details">
          <div class="item-name">${escapeHtml(item.item_name)}</div>
          <div class="item-category">${escapeHtml(item.category_name || 'Uncategorized')}</div>
          <div class="item-price">${formatPrice(item.price)}</div>
          <div class="item-stock">${item.stock_count} in stock</div>
        </div>
        ${!noStock ? `<div class="item-qty-control"><div class="qty-stepper">
          <button type="button" class="qty-minus" data-id="${item.id}">−</button>
          <input type="number" class="qty-input" data-id="${item.id}" value="${qty}" min="0" max="${Math.min(item.max_order_qty, item.stock_count)}" readonly>
          <button type="button" class="qty-plus" data-id="${item.id}">+</button>
        </div></div>` : ''}`;
      grid.appendChild(card);
    });
  }

  document.addEventListener('click', (e) => {
    const plus = e.target.closest('.qty-plus');
    const minus = e.target.closest('.qty-minus');
    if (plus) changeQty(plus.dataset.id, 1);
    else if (minus) changeQty(minus.dataset.id, -1);
  });

  function changeQty(id, delta) {
    const item = allItems.find(i => String(i.id) === String(id));
    if (!item) return;
    const max = Math.min(parseInt(item.max_order_qty), parseInt(item.stock_count));
    let cur = cart[id] ? cart[id].qty : 0;
    let nq = Math.max(0, Math.min(max, cur + delta));
    if (nq === 0) delete cart[id]; else cart[id] = { item, qty: nq };
    const input = document.querySelector(`.qty-input[data-id="${id}"]`);
    if (input) input.value = nq;
    const card = document.querySelector(`.kiosk-item[data-id="${id}"]`);
    if (card) card.classList.toggle('selected', nq > 0);
    updateSummary();
  }

  function updateSummary() {
    let ti = 0, tp = 0;
    Object.values(cart).forEach(({ item, qty }) => { ti += qty; tp += qty * parseFloat(item.price); });
    document.getElementById('co-total-items').textContent = ti;
    document.getElementById('co-total-amount').textContent = formatPrice(tp);
    document.getElementById('co-place-btn').disabled = ti === 0;
  }

  document.getElementById('co-place-btn').addEventListener('click', () => {
    const entries = Object.values(cart);
    if (!entries.length) { Toast.warning('Cart is empty.'); return; }
    const list = document.getElementById('co-confirm-list');
    list.innerHTML = '';
    let total = 0;
    entries.forEach(({ item, qty }) => {
      const sub = qty * parseFloat(item.price);
      total += sub;
      const row = document.createElement('div');
      row.className = 'kiosk-confirm-item';
      row.innerHTML = `<div class="item-info"><div class="name">${escapeHtml(item.item_name)}</div><div class="detail">${qty} × ${formatPrice(item.price)}</div></div><span class="item-subtotal">${formatPrice(sub)}</span>
        <button class="remove-btn" data-id="${item.id}">&times;</button>`;
      list.appendChild(row);
    });
    list.querySelectorAll('.remove-btn').forEach(b => b.addEventListener('click', () => {
      delete cart[b.dataset.id];
      const inp = document.querySelector(`.qty-input[data-id="${b.dataset.id}"]`);
      if (inp) inp.value = 0;
      const c = document.querySelector(`.kiosk-item[data-id="${b.dataset.id}"]`);
      if (c) c.classList.remove('selected');
      updateSummary();
      if (!Object.keys(cart).length) closeModal('co-confirm-modal');
      else document.getElementById('co-place-btn').click();
    }));
    document.getElementById('co-confirm-total').textContent = formatPrice(total);
    openModal('co-confirm-modal');
  });

  document.getElementById('co-confirm-btn').addEventListener('click', async () => {
    const btn = document.getElementById('co-confirm-btn');
    if (!preventDoubleClick(btn)) return;
    const items = Object.values(cart).map(({ item, qty }) => ({ item_id: item.id, quantity: qty }));
    try {
      const data = await apiFetch('api/orders/create.php', { body: JSON.stringify({ csrf_token: getCSRFToken(), items }) });
      if (data.success) {
        closeModal('co-confirm-modal');
        showReceipt(data.order);
        cart = {};
        updateSummary();
        renderGrid();
      }
    } catch(e) { btn.disabled = false; btn.textContent = 'Confirm Order'; btn.dataset.processing = 'false'; }
  });

  function showReceipt(order) {
    const c = document.getElementById('co-receipt-content');
    let ih = '';
    (order.items || []).forEach(i => { ih += `<tr><td>${escapeHtml(i.item_name_snapshot)}</td><td style="text-align:center">${i.quantity}</td><td style="text-align:right">${formatPrice(i.unit_price)}</td><td style="text-align:right">${formatPrice(i.quantity*i.unit_price)}</td></tr>`; });
    c.innerHTML = `<div style="text-align:center;margin-bottom:1rem"><h3 style="color:var(--primary)">${escapeHtml(order.order_code)}</h3><p class="text-muted" style="font-size:0.82rem">${order.created_at}</p></div>
      <table class="data-table" style="font-size:0.85rem"><thead><tr><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Subtotal</th></tr></thead><tbody>${ih}</tbody></table>
      <div class="kiosk-total-row" style="margin-top:0.75rem"><span>Total</span><span class="total-value">${formatPrice(order.total_price)}</span></div>`;
    const pdf = document.getElementById('co-pdf-btn');
    if (pdf) pdf.href = getBasePath() + 'receipt.php?order_id=' + order.id + '&format=pdf';
    const pr = document.getElementById('co-print-btn');
    if (pr) pr.onclick = () => { const w = window.open(getBasePath()+'receipt.php?order_id='+order.id+'&format=html','_blank'); if(w) w.onload=()=>w.print(); };
    openModal('co-receipt-modal');
  }

  loadItems();
});
</script>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
