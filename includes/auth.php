<?php
/**
 * Auth guards and session helpers.
 * Include this file on any protected page.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

/**
 * Require the user to be logged in. Redirects to login if not.
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('../login.php');
    }
}

/**
 * Require the user to have the 'admin' role.
 */
function require_admin(): void {
    require_login();
    if (current_user_role() !== 'admin') {
        set_flash('error', 'Access denied.');
        redirect('../login.php');
    }
}

/**
 * Require the user to have the 'staff' role.
 */
function require_staff(): void {
    require_login();
    if (current_user_role() !== 'staff') {
        set_flash('error', 'Access denied.');
        redirect('../login.php');
    }
}

/**
 * Require the user to have the 'customer' role.
 */
function require_customer(): void {
    require_login();
    if (current_user_role() !== 'customer') {
        set_flash('error', 'Access denied.');
        redirect('../login.php');
    }
}

/**
 * Require admin OR staff role.
 */
function require_admin_or_staff(): void {
    require_login();
    $role = current_user_role();
    if ($role !== 'admin' && $role !== 'staff') {
        set_flash('error', 'Access denied.');
        redirect('../login.php');
    }
}

/**
 * Check system status and enforce access rules.
 * Call this after require_login/require_role.
 *
 * Statuses:
 *   online      → full access for everyone
 *   offline     → only admin can access; others blocked
 *   maintenance → admin full, staff no orders, customer view-only
 */
function enforce_system_status(): void {
    $status = get_setting('system_status', 'online');
    $role = current_user_role();

    if ($role === 'admin') {
        return; // Admin always has full access
    }

    if ($status === 'offline') {
        // Destroy session for non-admin
        session_destroy();
        session_start();
        set_flash('error', 'The system is currently offline. Please try again later.');
        redirect('../login.php');
    }

    // Maintenance mode: store in session for page-level checks
    if ($status === 'maintenance') {
        $_SESSION['maintenance_mode'] = true;
    } else {
        $_SESSION['maintenance_mode'] = false;
    }
}

/**
 * Check if the system is in maintenance mode (for inline page checks).
 */
function is_maintenance(): bool {
    return !empty($_SESSION['maintenance_mode']);
}

/**
 * Get the current user data from the database.
 */
function get_current_user_data(): ?array {
    global $pdo;
    $userId = current_user_id();
    if (!$userId) return null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get badge counts for sidebar (pending orders, pending accounts).
 */
function get_sidebar_badges(): array {
    global $pdo;
    $badges = ['pending_orders' => 0, 'pending_accounts' => 0];

    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $badges['pending_orders'] = (int) $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
        $badges['pending_accounts'] = (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        // Silent fail
    }

    return $badges;
}
