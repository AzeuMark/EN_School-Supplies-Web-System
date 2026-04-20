<?php
/**
 * E&N School Supplies — One-Click Setup
 * Creates all tables (drops existing), seeds data, and encrypts the admin password.
 *
 * Visit: http://localhost/en-school-supplies/setup.php
 * DELETE THIS FILE AFTER SETUP.
 */

$configFile = __DIR__ . '/config.json';

if (!file_exists($configFile)) {
    die('<h2 style="color:red">config.json not found.</h2>');
}

$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    die('<h2 style="color:red">Invalid config.json.</h2>');
}

$db = $config['database'];
$results = [];
$dbExists = false;
$setupDone = false;

// ── Check if database exists ──
try {
    $pdo = new PDO(
        "mysql:host={$db['host']};charset={$db['charset']}",
        $db['user'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($db['name']));
    $dbExists = (bool)$stmt->fetch();
} catch (PDOException $e) {
    die('<h2 style="color:red">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</h2>');
}

// ── Handle POST: run setup ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    if (!$dbExists) {
        $results[] = ['error', "Database '{$db['name']}' does not exist. Create it manually first via phpMyAdmin."];
    } else {
        try {
            $pdo = new PDO(
                "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}",
                $db['user'],
                $db['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Disable FK checks for drop
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Drop existing tables
            $tables = ['system_logs', 'staff_sessions', 'order_items', 'orders', 'inventory', 'default_item_names', 'item_categories', 'system_settings', 'users'];
            foreach ($tables as $t) {
                $pdo->exec("DROP TABLE IF EXISTS `$t`");
                $results[] = ['info', "Dropped table <strong>$t</strong> (if existed)"];
            }

            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

            // ── Create Tables ──

            $pdo->exec("CREATE TABLE `users` (
                `id`               INT AUTO_INCREMENT PRIMARY KEY,
                `full_name`        VARCHAR(150)    NOT NULL,
                `email`            VARCHAR(150)    NOT NULL UNIQUE,
                `phone`            VARCHAR(20)     NOT NULL DEFAULT '',
                `password`         TEXT            NOT NULL,
                `role`             ENUM('admin','staff','customer') NOT NULL,
                `status`           ENUM('active','pending','flagged') NOT NULL DEFAULT 'active',
                `flag_reason`      TEXT            NULL,
                `profile_image`    VARCHAR(255)    NULL,
                `theme_preference` ENUM('light','dark','auto') NOT NULL DEFAULT 'auto',
                `created_by`       INT             NULL,
                `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>users</strong>'];

            $pdo->exec("CREATE TABLE `item_categories` (
                `id`            INT AUTO_INCREMENT PRIMARY KEY,
                `category_name` VARCHAR(100) NOT NULL UNIQUE,
                `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>item_categories</strong>'];

            $pdo->exec("CREATE TABLE `default_item_names` (
                `id`        INT AUTO_INCREMENT PRIMARY KEY,
                `item_name` VARCHAR(150) NOT NULL UNIQUE
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>default_item_names</strong>'];

            $pdo->exec("CREATE TABLE `inventory` (
                `id`            INT AUTO_INCREMENT PRIMARY KEY,
                `item_name`     VARCHAR(150)   NOT NULL,
                `category_id`   INT            NULL,
                `price`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
                `stock_count`   INT            NOT NULL DEFAULT 0,
                `max_order_qty` INT            NOT NULL DEFAULT 1,
                `item_image`    VARCHAR(255)   NULL,
                `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_inventory_category` FOREIGN KEY (`category_id`) REFERENCES `item_categories`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>inventory</strong>'];

            $pdo->exec("CREATE TABLE `orders` (
                `id`           INT AUTO_INCREMENT PRIMARY KEY,
                `order_code`   VARCHAR(20)    NOT NULL UNIQUE,
                `user_id`      INT            NULL,
                `guest_name`   VARCHAR(150)   NULL,
                `guest_phone`  VARCHAR(20)    NULL,
                `guest_note`   TEXT           NULL,
                `status`       ENUM('pending','ready','claimed','cancelled') NOT NULL DEFAULT 'pending',
                `total_price`  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
                `processed_by` INT            NULL,
                `created_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_orders_user`      FOREIGN KEY (`user_id`)      REFERENCES `users`(`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_orders_processed` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>orders</strong>'];

            $pdo->exec("CREATE TABLE `order_items` (
                `id`                 INT AUTO_INCREMENT PRIMARY KEY,
                `order_id`           INT            NOT NULL,
                `item_id`            INT            NOT NULL,
                `item_name_snapshot` VARCHAR(150)   NOT NULL,
                `quantity`           INT            NOT NULL DEFAULT 1,
                `unit_price`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
                CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_oi_item`  FOREIGN KEY (`item_id`)  REFERENCES `inventory`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>order_items</strong>'];

            $pdo->exec("CREATE TABLE `staff_sessions` (
                `id`               INT AUTO_INCREMENT PRIMARY KEY,
                `user_id`          INT        NOT NULL,
                `login_time`       TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `logout_time`      TIMESTAMP  NULL,
                `logout_type`      ENUM('manual','auto_system') NULL,
                `duration_minutes` INT        NULL,
                `is_suspicious`    TINYINT(1) NOT NULL DEFAULT 0,
                CONSTRAINT `fk_ss_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>staff_sessions</strong>'];

            $pdo->exec("CREATE TABLE `system_settings` (
                `setting_key`   VARCHAR(100) PRIMARY KEY,
                `setting_value` TEXT         NOT NULL,
                `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>system_settings</strong>'];

            $pdo->exec("CREATE TABLE `system_logs` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `level`      ENUM('info','warning','error') NOT NULL DEFAULT 'info',
                `message`    TEXT        NOT NULL,
                `context`    JSON        NULL,
                `user_id`    INT         NULL,
                `ip_address` VARCHAR(45) NULL,
                `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB");
            $results[] = ['ok', 'Created table <strong>system_logs</strong>'];

            // ── Seed Data ──

            // Admin account with AES-encrypted password
            require_once __DIR__ . '/includes/aes.php';
            $adminEmail = $config['admin']['default_username'];
            $adminPass  = $config['admin']['default_password'];
            $encPass    = aes_encrypt($adminPass);

            $stmt = $pdo->prepare("INSERT INTO `users` (`full_name`,`email`,`phone`,`password`,`role`,`status`) VALUES (?,?,?,?,?,?)");
            $stmt->execute(['Administrator', $adminEmail, '', $encPass, 'admin', 'active']);
            $results[] = ['ok', "Created admin account: <strong>$adminEmail</strong> / <strong>$adminPass</strong>"];

            // System settings
            $settings = [
                ['store_name',             'E&N School Supplies'],
                ['store_phone',            ''],
                ['store_email',            ''],
                ['logo_path',              'assets/images/logo.png'],
                ['timezone',               'Asia/Manila'],
                ['force_dark_mode',        '0'],
                ['disable_nologin_orders', '0'],
                ['online_payment',         '0'],
                ['system_status',          'online'],
                ['auto_logout_hours',      '8'],
            ];
            $stmt = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`,`setting_value`) VALUES (?,?)");
            foreach ($settings as $s) $stmt->execute($s);
            $results[] = ['ok', 'Inserted <strong>' . count($settings) . '</strong> system settings'];

            // Categories
            $cats = ['Notebooks','Writing Instruments','Paper Products','Art Supplies','Filing & Organization','Bags & Cases','Measuring Tools','Adhesives & Tapes','Scissors & Cutters','General Supplies'];
            $stmt = $pdo->prepare("INSERT INTO `item_categories` (`category_name`) VALUES (?)");
            foreach ($cats as $c) $stmt->execute([$c]);
            $results[] = ['ok', 'Inserted <strong>' . count($cats) . '</strong> item categories'];

            // Default item names
            $names = ['Notebook (80 leaves)','Notebook (100 leaves)','Ballpen (Blue)','Ballpen (Black)','Ballpen (Red)','Pencil #2','Eraser','Ruler (12 inch)','Ruler (18 inch)','Scissors','Glue Stick','Liquid Glue','Yellow Pad','Bond Paper (Short)','Bond Paper (Long)','Folder (Long)','Folder (Short)','Clear Book (20 pockets)','Crayons (24 colors)','Colored Pencils (12 colors)','Marker (Black)','Highlighter (Yellow)','Correction Tape','Stapler','Staple Wire (#35)','Masking Tape','Transparent Tape','Pencil Case','Backpack','Protractor'];
            $stmt = $pdo->prepare("INSERT INTO `default_item_names` (`item_name`) VALUES (?)");
            foreach ($names as $n) $stmt->execute([$n]);
            $results[] = ['ok', 'Inserted <strong>' . count($names) . '</strong> default item names'];

            $setupDone = true;

        } catch (PDOException $e) {
            $results[] = ['error', 'SQL Error: ' . htmlspecialchars($e->getMessage())];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup — E&N School Supplies</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Segoe UI',sans-serif; background:#f5f5f5; color:#222; padding:2rem; }
  .wrap { max-width:640px; margin:0 auto; }
  h1 { font-size:1.5rem; margin-bottom:0.25rem; }
  .sub { color:#666; font-size:0.9rem; margin-bottom:1.5rem; }
  .card { background:#fff; border:1px solid #ddd; border-radius:10px; padding:1.5rem; margin-bottom:1rem; }
  .status { padding:0.5rem 0.75rem; border-radius:6px; font-size:0.85rem; margin-bottom:0.4rem; }
  .status.ok { background:#e8f5e9; color:#2e7d32; }
  .status.info { background:#e3f2fd; color:#1565c0; }
  .status.error { background:#ffebee; color:#d32f2f; }
  .status.warn { background:#fff8e1; color:#e65100; }
  .btn { display:inline-block; padding:0.65rem 1.5rem; border:none; border-radius:8px; font-size:0.95rem; font-weight:600; cursor:pointer; text-decoration:none; }
  .btn-green { background:#2e7d32; color:#fff; }
  .btn-green:hover { background:#1b5e20; }
  .btn-blue { background:#1565c0; color:#fff; }
  .btn-blue:hover { background:#0d47a1; }
  .warn-box { background:#fff8e1; border:1px solid #ffe082; border-radius:8px; padding:1rem; margin-bottom:1rem; color:#e65100; font-size:0.9rem; }
  .success-box { background:#e8f5e9; border:1px solid #a5d6a7; border-radius:8px; padding:1rem; margin-bottom:1rem; color:#2e7d32; }
  .creds { background:#f5f5f5; border:1px solid #ddd; border-radius:6px; padding:0.75rem 1rem; font-family:monospace; font-size:0.95rem; margin-top:0.5rem; }
  .danger { color:#d32f2f; font-size:0.82rem; margin-top:1rem; }
</style>
</head>
<body>
<div class="wrap">
  <h1>E&N School Supplies — Setup</h1>
  <p class="sub">One-click database setup</p>

  <?php if (!$dbExists): ?>
    <div class="warn-box">
      <strong>Database '<?= htmlspecialchars($db['name']) ?>' does not exist!</strong><br>
      Please create it manually in phpMyAdmin first:<br><br>
      <code>CREATE DATABASE `<?= htmlspecialchars($db['name']) ?>` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code><br><br>
      Then refresh this page.
    </div>
  <?php endif; ?>

  <?php if (!empty($results)): ?>
    <div class="card">
      <h3 style="margin-bottom:0.75rem">Setup Log</h3>
      <?php foreach ($results as $r): ?>
        <div class="status <?= $r[0] ?>"><?= $r[1] ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($setupDone): ?>
    <div class="success-box">
      <strong>Setup complete!</strong> You can now log in.
      <div class="creds">
        Email: <strong><?= htmlspecialchars($config['admin']['default_username']) ?></strong><br>
        Password: <strong><?= htmlspecialchars($config['admin']['default_password']) ?></strong>
      </div>
      <p class="danger">Delete this file (setup.php) after you're done for security.</p>
    </div>
    <a href="login.php" class="btn btn-blue">Go to Login &rarr;</a>
  <?php elseif ($dbExists): ?>
    <div class="card">
      <p style="margin-bottom:0.5rem"><strong>Ready to set up.</strong></p>
      <p style="font-size:0.85rem;color:#666;margin-bottom:1rem">This will <strong>drop all existing tables</strong> in <code><?= htmlspecialchars($db['name']) ?></code> and recreate them with fresh seed data.</p>
      <form method="POST">
        <button type="submit" name="run_setup" value="1" class="btn btn-green" onclick="return confirm('This will DROP all existing tables and data. Continue?')">Run Setup</button>
      </form>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
