<?php
/**
 * Shared layout header — Navbar + Sidebar.
 *
 * Expects these variables to be set before including:
 *   $pageTitle   (string)  — page <title>
 *   $activePage  (string)  — sidebar item identifier
 *   $user        (array)   — current user row from DB
 *   $badges      (array)   — sidebar badge counts
 */

$storeName  = get_setting('store_name', 'E&N School Supplies');
$logoPath   = get_setting('logo_path', 'assets/images/logo.png');
$sysStatus  = get_setting('system_status', 'online');
$forceDark  = get_setting('force_dark_mode', '0');
$role       = $user['role'] ?? '';
$basePath   = '';

// Determine base path based on current directory depth
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($scriptPath, '/admin/') !== false ||
    strpos($scriptPath, '/staff/') !== false ||
    strpos($scriptPath, '/customer/') !== false) {
    $basePath = '../';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title><?= htmlspecialchars($pageTitle ?? 'E&N School Supplies') ?></title>
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/global.css">
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/components.css">
  <link rel="stylesheet" href="<?= $basePath ?>assets/css/sidebar.css">
  <?php if (!empty($extraCss)): ?>
    <?php foreach ((array)$extraCss as $css): ?>
      <link rel="stylesheet" href="<?= $basePath . $css ?>">
    <?php endforeach; ?>
  <?php endif; ?>
</head>
<body
  data-base-path="<?= $basePath ?>"
  data-user-id="<?= $user['id'] ?? '' ?>"
  data-theme-preference="<?= $user['theme_preference'] ?? 'auto' ?>"
  data-force-dark-mode="<?= $forceDark ?>"
>

<!-- Navbar -->
<nav class="navbar">
  <div class="navbar-left">
    <button class="hamburger" id="hamburger-btn" title="Toggle sidebar">&#9776;</button>
    <a href="<?= $basePath ?>index.php" class="navbar-brand">
      <img src="<?= $basePath . $logoPath ?>" alt="Logo" onerror="this.style.display='none'">
      <span class="brand-name"><?= htmlspecialchars($storeName) ?></span>
    </a>
    <div class="navbar-country">
      <img src="<?= $basePath ?>assets/images/ph-flag.svg" alt="PH" class="navbar-country-flag">
      <span class="navbar-country-name">Philippines</span>
    </div>
  </div>

  <div class="navbar-center">
    <div class="navbar-datetime">
      <span id="navbar-datetime"></span>
    </div>
  </div>

  <div class="navbar-right">
    <span class="system-status <?= $sysStatus ?>">
      <span class="status-dot"></span>
      <span class="status-label"><?= ucfirst($sysStatus) ?></span>
    </span>
    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">&#127769;</button>

    <div class="profile-section">
      <div class="profile-trigger" id="profile-trigger">
        <?php
        $avatarPath = $basePath . ($user['profile_image'] ?? '');
        $initials = '';
        if (!empty($user['full_name'])) {
            $parts = explode(' ', $user['full_name']);
            $initials = strtoupper(substr($parts[0], 0, 1));
            if (count($parts) > 1) $initials .= strtoupper(substr(end($parts), 0, 1));
        }
        ?>
        <?php if (!empty($user['profile_image'])): ?>
          <img class="profile-avatar" src="<?= $avatarPath ?>" alt="Avatar">
        <?php else: ?>
          <span class="profile-avatar"><?= $initials ?></span>
        <?php endif; ?>
        <div class="profile-info">
          <span class="profile-name"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></span>
          <span class="profile-role"><?= htmlspecialchars($role) ?></span>
        </div>
      </div>
      <div class="profile-dropdown" id="profile-dropdown">
        <a href="<?= $basePath . $role ?>/profile.php">&#128100; Profile</a>
        <div class="dropdown-divider"></div>
        <form method="POST" action="<?= $basePath ?>logout.php" style="margin:0">
          <?= csrf_field() ?>
          <button type="submit" class="logout-btn">&#128682; Logout</button>
        </form>
      </div>
    </div>
  </div>
</nav>

<!-- Sidebar -->
<aside class="sidebar">
  <?php if ($role === 'admin'): ?>
    <div class="sidebar-section">
      <div class="sidebar-section-title">Main</div>
      <a class="sidebar-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>" href="<?= $basePath ?>admin/dashboard.php">
        <span class="sidebar-icon">&#127968;</span>
        <span class="sidebar-label">Dashboard</span>
      </a>
      <a class="sidebar-item <?= ($activePage === 'orders') ? 'active' : '' ?>" href="<?= $basePath ?>admin/manage_orders.php">
        <span class="sidebar-icon">&#128230;</span>
        <span class="sidebar-label">Manage Orders</span>
        <?php if ($badges['pending_orders'] > 0): ?>
          <span class="badge-count sidebar-badge"><?= $badges['pending_orders'] ?></span>
        <?php endif; ?>
      </a>
      <a class="sidebar-item <?= ($activePage === 'users') ? 'active' : '' ?>" href="<?= $basePath ?>admin/manage_users.php">
        <span class="sidebar-icon">&#128101;</span>
        <span class="sidebar-label">Manage Users</span>
      </a>
      <a class="sidebar-item <?= ($activePage === 'pending') ? 'active' : '' ?>" href="<?= $basePath ?>admin/pending_accounts.php">
        <span class="sidebar-icon">&#128336;</span>
        <span class="sidebar-label">Pending Accounts</span>
        <?php if ($badges['pending_accounts'] > 0): ?>
          <span class="badge-count sidebar-badge"><?= $badges['pending_accounts'] ?></span>
        <?php endif; ?>
      </a>
      <a class="sidebar-item <?= ($activePage === 'flagged') ? 'active' : '' ?>" href="<?= $basePath ?>admin/flagged_users.php">
        <span class="sidebar-icon">&#9873;</span>
        <span class="sidebar-label">Flagged Users</span>
      </a>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-title">Manage</div>
      <a class="sidebar-item <?= ($activePage === 'inventory') ? 'active' : '' ?>" href="<?= $basePath ?>admin/inventory.php">
        <span class="sidebar-icon">&#128451;</span>
        <span class="sidebar-label">Inventory</span>
      </a>
      <a class="sidebar-item <?= ($activePage === 'staff_stats') ? 'active' : '' ?>" href="<?= $basePath ?>admin/staff_statistics.php">
        <span class="sidebar-icon">&#128202;</span>
        <span class="sidebar-label">Staff Statistics</span>
      </a>
      <a class="sidebar-item <?= ($activePage === 'analytics') ? 'active' : '' ?>" href="<?= $basePath ?>admin/analytics.php">
        <span class="sidebar-icon">&#128200;</span>
        <span class="sidebar-label">Analytics</span>
      </a>
      <a class="sidebar-item <?= ($activePage === 'settings') ? 'active' : '' ?>" href="<?= $basePath ?>admin/system_settings.php">
        <span class="sidebar-icon">&#9881;</span>
        <span class="sidebar-label">System Settings</span>
      </a>
    </div>

  <?php elseif ($role === 'staff'): ?>
    <div class="sidebar-section">
      <div class="sidebar-section-title">Main</div>
      <a class="sidebar-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>" href="<?= $basePath ?>staff/dashboard.php">
        <span class="sidebar-icon">&#127968;</span>
        <span class="sidebar-label">Dashboard</span>
      </a>
      <a class="sidebar-item <?= ($activePage === 'orders') ? 'active' : '' ?>" href="<?= $basePath ?>staff/manage_orders.php">
        <span class="sidebar-icon">&#128230;</span>
        <span class="sidebar-label">Manage Orders</span>
        <?php if ($badges['pending_orders'] > 0): ?>
          <span class="badge-count sidebar-badge"><?= $badges['pending_orders'] ?></span>
        <?php endif; ?>
      </a>
      <a class="sidebar-item <?= ($activePage === 'pending') ? 'active' : '' ?>" href="<?= $basePath ?>staff/pending_accounts.php">
        <span class="sidebar-icon">&#128336;</span>
        <span class="sidebar-label">Pending Accounts</span>
        <?php if ($badges['pending_accounts'] > 0): ?>
          <span class="badge-count sidebar-badge"><?= $badges['pending_accounts'] ?></span>
        <?php endif; ?>
      </a>
    </div>

  <?php elseif ($role === 'customer'): ?>
    <div class="sidebar-section">
      <div class="sidebar-section-title">Main</div>
      <a class="sidebar-item <?= ($activePage === 'dashboard') ? 'active' : '' ?>" href="<?= $basePath ?>customer/dashboard.php">
        <span class="sidebar-icon">&#127968;</span>
        <span class="sidebar-label">Dashboard</span>
      </a>
      <a class="sidebar-item <?= ($activePage === 'make_order') ? 'active' : '' ?>" href="<?= $basePath ?>customer/make_order.php">
        <span class="sidebar-icon">&#128722;</span>
        <span class="sidebar-label">Make Order</span>
      </a>
      <a class="sidebar-item <?= ($activePage === 'order_history') ? 'active' : '' ?>" href="<?= $basePath ?>customer/order_history.php">
        <span class="sidebar-icon">&#128196;</span>
        <span class="sidebar-label">Order History</span>
      </a>
    </div>
  <?php endif; ?>
</aside>

<div class="sidebar-overlay"></div>

<!-- Page Content Start -->
<main class="page-content">
  <?php
  $flash = get_flash();
  if ($flash): ?>
    <div id="flash-data" data-type="<?= $flash['type'] ?>" data-message="<?= htmlspecialchars($flash['message']) ?>" style="display:none"></div>
  <?php endif; ?>
  <div id="page-error" <?php if (!empty($pageError)): ?>data-message="<?= htmlspecialchars($pageError) ?>"<?php endif; ?>></div>
