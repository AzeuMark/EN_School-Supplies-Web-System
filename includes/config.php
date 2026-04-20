<?php
/**
 * Core configuration loader.
 * Reads config.json, creates PDO connection, starts session, sets timezone.
 */

// Prevent direct access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Load config.json ──
define('BASE_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
$configFile = BASE_PATH . 'config.json';

if (!file_exists($configFile)) {
    die('Configuration file not found.');
}

$config = json_decode(file_get_contents($configFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die('Invalid configuration file.');
}

// ── Database connection ──
$dbConf = $config['database'];

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $dbConf['host'],
        $dbConf['name'],
        $dbConf['charset']
    );
    $pdo = new PDO($dsn, $dbConf['user'], $dbConf['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// ── AES config ──
define('AES_KEY', $config['aes']['key']);
define('AES_IV',  $config['aes']['iv']);

// ── Timezone ──
// Try to read from DB first, fallback to config.json
try {
    $tzStmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'timezone' LIMIT 1");
    $tzStmt->execute();
    $tzRow = $tzStmt->fetch();
    $timezone = $tzRow ? $tzRow['setting_value'] : ($config['system']['timezone'] ?? 'Asia/Manila');
} catch (Exception $e) {
    $timezone = $config['system']['timezone'] ?? 'Asia/Manila';
}
date_default_timezone_set($timezone);

// ── Admin seed check ──
// On first run, encrypt the default admin password if it's still plain text
try {
    $adminStmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
    $adminStmt->execute([$config['admin']['default_username']]);
    $adminRow = $adminStmt->fetch();

    if ($adminRow && $adminRow['password'] === $config['admin']['default_password']) {
        require_once __DIR__ . '/aes.php';
        $encrypted = aes_encrypt($config['admin']['default_password']);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->execute([$encrypted, $adminRow['id']]);
    }
} catch (Exception $e) {
    // Silently fail on seed check — table may not exist yet
}

// ── Helper function to get a system setting ──
function get_setting(string $key, string $default = ''): string {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// ── Helper function to set a system setting ──
function set_setting(string $key, string $value): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}
