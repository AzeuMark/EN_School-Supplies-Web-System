/**
 * E&N School Supplies — Kiosk Page Interactions
 */

const Kiosk = (() => {
  let allItems = [];
  let cart = {}; // { itemId: { item, qty } }
  let currentPage = 1;
  const perPage = 20;

  async function init() {
    await loadItems();
    bindEvents();
  }

  async function loadItems() {
    try {
      const data = await apiFetch('api/inventory/get_items.php?all=1', { method: 'GET' });
      if (data.success) {
        allItems = data.items || [];
        renderPage();
      }
    } catch (e) {
      document.getElementById('kiosk-loading').innerHTML =
        '<p class="text-muted">Failed to load items. Please refresh.</p>';
    }
  }

  function renderPage() {
    const grid = document.getElementById('kiosk-grid');
    const loading = document.getElementById('kiosk-loading');
    if (loading) loading.style.display = 'none';
    grid.style.display = '';

    const start = (currentPage - 1) * perPage;
    const pageItems = allItems.slice(start, start + perPage);
    const totalPages = Math.ceil(allItems.length / perPage);

    grid.innerHTML = '';

    if (pageItems.length === 0) {
      grid.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128230;</div><div class="empty-text">No items available</div></div>';
      return;
    }

    pageItems.forEach(item => {
      const inCart = cart[item.id];
      const qty = inCart ? inCart.qty : 0;
      const noStock = parseInt(item.stock_count) <= 0;

      const card = document.createElement('div');
      card.className = 'kiosk-item' + (qty > 0 ? ' selected' : '') + (noStock ? ' no-stock' : '');
      card.dataset.id = item.id;

      card.innerHTML = `
        <div class="item-image">
          ${item.item_image
            ? `<img src="${item.item_image}" alt="${escapeHtml(item.item_name)}" style="width:100%;height:100%;object-fit:cover">`
            : '&#128218;'}
        </div>
        ${noStock ? '<span class="no-stock-label">Out of Stock</span>' : ''}
        <div class="item-details">
          <div class="item-name">${escapeHtml(item.item_name)}</div>
          <div class="item-category">${escapeHtml(item.category_name || 'Uncategorized')}</div>
          <div class="item-price">${formatPrice(item.price)}</div>
          <div class="item-stock">${item.stock_count} in stock</div>
        </div>
        ${!noStock ? `
        <div class="item-qty-control">
          <div class="qty-stepper">
            <button type="button" class="qty-minus" data-id="${item.id}">−</button>
            <input type="number" class="qty-input" data-id="${item.id}" value="${qty}" min="0" max="${Math.min(item.max_order_qty, item.stock_count)}" readonly>
            <button type="button" class="qty-plus" data-id="${item.id}">+</button>
          </div>
        </div>` : ''}
      `;

      grid.appendChild(card);
    });

    Pagination.render({
      container: '#kiosk-pagination',
      currentPage: currentPage,
      totalPages: totalPages,
      onPageChange: (page) => { currentPage = page; renderPage(); }
    });
  }

  function bindEvents() {
    // Qty buttons via delegation
    document.addEventListener('click', (e) => {
      const plus = e.target.closest('.qty-plus');
      const minus = e.target.closest('.qty-minus');

      if (plus) {
        const id = plus.dataset.id;
        changeQty(id, 1);
      } else if (minus) {
        const id = minus.dataset.id;
        changeQty(id, -1);
      }
    });

    // Cart button
    document.getElementById('cart-btn')?.addEventListener('click', showConfirmModal);

    // Place order button
    document.getElementById('place-order-btn')?.addEventListener('click', showConfirmModal);

    // Confirm order
    document.getElementById('confirm-order-btn')?.addEventListener('click', submitOrder);
  }

  function changeQty(itemId, delta) {
    const item = allItems.find(i => String(i.id) === String(itemId));
    if (!item) return;

    const maxQty = Math.min(parseInt(item.max_order_qty), parseInt(item.stock_count));
    let current = cart[itemId] ? cart[itemId].qty : 0;
    let newQty = Math.max(0, Math.min(maxQty, current + delta));

    if (newQty === 0) {
      delete cart[itemId];
    } else {
      cart[itemId] = { item, qty: newQty };
    }

    // Update input display
    const input = document.querySelector(`.qty-input[data-id="${itemId}"]`);
    if (input) input.value = newQty;

    // Update card selection
    const card = document.querySelector(`.kiosk-item[data-id="${itemId}"]`);
    if (card) {
      card.classList.toggle('selected', newQty > 0);
    }

    updateSummary();
  }

  function updateSummary() {
    let totalItems = 0;
    let totalPrice = 0;

    Object.values(cart).forEach(({ item, qty }) => {
      totalItems += qty;
      totalPrice += qty * parseFloat(item.price);
    });

    document.getElementById('cart-count').textContent = totalItems;
    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-amount').textContent = formatPrice(totalPrice);
    document.getElementById('place-order-btn').disabled = totalItems === 0;
  }

  function showConfirmModal() {
    const entries = Object.values(cart);
    if (entries.length === 0) {
      Toast.warning('Your cart is empty.');
      return;
    }

    const list = document.getElementById('confirm-list');
    list.innerHTML = '';

    let total = 0;
    entries.forEach(({ item, qty }) => {
      const subtotal = qty * parseFloat(item.price);
      total += subtotal;

      const row = document.createElement('div');
      row.className = 'kiosk-confirm-item';
      row.innerHTML = `
        <div class="item-info">
          <div class="name">${escapeHtml(item.item_name)}</div>
          <div class="detail">${qty} × ${formatPrice(item.price)}</div>
        </div>
        <span class="item-subtotal">${formatPrice(subtotal)}</span>
        <button class="remove-btn" data-id="${item.id}" title="Remove">&times;</button>
      `;
      list.appendChild(row);
    });

    // Remove item buttons
    list.querySelectorAll('.remove-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        delete cart[id];
        // Reset qty input
        const input = document.querySelector(`.qty-input[data-id="${id}"]`);
        if (input) input.value = 0;
        const card = document.querySelector(`.kiosk-item[data-id="${id}"]`);
        if (card) card.classList.remove('selected');
        updateSummary();

        if (Object.keys(cart).length === 0) {
          closeModal('confirm-modal');
        } else {
          showConfirmModal();
        }
      });
    });

    document.getElementById('confirm-total').textContent = formatPrice(total);
    openModal('confirm-modal');
  }

  async function submitOrder() {
    const btn = document.getElementById('confirm-order-btn');
    if (!preventDoubleClick(btn)) return;

    const name = document.getElementById('guest-name').value.trim();
    const phone = document.getElementById('guest-phone').value.trim();
    const note = document.getElementById('guest-note').value.trim();

    if (!name || !phone) {
      Toast.error('Name and phone are required.');
      btn.disabled = false;
      btn.textContent = 'Confirm Order';
      btn.dataset.processing = 'false';
      return;
    }

    const items = Object.values(cart).map(({ item, qty }) => ({
      item_id: item.id,
      quantity: qty
    }));

    try {
      const data = await apiFetch('api/orders/create.php', {
        body: JSON.stringify({
          csrf_token: getCSRFToken(),
          guest_name: name,
          guest_phone: phone,
          guest_note: note,
          items: items
        })
      });

      if (data.success) {
        closeModal('confirm-modal');
        showReceipt(data.order);
        cart = {};
        updateSummary();
        renderPage();
      }
    } catch (e) {
      btn.disabled = false;
      btn.textContent = 'Confirm Order';
      btn.dataset.processing = 'false';
    }
  }

  function showReceipt(order) {
    const content = document.getElementById('receipt-content');
    let itemsHtml = '';
    (order.items || []).forEach(item => {
      itemsHtml += `
        <tr>
          <td>${escapeHtml(item.item_name_snapshot)}</td>
          <td style="text-align:center">${item.quantity}</td>
          <td style="text-align:right">${formatPrice(item.unit_price)}</td>
          <td style="text-align:right">${formatPrice(item.quantity * item.unit_price)}</td>
        </tr>`;
    });

    content.innerHTML = `
      <div style="text-align:center;margin-bottom:1rem">
        <h3 style="color:var(--primary);font-size:1.1rem">Order ${escapeHtml(order.order_code)}</h3>
        <p class="text-muted" style="font-size:0.82rem">${order.created_at}</p>
        <p style="font-size:0.9rem">Guest: <strong>${escapeHtml(order.guest_name)}</strong></p>
      </div>
      <table class="data-table" style="font-size:0.85rem">
        <thead><tr><th>Item</th><th style="text-align:center">Qty</th><th style="text-align:right">Price</th><th style="text-align:right">Subtotal</th></tr></thead>
        <tbody>${itemsHtml}</tbody>
      </table>
      <div class="kiosk-total-row" style="margin-top:0.75rem">
        <span>Total</span>
        <span class="total-value">${formatPrice(order.total_price)}</span>
      </div>
      <p style="text-align:center;margin-top:1rem;font-size:0.82rem;color:var(--text-muted)">
        Thank you for shopping at E&N School Supplies!
      </p>
    `;

    // PDF link
    const pdfBtn = document.getElementById('receipt-pdf-btn');
    if (pdfBtn) pdfBtn.href = 'receipt.php?order_id=' + order.id + '&format=pdf';

    // Print
    const printBtn = document.getElementById('receipt-print-btn');
    if (printBtn) {
      printBtn.onclick = () => {
        const w = window.open('receipt.php?order_id=' + order.id + '&format=html', '_blank');
        if (w) w.onload = () => w.print();
      };
    }

    openModal('receipt-modal');
  }

  return { init };
})();

document.addEventListener('DOMContentLoaded', Kiosk.init);
