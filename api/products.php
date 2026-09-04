<?php
/**
 * API Endpoint: Get Products with Pagination, Search, and Filters
 */

header('Content-Type: application/json');

require_once('../config/database.php');
require_once('../includes/functions.php');

// Get connection
$db = Database::getInstance();
$conn = $db->getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get parameters
$page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
$page_size = isset($_POST['page_size']) ? (int)$_POST['page_size'] : 20;
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

$offset = ($page - 1) * $page_size;

try {
    // Build WHERE clause
    $where_conditions = ["p.delete_flag = 0"];
    $bind_types = "";
    $bind_params = [];

    // Search filter
    if (!empty($search)) {
        $where_conditions[] = "(p.product_code LIKE ? OR p.name LIKE ? OR p.barcode LIKE ?)";
        $search_term = "%" . $search . "%";
        $bind_types .= "sss";
        $bind_params[] = $search_term;
        $bind_params[] = $search_term;
        $bind_params[] = $search_term;
    }

    // Category filter
    if (!empty($category) && is_numeric($category)) {
        $where_conditions[] = "p.category_id = ?";
        $bind_types .= "i";
        $bind_params[] = (int)$category;
    }

    // Status filter
    if ($status !== '' && is_numeric($status)) {
        $where_conditions[] = "p.status = ?";
        $bind_types .= "i";
        $bind_params[] = (int)$status;
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM `product_list` p WHERE {$where_clause}";
    $count_stmt = $conn->prepare($count_sql);

    if (!empty($bind_params)) {
        $count_stmt->bind_param($bind_types, ...$bind_params);
    }

    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_products = $count_row['total'];
    $total_pages = ceil($total_products / $page_size);
    $count_stmt->close();

    // Get paginated products
    $sql = "SELECT p.product_id, p.product_code, p.barcode, p.category_id, p.name, p.description,
                   p.cost_price, p.price, p.discount_percent, p.stock, p.alert_restock, p.image, p.status,
                   COALESCE(c.name, 'Unassigned') as category_name
            FROM `product_list` p
            LEFT JOIN `category_list` c ON p.category_id = c.category_id
            WHERE {$where_clause}
            ORDER BY p.name ASC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Add pagination params
    $bind_types_final = $bind_types . "ii";
    $bind_params[] = $page_size;
    $bind_params[] = $offset;

    $stmt->bind_param($bind_types_final, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $row['image_path'] = get_product_image_path($row['image']);
        $row['stock_status'] = get_stock_status($row['stock'], $row['alert_restock']);
        $products[] = $row;
    }

    $stmt->close();

    // Return JSON response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'total_products' => $total_products,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'page_size' => $page_size
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>