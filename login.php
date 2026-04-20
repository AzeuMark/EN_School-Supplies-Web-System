<?php
/**
 * E&N School Supplies — Login Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/aes.php';
require_once __DIR__ . '/includes/logger.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(current_user_role() . '/dashboard.php');
}

$storeName = get_setting('store_name', 'E&N School Supplies');
$logoPath  = get_setting('logo_path', 'assets/images/logo.png');
$forceDark = get_setting('force_dark_mode', '0');
$sysStatus = get_setting('system_status', 'online');

$error = '';
$email = '';

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'Invalid email or password.';
                    log_warning('Failed login attempt — user not found', ['email' => $email]);
                } elseif ($user['status'] === 'flagged') {
                    $storePhone = get_setting('store_phone', 'the store');
                    $error = 'Your account has been flagged. Please contact us at ' . ($storePhone ?: 'the store') . ' or visit the store.';
                    log_warning('Flagged user attempted login', ['user_id' => $user['id']]);
                } elseif ($user['status'] === 'pending') {
                    $error = 'Your account is still pending approval. Please wait for an admin or staff to approve it.';
                } else {
                    // Verify password
                    $decrypted = aes_decrypt($user['password']);
                    if ($decrypted === $password) {
                        // Check system status for non-admin
                        if ($user['role'] !== 'admin' && $sysStatus === 'offline') {
                            $error = 'The system is currently offline. Please try again later.';
                        } else {
                            // Set session
                            $_SESSION['user_id']   = $user['id'];
                            $_SESSION['user_role']  = $user['role'];
                            $_SESSION['user_name']  = $user['full_name'];

                            // Log staff session start
                            if ($user['role'] === 'staff') {
                                $pdo->prepare(
                                    "INSERT INTO staff_sessions (user_id, login_time) VALUES (?, NOW())"
                                )->execute([$user['id']]);
                                $_SESSION['staff_session_id'] = $pdo->lastInsertId();
                            }

                            log_info('User logged in', ['user_id' => $user['id'], 'role' => $user['role']], $user['id']);

                            // PRG redirect to dashboard
                            redirect($user['role'] . '/dashboard.php');
                        }
                    } else {
                        $error = 'Invalid email or password.';
                        log_warning('Failed login attempt — wrong password', ['email' => $email]);
                    }
                }
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again.';
                log_error('Login error', ['error' => $e->getMessage()]);
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Login — <?= htmlspecialchars($storeName) ?></title>
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <style>
    .auth-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      background: var(--background);
    }
    .auth-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      width: 100%;
      max-width: 420px;
      padding: 2rem;
      box-shadow: var(--shadow-md);
    }
    .auth-brand {
      text-align: center;
      margin-bottom: 1.5rem;
    }
    .auth-brand img {
      width: 56px;
      height: 56px;
      border-radius: var(--radius-md);
      margin: 0 auto 0.75rem;
      object-fit: contain;
    }
    .auth-brand h1 {
      font-size: 1.3rem;
      color: var(--primary);
      margin-bottom: 0.25rem;
    }
    .auth-brand p {
      font-size: 0.85rem;
      color: var(--text-secondary);
    }
    .auth-links {
      text-align: center;
      margin-top: 1rem;
      font-size: 0.85rem;
    }
    .auth-back {
      display: inline-block;
      margin-top: 0.75rem;
      font-size: 0.82rem;
      color: var(--text-muted);
    }
  </style>
</head>
<body data-base-path="" data-force-dark-mode="<?= $forceDark ?>" data-theme-preference="auto">

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-brand">
      <img src="<?= $logoPath ?>" alt="Logo" onerror="this.style.display='none'">
      <h1><?= htmlspecialchars($storeName) ?></h1>
      <p>Sign in to your account</p>
    </div>

    <?php if ($error): ?>
      <div id="page-error" class="show" style="display:block"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($flash): ?>
      <div id="flash-data" data-type="<?= $flash['type'] ?>" data-message="<?= htmlspecialchars($flash['message']) ?>" style="display:none"></div>
    <?php endif; ?>

    <form method="POST" action="login.php" data-prg>
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" type="text" id="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($email) ?>" required autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-input" type="password" id="password" name="password" placeholder="Enter your password" required>
      </div>

      <button class="btn btn-primary w-full" type="submit">Sign In</button>
    </form>

    <div class="auth-links">
      Don't have an account? <a href="register.php">Register here</a>
    </div>
    <div style="text-align:center">
      <a href="index.php" class="auth-back">&larr; Back to home</a>
    </div>
  </div>
</div>

<script src="assets/js/global.js"></script>
<script src="assets/js/theme.js"></script>
</body>
</html>
