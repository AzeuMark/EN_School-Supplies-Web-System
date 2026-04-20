<?php
/**
 * E&N School Supplies — Kiosk Page (Full-screen, no-login ordering)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$storeName  = get_setting('store_name', 'E&N School Supplies');
$logoPath   = get_setting('logo_path', 'assets/images/logo.png');
$forceDark  = get_setting('force_dark_mode', '0');
$disableNoLogin = get_setting('disable_nologin_orders', '0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Kiosk — <?= htmlspecialchars($storeName) ?></title>
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/kiosk.css">
</head>
<body data-base-path="" data-force-dark-mode="<?= $forceDark ?>" data-theme-preference="auto">

<?php if ($disableNoLogin === '1'): ?>
  <div class="kiosk-disabled">
    <div class="disabled-icon">&#128683;</div>
    <div class="disabled-title">Kiosk Ordering Disabled</div>
    <div class="disabled-text">No-login ordering is currently disabled. Please log in to place an order or visit the store counter.</div>
    <a href="login.php" class="btn btn-primary mt-4">Go to Login</a>
  </div>
<?php else: ?>

<div class="kiosk-page">
  <!-- Header -->
  <div class="kiosk-header">
    <div class="kiosk-brand">
      <img src="<?= $logoPath ?>" alt="Logo" onerror="this.style.display='none'">
      <div>
        <h1><?= htmlspecialchars($storeName) ?></h1>
        <div class="kiosk-subtitle">In-Store Ordering</div>
      </div>
    </div>
    <button class="kiosk-cart-btn" id="cart-btn">
      &#128722; Cart
      <span class="cart-count" id="cart-count">0</span>
    </button>
  </div>

  <!-- Content -->
  <div class="kiosk-content">
    <div id="kiosk-loading" style="text-align:center;padding:3rem"><div class="spinner" style="margin:0 auto"></div></div>
    <div class="kiosk-grid" id="kiosk-grid" style="display:none"></div>
    <div id="kiosk-pagination" style="margin-top:1rem"></div>
  </div>

  <!-- Footer -->
  <div class="kiosk-footer">
    <div class="order-summary">
      Items: <strong id="total-items">0</strong> |
      Total: <span class="total-amount" id="total-amount">₱0.00</span>
    </div>
    <button class="place-order-btn" id="place-order-btn" disabled>Place Order</button>
  </div>
</div>

<!-- Confirm Order Modal -->
<div class="modal-overlay" id="confirm-modal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <h3>Confirm Your Order</h3>
      <button class="modal-close" onclick="closeModal('confirm-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <div class="kiosk-confirm-list" id="confirm-list"></div>
      <div class="kiosk-total-row">
        <span>Grand Total</span>
        <span class="total-value" id="confirm-total">₱0.00</span>
      </div>

      <hr style="border:none;border-top:1px solid var(--border);margin:1rem 0">

      <div class="form-group">
        <label class="form-label" for="guest-name">Your Name *</label>
        <input class="form-input" type="text" id="guest-name" placeholder="Enter your name" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="guest-phone">Phone Number *</label>
        <input class="form-input" type="text" id="guest-phone" placeholder="09xxxxxxxxx" required>
      </div>
      <div class="form-group">
        <label class="form-label" for="guest-note">Note (optional)</label>
        <textarea class="form-textarea" id="guest-note" rows="2" placeholder="Any special requests..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('confirm-modal')">Close</button>
      <button class="btn btn-primary" id="confirm-order-btn">Confirm Order</button>
    </div>
  </div>
</div>

<!-- Receipt Modal -->
<div class="modal-overlay" id="receipt-modal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header">
      <h3>&#9989; Order Placed!</h3>
      <button class="modal-close" onclick="closeModal('receipt-modal'); location.reload();">&times;</button>
    </div>
    <div class="modal-body" id="receipt-content"></div>
    <div class="modal-footer">
      <button class="btn btn-outline" id="receipt-print-btn">&#128424; Print</button>
      <a class="btn btn-primary" id="receipt-pdf-btn" target="_blank">&#128196; Download PDF</a>
      <button class="btn btn-accent" onclick="closeModal('receipt-modal'); location.reload();">Done</button>
    </div>
  </div>
</div>

<?php endif; ?>

<script src="assets/js/global.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/pagination.js"></script>
<script src="assets/js/kiosk.js"></script>
</body>
</html>
