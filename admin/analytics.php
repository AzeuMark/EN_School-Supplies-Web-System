<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
enforce_system_status();

$user = get_current_user_data();
$badges = get_sidebar_badges();
$pageTitle = 'Analytics — Admin';
$activePage = 'analytics';

include __DIR__ . '/../includes/layout_header.php';
?>

<div class="page-header">
  <h1>Analytics</h1>
  <p class="subtitle">Store performance overview</p>
</div>

<!-- Date Range Filter -->
<div class="toolbar">
  <div class="status-tabs">
    <button class="status-tab active" data-range="month">Last 30 Days</button>
    <button class="status-tab" data-range="week">Last 7 Days</button>
    <button class="status-tab" data-range="today">Today</button>
  </div>
  <div class="d-flex gap-2 align-center">
    <input type="date" class="form-input" id="date-from" style="width:auto">
    <span class="text-muted">to</span>
    <input type="date" class="form-input" id="date-to" style="width:auto">
    <button class="btn btn-outline btn-sm" id="custom-range-btn">Apply</button>
  </div>
</div>

<div id="analytics-loading" style="text-align:center;padding:2rem"><div class="spinner" style="margin:0 auto"></div></div>

<div id="analytics-content" style="display:none">
  <!-- Row 1: Summary Cards -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem">
    <div class="stat-card"><div class="stat-icon">&#128230;</div><div class="stat-info"><div class="stat-value" id="a-total-orders">0</div><div class="stat-label">Total Orders</div></div></div>
    <div class="stat-card"><div class="stat-icon">&#128176;</div><div class="stat-info"><div class="stat-value" id="a-total-revenue">₱0</div><div class="stat-label">Total Revenue</div></div></div>
    <div class="stat-card"><div class="stat-icon">&#128101;</div><div class="stat-info"><div class="stat-value" id="a-new-customers">0</div><div class="stat-label">New Customers</div></div></div>
  </div>

  <!-- Row 2: Charts -->
  <div class="analytics-grid-2" style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
    <!-- Orders Over Time -->
    <div class="card">
      <div class="card-header">Orders Over Time</div>
      <div class="card-body" id="chart-orders" style="min-height:160px"></div>
    </div>
    <!-- Top Selling Items -->
    <div class="card">
      <div class="card-header">Top Selling Items</div>
      <div class="card-body" id="chart-top-items" style="min-height:160px"></div>
    </div>
  </div>

  <div class="analytics-grid-3" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem;margin-bottom:1.5rem">
    <!-- Status Breakdown -->
    <div class="card">
      <div class="card-header">Orders by Status</div>
      <div class="card-body" id="chart-status"></div>
    </div>
    <!-- By Category -->
    <div class="card">
      <div class="card-header">Orders by Category</div>
      <div class="card-body" id="chart-category"></div>
    </div>
    <!-- Guest vs Registered -->
    <div class="card">
      <div class="card-header">Guest vs Registered</div>
      <div class="card-body" id="chart-guest"></div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  let currentRange = 'month';

  async function loadAnalytics(range, from, to) {
    document.getElementById('analytics-loading').style.display = '';
    document.getElementById('analytics-content').style.display = 'none';
    const params = new URLSearchParams({ range });
    if (from) params.set('from', from);
    if (to) params.set('to', to);

    try {
      const resp = await apiFetch('api/analytics/get_data.php?' + params, { method: 'GET' });
      if (resp.success) renderAnalytics(resp.data);
    } catch(e) {}

    document.getElementById('analytics-loading').style.display = 'none';
    document.getElementById('analytics-content').style.display = '';
  }

  function renderAnalytics(d) {
    // Summary
    const totalOrders = d.orders_over_time.reduce((s, r) => s + parseInt(r.count), 0);
    const totalRevenue = d.orders_over_time.reduce((s, r) => s + parseFloat(r.revenue), 0);
    const totalNewCust = d.new_customers.reduce((s, r) => s + parseInt(r.count), 0);
    document.getElementById('a-total-orders').textContent = totalOrders;
    document.getElementById('a-total-revenue').textContent = formatPrice(totalRevenue);
    document.getElementById('a-new-customers').textContent = totalNewCust;

    // Orders over time (bar chart)
    const ordersEl = document.getElementById('chart-orders');
    if (d.orders_over_time.length === 0) { ordersEl.innerHTML = '<p class="text-muted text-center">No data</p>'; }
    else {
      const maxO = Math.max(...d.orders_over_time.map(r => parseInt(r.count)));
      ordersEl.innerHTML = '<div style="display:flex;align-items:flex-end;gap:3px;height:130px">' +
        d.orders_over_time.map(r => {
          const h = maxO > 0 ? (parseInt(r.count)/maxO*100) : 0;
          return `<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px">
            <span style="font-size:0.65rem;color:var(--text-muted)">${r.count}</span>
            <div style="width:100%;background:var(--primary);border-radius:3px 3px 0 0;height:${h}%;min-height:3px"></div>
            <span style="font-size:0.6rem;color:var(--text-muted)">${new Date(r.day).toLocaleDateString('en',{month:'short',day:'numeric'})}</span>
          </div>`;
        }).join('') + '</div>';
    }

    // Top items
    const topEl = document.getElementById('chart-top-items');
    if (!d.top_items.length) { topEl.innerHTML = '<p class="text-muted text-center">No data</p>'; }
    else {
      const maxT = parseInt(d.top_items[0].total_qty) || 1;
      topEl.innerHTML = d.top_items.map(i => {
        const pct = (parseInt(i.total_qty)/maxT*100);
        return `<div style="margin-bottom:0.5rem"><div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:2px"><span>${escapeHtml(i.name)}</span><span class="text-muted">${i.total_qty}</span></div>
        <div style="height:6px;background:var(--border);border-radius:999px"><div style="width:${pct}%;height:100%;background:var(--accent);border-radius:999px"></div></div></div>`;
      }).join('');
    }

    // Status
    const statusEl = document.getElementById('chart-status');
    const colors = { pending: '#f9a825', ready: '#1565c0', claimed: '#2e7d32', cancelled: '#d32f2f' };
    if (!d.by_status.length) { statusEl.innerHTML = '<p class="text-muted text-center">No data</p>'; }
    else {
      statusEl.innerHTML = d.by_status.map(s => `<div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.4rem">
        <span style="width:12px;height:12px;border-radius:50%;background:${colors[s.status]||'#888'}"></span>
        <span style="flex:1;font-size:0.85rem">${s.status.charAt(0).toUpperCase()+s.status.slice(1)}</span>
        <strong style="font-size:0.85rem">${s.count}</strong>
      </div>`).join('');
    }

    // Category
    const catEl = document.getElementById('chart-category');
    if (!d.by_category.length) { catEl.innerHTML = '<p class="text-muted text-center">No data</p>'; }
    else {
      const maxC = parseInt(d.by_category[0].total_qty) || 1;
      catEl.innerHTML = d.by_category.map(c => {
        const pct = parseInt(c.total_qty)/maxC*100;
        return `<div style="margin-bottom:0.4rem"><div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:2px"><span>${escapeHtml(c.category)}</span><span class="text-muted">${c.total_qty}</span></div>
        <div style="height:6px;background:var(--border);border-radius:999px"><div style="width:${pct}%;height:100%;background:var(--primary);border-radius:999px"></div></div></div>`;
      }).join('');
    }

    // Guest vs Registered
    const gEl = document.getElementById('chart-guest');
    const guest = parseInt(d.guest_vs_registered?.guest || 0);
    const reg = parseInt(d.guest_vs_registered?.registered || 0);
    const total = guest + reg;
    if (total === 0) { gEl.innerHTML = '<p class="text-muted text-center">No data</p>'; }
    else {
      gEl.innerHTML = `<div style="display:flex;gap:1rem;justify-content:center;padding:1rem 0">
        <div style="text-align:center"><div style="font-size:1.5rem;font-weight:800;color:var(--primary)">${reg}</div><div style="font-size:0.8rem;color:var(--text-muted)">Registered</div></div>
        <div style="text-align:center"><div style="font-size:1.5rem;font-weight:800;color:var(--warning)">${guest}</div><div style="font-size:0.8rem;color:var(--text-muted)">Guest</div></div>
      </div>
      <div style="height:10px;background:var(--border);border-radius:999px;overflow:hidden;display:flex">
        <div style="width:${(reg/total*100)}%;background:var(--primary)"></div>
        <div style="width:${(guest/total*100)}%;background:var(--warning)"></div>
      </div>`;
    }
  }

  // Range tabs
  document.querySelectorAll('.status-tabs .status-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.status-tabs .status-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      currentRange = tab.dataset.range;
      loadAnalytics(currentRange);
    });
  });

  document.getElementById('custom-range-btn').addEventListener('click', () => {
    const from = document.getElementById('date-from').value;
    const to = document.getElementById('date-to').value;
    if (from && to) loadAnalytics('custom', from, to);
    else Toast.warning('Select both dates.');
  });

  loadAnalytics('month');
});
</script>

<style>
  @media (max-width: 768px) {
    .analytics-grid-2,
    .analytics-grid-3 { grid-template-columns: 1fr !important; }

    .toolbar {
      flex-direction: column;
      align-items: stretch;
      gap: 0.6rem;
    }
    .toolbar .status-tabs { flex-wrap: wrap; }
    .toolbar .d-flex.gap-2.align-center {
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .toolbar .d-flex.gap-2.align-center .form-input {
      flex: 1;
      min-width: 120px;
    }
  }
</style>

<?php include __DIR__ . '/../includes/layout_footer.php'; ?>
