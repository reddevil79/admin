<?php
/**
 * API Endpoint: Dashboard Statistics
 */

header('Content-Type: application/json');

require_once('../config/database.php');
require_once('../config/security.php');
require_once('../includes/functions.php');

if (!is_authenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Category count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM category_list WHERE delete_flag = 0");
    $stmt->execute();
    $categories = (int)$stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    // Product count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_list WHERE delete_flag = 0");
    $stmt->execute();
    $products = (int)$stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    // Today's sales
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(total), 0) as total FROM transaction_list
         WHERE DATE(date_added) = CURDATE() AND status = 'completed'"
    );
    $stmt->execute();
    $today_sales = (float)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Out of stock count
    $stmt = $conn->prepare(
        "SELECT COUNT(*) as count FROM product_list
         WHERE stock <= 0 AND delete_flag = 0 AND status = 1"
    );
    $stmt->execute();
    $out_of_stock = (int)$stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    // Low stock count
    $stmt = $conn->prepare(
        "SELECT COUNT(*) as count FROM product_list
         WHERE stock > 0 AND stock <= alert_restock AND delete_flag = 0 AND status = 1"
    );
    $stmt->execute();
    $low_stock = (int)$stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();

    // Total profit today
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(ti.quantity * (ti.price - p.cost_price)), 0) as profit
         FROM transaction_items ti
         JOIN product_list p ON ti.product_id = p.product_id
         JOIN transaction_list t ON ti.transaction_id = t.transaction_id
         WHERE DATE(t.date_added) = CURDATE() AND t.status = 'completed'"
    );
    $stmt->execute();
    $today_profit = (float)$stmt->get_result()->fetch_assoc()['profit'];
    $stmt->close();

    // Top selling products (today)
    $stmt = $conn->prepare(
        "SELECT p.product_id, p.name, p.product_code, SUM(ti.quantity) as total_qty
         FROM transaction_items ti
         JOIN product_list p ON ti.product_id = p.product_id
         JOIN transaction_list t ON ti.transaction_id = t.transaction_id
         WHERE DATE(t.date_added) = CURDATE() AND t.status = 'completed'
         GROUP BY p.product_id
         ORDER BY total_qty DESC
         LIMIT 5"
    );
    $stmt->execute();
    $top_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'categories' => $categories,
            'products' => $products,
            'today_sales' => $today_sales,
            'today_profit' => $today_profit,
            'out_of_stock' => $out_of_stock,
            'low_stock' => $low_stock,
            'top_products' => $top_products
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>