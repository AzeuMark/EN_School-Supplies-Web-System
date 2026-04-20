<?php
/**
 * Shared utility helpers.
 */

/**
 * Sanitize user input — trim and escape HTML entities.
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format a number as Philippine Peso price string.
 */
function format_price($amount): string {
    return '₱' . number_format((float) $amount, 2);
}

/**
 * Generate a unique order code like ORD-00042.
 */
function generate_order_code(): string {
    global $pdo;

    try {
        $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM orders");
        $row  = $stmt->fetch();
        $next = ($row['max_id'] ?? 0) + 1;
    } catch (Exception $e) {
        $next = mt_rand(1, 99999);
    }

    return 'ORD-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate a CSRF token and store in session.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field for forms.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * Validate the submitted CSRF token against the session token.
 */
function csrf_validate(): bool {
    $submitted = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (empty($submitted) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submitted);
}

/**
 * Send a JSON response and exit.
 */
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/**
 * Redirect to a URL and exit (PRG pattern).
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Set a flash message in the session.
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message.
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get the currently logged-in user's ID, or null.
 */
function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get the currently logged-in user's role, or null.
 */
function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if any user is currently logged in.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}
