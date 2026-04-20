<?php
/**
 * API: Get Inventory Items
 * GET — returns JSON list of items with category names
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$search   = sanitize($_GET['search'] ?? '');
$category = (int)($_GET['category'] ?? 0);
$status   = $_GET['status'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = (int)($_GET['per_page'] ?? 20);
$all      = isset($_GET['all']);

$where  = [];
$params = [];

if ($search) {
    $where[]  = "i.item_name LIKE ?";
    $params[] = "%$search%";
}

if ($category > 0) {
    $where[]  = "i.category_id = ?";
    $params[] = $category;
}

if ($status === 'no_stock') {
    $where[] = "i.stock_count = 0";
} elseif ($status === 'low_stock') {
    $where[] = "i.stock_count > 0 AND i.stock_count <= GREATEST(1, CEIL(i.max_order_qty * 0.1))";
} elseif ($status === 'on_stock') {
    $where[] = "i.stock_count > GREATEST(1, CEIL(i.max_order_qty * 0.1))";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory i $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch items
    $limitSQL = '';
    if (!$all) {
        $offset   = ($page - 1) * $perPage;
        $limitSQL = "LIMIT $perPage OFFSET $offset";
    }

    $sql = "SELECT i.*, c.category_name
            FROM inventory i
            LEFT JOIN item_categories c ON i.category_id = c.id
            $whereSQL
            ORDER BY i.item_name ASC
            $limitSQL";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    // Add computed status
    foreach ($items as &$item) {
        $stock = (int)$item['stock_count'];
        $threshold = max(1, ceil((int)$item['max_order_qty'] * 0.1));
        if ($stock <= 0) {
            $item['item_status'] = 'No Stock';
        } elseif ($stock <= $threshold) {
            $item['item_status'] = 'Low Stock';
        } else {
            $item['item_status'] = 'On Stock';
        }
    }
    unset($item);

    json_response([
        'success'    => true,
        'items'      => $items,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'total_pages' => $all ? 1 : max(1, ceil($total / $perPage))
    ]);

} catch (Exception $e) {
    json_response(['success' => false, 'message' => 'Failed to load items.'], 500);
}
