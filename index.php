<?php
/**
 * E&N School Supplies — Landing Page (Public)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$storeName  = get_setting('store_name', 'E&N School Supplies');
$storePhone = get_setting('store_phone', '');
$storeEmail = get_setting('store_email', '');
$logoPath   = get_setting('logo_path', 'assets/images/logo.png');
$sysStatus  = get_setting('system_status', 'online');
$forceDark  = get_setting('force_dark_mode', '0');

// Featured items — top 8 in-stock
$featuredItems = [];
try {
    $stmt = $pdo->query(
        "SELECT i.*, c.category_name
         FROM inventory i
         LEFT JOIN item_categories c ON i.category_id = c.id
         WHERE i.stock_count > 0
         ORDER BY i.created_at DESC
         LIMIT 8"
    );
    $featuredItems = $stmt->fetchAll();
} catch (Exception $e) {
    // Silent fail
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($storeName) ?></title>
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .landing-nav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 2rem;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .landing-nav .brand {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      text-decoration: none;
      color: var(--primary);
      font-weight: 700;
      font-size: 1.1rem;
    }
    .landing-nav .brand img {
      width: 36px;
      height: 36px;
      border-radius: var(--radius-sm);
      object-fit: contain;
    }
    .landing-nav .nav-actions {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }

    .hero {
      text-align: center;
      padding: 4rem 1.5rem 3rem;
      background: linear-gradient(135deg, var(--primary) 0%, #1b5e20 100%);
      color: #fff;
    }
    .hero img {
      width: 80px;
      height: 80px;
      border-radius: var(--radius-md);
      margin: 0 auto 1rem;
      object-fit: contain;
      background: rgba(255,255,255,0.15);
      padding: 0.5rem;
    }
    .hero h1 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }
    .hero .tagline {
      font-size: 1.05rem;
      opacity: 0.9;
      margin-bottom: 1.5rem;
    }
    .hero .hero-actions {
      display: flex;
      gap: 0.75rem;
      justify-content: center;
      flex-wrap: wrap;
    }
    .hero .btn-hero-primary {
      padding: 0.7rem 1.8rem;
      background: #fff;
      color: var(--primary);
      border: none;
      border-radius: var(--radius-sm);
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all var(--transition);
    }
    .hero .btn-hero-primary:hover { background: #e8f5e9; }
    .hero .btn-hero-outline {
      padding: 0.7rem 1.8rem;
      background: transparent;
      color: #fff;
      border: 2px solid rgba(255,255,255,0.5);
      border-radius: var(--radius-sm);
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all var(--transition);
    }
    .hero .btn-hero-outline:hover { border-color: #fff; background: rgba(255,255,255,0.1); }

    .featured-section {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2.5rem 1.5rem;
    }
    .featured-section h2 {
      text-align: center;
      margin-bottom: 1.5rem;
      color: var(--text-primary);
    }
    .featured-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
      gap: 1rem;
    }
    .featured-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      transition: box-shadow var(--transition), transform var(--transition);
    }
    .featured-card:hover {
      box-shadow: var(--shadow-md);
      transform: translateY(-3px);
    }
    .featured-card .card-img {
      width: 100%;
      height: 130px;
      background: var(--background);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: var(--text-muted);
    }
    .featured-card .card-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .featured-card .card-info {
      padding: 0.85rem;
    }
    .featured-card .card-info .name {
      font-weight: 700;
      font-size: 0.9rem;
      margin-bottom: 0.15rem;
    }
    .featured-card .card-info .category {
      font-size: 0.78rem;
      color: var(--text-muted);
      margin-bottom: 0.3rem;
    }
    .featured-card .card-info .price {
      font-size: 1.05rem;
      font-weight: 800;
      color: var(--primary);
    }

    .landing-footer {
      background: var(--surface);
      border-top: 1px solid var(--border);
      text-align: center;
      padding: 1.5rem;
      font-size: 0.85rem;
      color: var(--text-secondary);
    }
    .landing-footer .footer-info {
      display: flex;
      justify-content: center;
      gap: 1.5rem;
      flex-wrap: wrap;
      margin-bottom: 0.5rem;
    }
    .landing-footer .status {
      margin-top: 0.5rem;
    }

    @media (max-width: 480px) {
      .hero h1 { font-size: 1.5rem; }
      .landing-nav { padding: 0.75rem 1rem; }
    }
  </style>
</head>
<body
  data-base-path=""
  data-force-dark-mode="<?= $forceDark ?>"
  data-theme-preference="auto"
>

<nav class="landing-nav">
  <a href="index.php" class="brand">
    <img src="<?= $logoPath ?>" alt="Logo" onerror="this.style.display='none'">
    <?= htmlspecialchars($storeName) ?>
  </a>
  <div class="nav-actions">
    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">&#9790;</button>
    <?php if (is_logged_in()): ?>
      <a href="<?= current_user_role() ?>/dashboard.php" class="btn btn-primary btn-sm">Dashboard</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-outline btn-sm">Login</a>
      <a href="register.php" class="btn btn-primary btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<section class="hero">
  <img src="<?= $logoPath ?>" alt="Logo" onerror="this.style.display='none'">
  <h1><?= htmlspecialchars($storeName) ?></h1>
  <p class="tagline">Your one-stop shop for quality school supplies</p>
  <div class="hero-actions">
    <a href="kiosk.php" class="btn-hero-primary">&#128722; Order Now</a>
    <?php if (!is_logged_in()): ?>
      <a href="login.php" class="btn-hero-outline">Login</a>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($featuredItems)): ?>
<section class="featured-section">
  <h2>Featured Items</h2>
  <div class="featured-grid">
    <?php foreach ($featuredItems as $item): ?>
      <div class="featured-card">
        <div class="card-img">
          <?php if (!empty($item['item_image'])): ?>
            <img src="<?= htmlspecialchars($item['item_image']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>">
          <?php else: ?>
            &#128218;
          <?php endif; ?>
        </div>
        <div class="card-info">
          <div class="name"><?= htmlspecialchars($item['item_name']) ?></div>
          <div class="category"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></div>
          <div class="price"><?= format_price($item['price']) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<footer class="landing-footer">
  <div class="footer-info">
    <span><?= htmlspecialchars($storeName) ?></span>
    <?php if ($storePhone): ?>
      <span>&#128222; <?= htmlspecialchars($storePhone) ?></span>
    <?php endif; ?>
    <?php if ($storeEmail): ?>
      <span>&#9993; <?= htmlspecialchars($storeEmail) ?></span>
    <?php endif; ?>
  </div>
  <div class="status">
    <span class="system-status <?= $sysStatus ?>">
      <span class="status-dot"></span>
      System: <?= ucfirst($sysStatus) ?>
    </span>
  </div>
</footer>

<script src="assets/js/global.js"></script>
<script src="assets/js/theme.js"></script>
</body>
</html>
