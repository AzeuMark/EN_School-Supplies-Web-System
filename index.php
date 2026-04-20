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
    /* ── Nav ── */
    .landing-nav {
      position: sticky;
      top: 0;
      z-index: 200;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
      height: 64px;
      background: rgba(255,255,255,0.85);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      transition: background var(--transition);
    }
    [data-theme="dark"] .landing-nav {
      background: rgba(30,42,30,0.88);
    }
    .landing-nav .brand {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      text-decoration: none;
      color: var(--primary);
      font-weight: 800;
      font-size: 1.05rem;
      letter-spacing: -0.01em;
    }
    .landing-nav .brand img {
      width: 38px;
      height: 38px;
      border-radius: var(--radius-sm);
      object-fit: contain;
    }
    .landing-nav .nav-right {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .theme-toggle {
      background: none;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      width: 36px;
      height: 36px;
      cursor: pointer;
      color: var(--text-secondary);
      font-size: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all var(--transition);
    }
    .theme-toggle:hover {
      border-color: var(--primary);
      color: var(--primary);
    }

    /* ── Hero ── */
    .hero {
      position: relative;
      overflow: hidden;
      background: linear-gradient(150deg, #1b5e20 0%, #2e7d32 45%, #388e3c 100%);
      color: #fff;
      padding: 5.5rem 1.5rem 7rem;
      text-align: center;
    }
    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse 60% 50% at 20% 80%, rgba(129,199,132,0.18) 0%, transparent 70%),
        radial-gradient(ellipse 50% 60% at 80% 20%, rgba(255,255,255,0.07) 0%, transparent 70%);
      pointer-events: none;
    }
    .hero-dots {
      position: absolute;
      inset: 0;
      pointer-events: none;
      background-image: radial-gradient(rgba(255,255,255,0.08) 1px, transparent 1px);
      background-size: 28px 28px;
    }
    .hero-wave {
      position: absolute;
      bottom: -1px;
      left: 0;
      width: 100%;
      line-height: 0;
    }
    .hero-wave svg {
      display: block;
      width: 100%;
    }
    .hero-inner {
      position: relative;
      z-index: 1;
      max-width: 680px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .hero-logo-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 88px;
      height: 88px;
      border-radius: var(--radius-lg);
      background: rgba(255,255,255,0.15);
      border: 2px solid rgba(255,255,255,0.25);
      margin-bottom: 1.25rem;
      backdrop-filter: blur(4px);
      animation: heroFloat 4s ease-in-out infinite;
      flex-shrink: 0;
    }
    .hero-logo-wrap img {
      width: 52px;
      height: 52px;
      object-fit: contain;
    }
    @keyframes heroFloat {
      0%, 100% { transform: translateY(0); }
      50%       { transform: translateY(-8px); }
    }
    .hero-eyebrow {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: var(--radius-full);
      padding: 0.3rem 0.9rem;
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      margin-bottom: 1rem;
      backdrop-filter: blur(4px);
    }
    .hero h1 {
      font-size: clamp(2rem, 5vw, 3rem);
      font-weight: 800;
      line-height: 1.15;
      margin-bottom: 1rem;
      letter-spacing: -0.02em;
    }
    .hero h1 span {
      color: var(--accent);
    }
    .hero .tagline {
      font-size: 1.08rem;
      opacity: 0.88;
      margin-bottom: 2rem;
      max-width: 480px;
      margin-left: auto;
      margin-right: auto;
    }
    .hero-actions {
      display: flex;
      gap: 0.75rem;
      justify-content: center;
      flex-wrap: wrap;
    }
    .btn-hero-primary {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.8rem 2rem;
      background: #fff;
      color: var(--primary);
      border: none;
      border-radius: var(--radius-sm);
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
      transition: all var(--transition);
    }
    .btn-hero-primary:hover {
      background: #e8f5e9;
      transform: translateY(-2px);
      box-shadow: 0 6px 24px rgba(0,0,0,0.25);
      color: var(--primary);
    }
    .btn-hero-outline {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.8rem 2rem;
      background: transparent;
      color: #fff;
      border: 2px solid rgba(255,255,255,0.45);
      border-radius: var(--radius-sm);
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all var(--transition);
    }
    .btn-hero-outline:hover {
      border-color: #fff;
      background: rgba(255,255,255,0.12);
      color: #fff;
      transform: translateY(-2px);
    }

    /* ── Benefits Strip ── */
    .benefits-strip {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
    }
    .benefits-inner {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0 1.5rem;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0;
    }
    .benefit-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1.5rem 1.5rem;
      border-right: 1px solid var(--border);
    }
    .benefit-item:last-child { border-right: none; }
    .benefit-icon {
      width: 46px;
      height: 46px;
      border-radius: var(--radius-md);
      background: var(--background);
      border: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
    }
    .benefit-text strong {
      display: block;
      font-size: 0.9rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 0.1rem;
    }
    .benefit-text span {
      font-size: 0.78rem;
      color: var(--text-muted);
    }

    /* ── Featured Items ── */
    .featured-section {
      max-width: 1200px;
      margin: 0 auto;
      padding: 3.5rem 1.5rem;
    }
    .section-header {
      text-align: center;
      margin-bottom: 2.5rem;
    }
    .section-label {
      display: inline-block;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--primary);
      background: var(--background);
      border: 1px solid var(--border);
      border-radius: var(--radius-full);
      padding: 0.2rem 0.8rem;
      margin-bottom: 0.65rem;
    }
    .section-header h2 {
      font-size: 1.75rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: var(--text-primary);
      margin-bottom: 0.5rem;
    }
    .section-header p {
      font-size: 0.92rem;
      color: var(--text-secondary);
    }
    .featured-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 1.1rem;
    }
    .featured-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: box-shadow var(--transition), transform var(--transition), border-color var(--transition);
    }
    .featured-card:hover {
      box-shadow: var(--shadow-lg);
      transform: translateY(-4px);
      border-color: var(--primary-light);
    }
    .card-img {
      width: 100%;
      height: 148px;
      background: var(--background);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: var(--text-muted);
      position: relative;
      overflow: hidden;
    }
    .card-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.35s ease;
    }
    .featured-card:hover .card-img img {
      transform: scale(1.05);
    }
    .card-body {
      padding: 0.9rem 1rem 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
      flex: 1;
    }
    .card-category {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--primary);
      background: var(--background);
      border: 1px solid var(--border);
      border-radius: var(--radius-full);
      padding: 0.1rem 0.55rem;
      align-self: flex-start;
    }
    .card-name {
      font-size: 0.92rem;
      font-weight: 700;
      color: var(--text-primary);
      line-height: 1.35;
    }
    .card-footer-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 0.25rem;
    }
    .card-price {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--primary);
    }
    .card-stock {
      font-size: 0.7rem;
      font-weight: 600;
      color: var(--text-muted);
    }

    /* ── CTA Band ── */
    .cta-band {
      background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
      color: #fff;
      text-align: center;
      padding: 3.5rem 1.5rem;
      position: relative;
      overflow: hidden;
    }
    .cta-band::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
      background-size: 24px 24px;
    }
    .cta-band-inner {
      position: relative;
      z-index: 1;
      max-width: 560px;
      margin: 0 auto;
    }
    .cta-band h2 {
      font-size: 1.75rem;
      font-weight: 800;
      margin-bottom: 0.65rem;
      letter-spacing: -0.02em;
    }
    .cta-band p {
      font-size: 0.95rem;
      opacity: 0.85;
      margin-bottom: 1.75rem;
    }
    .cta-actions {
      display: flex;
      gap: 0.75rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    /* ── Footer ── */
    .landing-footer {
      background: var(--surface);
      border-top: 1px solid var(--border);
    }
    .footer-main {
      max-width: 1100px;
      margin: 0 auto;
      padding: 2.5rem 1.5rem 2rem;
      display: grid;
      grid-template-columns: 1.8fr 1fr 1fr;
      gap: 2.5rem;
    }
    .footer-brand .brand-logo {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      margin-bottom: 0.85rem;
    }
    .footer-brand .brand-logo img {
      width: 34px;
      height: 34px;
      object-fit: contain;
      border-radius: var(--radius-sm);
    }
    .footer-brand .brand-logo span {
      font-weight: 800;
      font-size: 1rem;
      color: var(--primary);
    }
    .footer-brand p {
      font-size: 0.83rem;
      color: var(--text-muted);
      line-height: 1.6;
      max-width: 260px;
    }
    .footer-contact-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.82rem;
      color: var(--text-secondary);
      margin-top: 0.65rem;
    }
    .footer-contact-item .contact-icon {
      font-size: 0.95rem;
      width: 22px;
      text-align: center;
    }
    .footer-col h4 {
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--text-muted);
      margin-bottom: 1rem;
    }
    .footer-col ul {
      display: flex;
      flex-direction: column;
      gap: 0.55rem;
    }
    .footer-col ul li a {
      font-size: 0.85rem;
      color: var(--text-secondary);
      text-decoration: none;
      transition: color var(--transition);
    }
    .footer-col ul li a:hover {
      color: var(--primary);
    }
    .footer-bottom {
      border-top: 1px solid var(--border);
    }
    .footer-bottom-inner {
      max-width: 1100px;
      margin: 0 auto;
      padding: 0.9rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .footer-bottom-inner span {
      font-size: 0.78rem;
      color: var(--text-muted);
    }

    /* ── Responsive ── */
    @media (max-width: 900px) {
      .benefits-inner { grid-template-columns: 1fr; }
      .benefit-item { border-right: none; border-bottom: 1px solid var(--border); }
      .benefit-item:last-child { border-bottom: none; }
      .footer-main { grid-template-columns: 1fr 1fr; }
      .footer-brand { grid-column: 1 / -1; }
    }
    @media (max-width: 600px) {
      .landing-nav { padding: 0 1rem; }
      .hero { padding: 4rem 1rem 6rem; }
      .hero h1 { font-size: 1.8rem; }
      .featured-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.75rem; }
      .footer-main { grid-template-columns: 1fr; gap: 1.5rem; }
      .footer-bottom-inner { flex-direction: column; text-align: center; }
    }
  </style>
</head>
<body
  data-base-path=""
  data-force-dark-mode="<?= $forceDark ?>"
  data-theme-preference="auto"
>

<!-- ── Navigation ── -->
<nav class="landing-nav">
  <a href="index.php" class="brand">
    <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" onerror="this.style.display='none'">
    <?= htmlspecialchars($storeName) ?>
  </a>
  <div class="nav-right">
    <button class="theme-toggle" id="theme-toggle" title="Toggle theme">&#9790;</button>
    <?php if (is_logged_in()): ?>
      <a href="<?= current_user_role() ?>/dashboard.php" class="btn btn-primary btn-sm">Dashboard</a>
    <?php else: ?>
      <a href="login.php" class="btn btn-outline btn-sm">Login</a>
      <a href="register.php" class="btn btn-primary btn-sm">Register</a>
    <?php endif; ?>
  </div>
</nav>

<!-- ── Hero ── -->
<section class="hero">
  <div class="hero-dots"></div>
  <div class="hero-inner">
    <div class="hero-logo-wrap">
      <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" onerror="this.style.display='none'">
    </div>
    <div class="hero-eyebrow">&#127891; School Supplies Store</div>
    <h1>Your Go-To Store for<br><span>Quality School Supplies</span></h1>
    <p class="tagline">Everything you need for school — from notebooks to art materials — available in-store and online.</p>
    <div class="hero-actions">
      <a href="kiosk.php" class="btn-hero-primary">&#128722; Order Now</a>
      <?php if (!is_logged_in()): ?>
        <a href="login.php" class="btn-hero-outline">&#128274; Login</a>
      <?php else: ?>
        <a href="<?= current_user_role() ?>/dashboard.php" class="btn-hero-outline">&#128202; Dashboard</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero-wave">
    <svg viewBox="0 0 1440 56" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
      <path d="M0 56L60 48C120 40 240 24 360 20C480 16 600 24 720 30C840 36 960 40 1080 38C1200 36 1320 28 1380 24L1440 20V56H1380C1320 56 1200 56 1080 56C960 56 840 56 720 56C600 56 480 56 360 56C240 56 120 56 60 56H0Z" fill="var(--surface)"/>
    </svg>
  </div>
</section>

<!-- ── Benefits Strip ── -->
<div class="benefits-strip">
  <div class="benefits-inner">
    <div class="benefit-item">
      <div class="benefit-icon">&#128230;</div>
      <div class="benefit-text">
        <strong>Walk-in &amp; Online Orders</strong>
        <span>Order at the kiosk in-store or through your account</span>
      </div>
    </div>
    <div class="benefit-item">
      <div class="benefit-icon">&#9989;</div>
      <div class="benefit-text">
        <strong>Live Stock Updates</strong>
        <span>Always up-to-date availability on every item</span>
      </div>
    </div>
    <div class="benefit-item">
      <div class="benefit-icon">&#128203;</div>
      <div class="benefit-text">
        <strong>Instant Order Receipt</strong>
        <span>Print or download your receipt immediately after ordering</span>
      </div>
    </div>
  </div>
</div>

<!-- ── Featured Items ── -->
<?php if (!empty($featuredItems)): ?>
<section class="featured-section">
  <div class="section-header">
    <span class="section-label">&#127775; Now Available</span>
    <h2>Featured Items</h2>
    <p>Browse our latest in-stock school supplies ready for pick-up.</p>
  </div>
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
        <div class="card-body">
          <span class="card-category"><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></span>
          <div class="card-name"><?= htmlspecialchars($item['item_name']) ?></div>
          <div class="card-footer-row">
            <span class="card-price"><?= format_price($item['price']) ?></span>
            <span class="card-stock">&#128230; <?= (int)$item['stock_count'] ?> left</span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ── CTA Band ── -->
<section class="cta-band">
  <div class="cta-band-inner">
    <h2>Ready to place your order?</h2>
    <p>Walk up to our in-store kiosk or create a free account to order online and track your orders.</p>
    <div class="cta-actions">
      <a href="kiosk.php" class="btn-hero-primary">&#128722; Order at Kiosk</a>
      <?php if (!is_logged_in()): ?>
        <a href="register.php" class="btn-hero-outline">&#128100; Create Account</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- ── Footer ── -->
<footer class="landing-footer">
  <div class="footer-main">
    <div class="footer-brand">
      <div class="brand-logo">
        <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" onerror="this.style.display='none'">
        <span><?= htmlspecialchars($storeName) ?></span>
      </div>
      <p>Your trusted neighborhood school supplies store — providing quality materials for students of all levels.</p>
      <?php if ($storePhone): ?>
        <div class="footer-contact-item">
          <span class="contact-icon">&#128222;</span>
          <?= htmlspecialchars($storePhone) ?>
        </div>
      <?php endif; ?>
      <?php if ($storeEmail): ?>
        <div class="footer-contact-item">
          <span class="contact-icon">&#9993;</span>
          <?= htmlspecialchars($storeEmail) ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="footer-col">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="kiosk.php">&#128722; Order Now (Kiosk)</a></li>
        <li><a href="login.php">&#128274; Login</a></li>
        <?php if (!is_logged_in()): ?>
          <li><a href="register.php">&#128100; Create Account</a></li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="footer-col">
      <h4>System</h4>
      <ul>
        <li>
          <span class="system-status <?= $sysStatus ?>">
            <span class="status-dot"></span>
            <?= ucfirst($sysStatus) ?>
          </span>
        </li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-bottom-inner">
      <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($storeName) ?>. All rights reserved.</span>
      <span>Powered by E&amp;N School Supplies System</span>
    </div>
  </div>
</footer>

<script src="assets/js/global.js"></script>
<script src="assets/js/theme.js"></script>
</body>
</html>
