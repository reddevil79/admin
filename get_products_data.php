<?php
require_once("DBConnection.php");

header('Content-Type: application/json');

// Get parameters
$page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
$page_size = isset($_POST['page_size']) ? (int)$_POST['page_size'] : 20;
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Calculate offset
$offset = ($page - 1) * $page_size;

try {
    // Build WHERE clause
    $where_conditions = ["p.delete_flag = 0"];
    $bind_types = "";
    $bind_params = [];

    // Search filter
    if (!empty($search)) {
        $where_conditions[] = "(p.product_code LIKE ? OR p.name LIKE ? OR p.description LIKE ?)";
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
    $sql = "SELECT p.product_id, p.product_code, p.category_id, p.name, p.description, p.price, 
                   p.stock, p.image, p.alert_restock, p.status, 
                   COALESCE(c.name, 'Unassigned') as cname,
                   CASE 
                       WHEN p.image IS NOT NULL AND p.image != '' THEN CONCAT('images/products/', SUBSTRING_INDEX(p.image, '/', -1))
                       ELSE 'images/no-image.png'
                   END as image_path
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
        // Ensure image path exists, fallback to no-image
        $image_path = 'images/no-image.png';
        
        $filename = basename($row['image']);
        $paths_to_check = [
            'images/products/' . $filename,
            'admin/images/products/' . $filename,
            'uploads/products/' . $filename,
            'admin/uploads/products/' . $filename
        ];
        
        foreach ($paths_to_check as $p) {
            if (!empty($row['image']) && file_exists($p)) {
                $image_path = $p;
                break;
            }
        }
        
        $row['image_path'] = $image_path;
        $products[] = $row;
    }

    $stmt->close();

    // Return JSON response
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total_products' => $total_products,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'page_size' => $page_size
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
