<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
http_response_code(403);
$storeName = get_setting('store_name', 'E&N School Supplies');
$forceDark = get_setting('force_dark_mode', '0');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 — <?= htmlspecialchars($storeName) ?></title>
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/components.css">
</head>
<body data-base-path="" data-force-dark-mode="<?= $forceDark ?>" data-theme-preference="auto">
<div style="min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;text-align:center;background:var(--background)">
  <div style="font-size:5rem;margin-bottom:1rem">&#128274;</div>
  <h1 style="font-size:2rem;color:var(--text-primary);margin-bottom:0.5rem">Access Denied</h1>
  <p style="color:var(--text-secondary);margin-bottom:1.5rem;max-width:400px">You don't have permission to view this page.</p>
  <a href="index.php" class="btn btn-primary">&larr; Back to Home</a>
</div>
<script src="assets/js/theme.js"></script>
</body>
</html>
