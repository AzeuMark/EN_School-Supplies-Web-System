<?php
/**
 * E&N School Supplies — Register Page
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/aes.php';
require_once __DIR__ . '/includes/logger.php';

if (is_logged_in()) {
    redirect(current_user_role() . '/dashboard.php');
}

$storeName = get_setting('store_name', 'E&N School Supplies');
$logoPath  = get_setting('logo_path', 'assets/images/logo.png');
$forceDark = get_setting('force_dark_mode', '0');

$error = '';
$success = '';
$formData = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $phone    = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        $formData = ['full_name' => $fullName, 'email' => $email, 'phone' => $phone];

        if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 4) {
            $error = 'Password must be at least 4 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            try {
                // Check duplicate email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'An account with this email already exists.';
                } else {
                    $encPassword = aes_encrypt($password);
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (full_name, email, phone, password, role, status)
                         VALUES (?, ?, ?, ?, 'customer', 'pending')"
                    );
                    $stmt->execute([$fullName, $email, $phone, $encPassword]);
                    log_info('New customer registration (pending)', ['email' => $email]);

                    $success = 'Registration successful! Your account is pending approval.';
                    $formData = ['full_name' => '', 'email' => '', 'phone' => ''];
                }
            } catch (Exception $e) {
                $error = 'An error occurred. Please try again.';
                log_error('Registration error', ['error' => $e->getMessage()]);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <title>Register — <?= htmlspecialchars($storeName) ?></title>
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
    .success-box {
      background: var(--toast-success-bg);
      border-left: 4px solid #4caf50;
      color: #2e7d32;
      padding: 0.75rem 1rem;
      border-radius: var(--radius-sm);
      margin-bottom: 1rem;
      font-size: 0.9rem;
      font-weight: 500;
    }
  </style>
</head>
<body data-base-path="" data-force-dark-mode="<?= $forceDark ?>" data-theme-preference="auto">

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-brand">
      <img src="<?= $logoPath ?>" alt="Logo" onerror="this.style.display='none'">
      <h1><?= htmlspecialchars($storeName) ?></h1>
      <p>Create your account</p>
    </div>

    <?php if ($error): ?>
      <div id="page-error" class="show" style="display:block"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success-box"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" data-prg>
      <?= csrf_field() ?>

      <div class="form-group">
        <label class="form-label" for="full_name">Full Name</label>
        <input class="form-input" type="text" id="full_name" name="full_name" placeholder="Juan Dela Cruz" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-input" type="email" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($formData['email']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="phone">Phone Number</label>
        <input class="form-input" type="text" id="phone" name="phone" placeholder="09xxxxxxxxx" value="<?= htmlspecialchars($formData['phone']) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-input" type="password" id="password" name="password" placeholder="Min. 4 characters" required>
      </div>

      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm Password</label>
        <input class="form-input" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
      </div>

      <button class="btn btn-primary w-full" type="submit">Register</button>
    </form>

    <div class="auth-links">
      Already have an account? <a href="login.php">Sign in</a>
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
