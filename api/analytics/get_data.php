<?php
/**
 * API: Get Analytics Data
 * GET — { range: 'today'|'week'|'month'|'custom', from?, to? }
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in() || current_user_role() !== 'admin') {
    json_response(['success' => false, 'message' => 'Access denied.'], 403);
}

$range = $_GET['range'] ?? 'month';
$from  = $_GET['from'] ?? '';
$to    = $_GET['to'] ?? '';

switch ($range) {
    case 'today':
        $dateFrom = date('Y-m-d');
        $dateTo   = date('Y-m-d');
        break;
    case 'week':
        $dateFrom = date('Y-m-d', strtotime('-7 days'));
        $dateTo   = date('Y-m-d');
        break;
    case 'custom':
        $dateFrom = $from ?: date('Y-m-d', strtotime('-30 days'));
        $dateTo   = $to ?: date('Y-m-d');
        break;
    default: // month
        $dateFrom = date('Y-m-d', strtotime('-30 days'));
        $dateTo   = date('Y-m-d');
}

try {
    // Orders over time
    $stmt = $pdo->prepare(
        "SELECT DATE(created_at) AS day, COUNT(*) AS count, COALESCE(SUM(total_price),0) AS revenue
         FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'
         GROUP BY DATE(created_at) ORDER BY day"
    );
    $stmt->execute([$dateFrom, $dateTo]);
    $ordersOverTime = $stmt->fetchAll();

    // Top selling items
    $stmt = $pdo->prepare(
        "SELECT oi.item_name_snapshot AS name, SUM(oi.quantity) AS total_qty
         FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
         GROUP BY oi.item_name_snapshot ORDER BY total_qty DESC LIMIT 10"
    );
    $stmt->execute([$dateFrom, $dateTo]);
    $topItems = $stmt->fetchAll();

    // Orders by status
    $stmt = $pdo->prepare(
        "SELECT status, COUNT(*) AS count FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status"
    );
    $stmt->execute([$dateFrom, $dateTo]);
    $byStatus = $stmt->fetchAll();

    // Orders by category
    $stmt = $pdo->prepare(
        "SELECT COALESCE(c.category_name, 'Uncategorized') AS category, SUM(oi.quantity) AS total_qty
         FROM order_items oi
         JOIN orders o ON oi.order_id = o.id
         LEFT JOIN inventory i ON oi.item_id = i.id
         LEFT JOIN item_categories c ON i.category_id = c.id
         WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status != 'cancelled'
         GROUP BY c.category_name ORDER BY total_qty DESC"
    );
    $stmt->execute([$dateFrom, $dateTo]);
    $byCategory = $stmt->fetchAll();

    // New customers
    $stmt = $pdo->prepare(
        "SELECT DATE(created_at) AS day, COUNT(*) AS count
         FROM users WHERE role='customer' AND DATE(created_at) BETWEEN ? AND ?
         GROUP BY DATE(created_at) ORDER BY day"
    );
    $stmt->execute([$dateFrom, $dateTo]);
    $newCustomers = $stmt->fetchAll();

    // Guest vs registered
    $stmt = $pdo->prepare(
        "SELECT
           SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) AS guest,
           SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) AS registered
         FROM orders WHERE DATE(created_at) BETWEEN ? AND ?"
    );
    $stmt->execute([$dateFrom, $dateTo]);
    $guestVsReg = $stmt->fetch();

    json_response([
        'success' => true,
        'data' => [
            'orders_over_time' => $ordersOverTime,
            'top_items'        => $topItems,
            'by_status'        => $byStatus,
            'by_category'      => $byCategory,
            'new_customers'    => $newCustomers,
            'guest_vs_registered' => $guestVsReg,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo
        ]
    ]);

} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to load analytics.'], 500);
}
