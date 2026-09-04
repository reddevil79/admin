<?php
/**
 * Common Helper Functions
 */

/**
 * Format number with thousands separator
 */
function format_num($number = 0, $decimals = 2) {
    if (!is_numeric($number)) {
        return '0';
    }
    return number_format((float)$number, (int)$decimals);
}

/**
 * Format currency for display
 */
function format_currency($amount, $symbol = 'Rs. ') {
    return $symbol . format_num($amount, 2);
}

/**
 * Get stock status
 */
function get_stock_status($current_stock, $reorder_level) {
    $current_stock = (float)$current_stock;
    $reorder_level = (float)$reorder_level;
    
    if ($current_stock <= 0) {
        return ['status' => 'OUT_OF_STOCK', 'label' => 'Out of Stock', 'badge' => 'danger'];
    } elseif ($current_stock <= $reorder_level) {
        return ['status' => 'LOW_STOCK', 'label' => 'Low Stock', 'badge' => 'warning'];
    } else {
        return ['status' => 'IN_STOCK', 'label' => 'In Stock', 'badge' => 'success'];
    }
}

/**
 * Convert array to query string
 */
function build_query_string($params) {
    $query_parts = [];
    foreach ($params as $key => $value) {
        if ($value !== null && $value !== '') {
            $query_parts[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    return implode('&', $query_parts);
}

/**
 * Get image path with fallback
 */
function get_product_image_path($image_path) {
    if (empty($image_path)) {
        return 'images/no-image.png';
    }
    
    $filename = basename($image_path);
    $paths_to_check = [
        $image_path,
        'uploads/products/' . $filename,
        'images/products/' . $filename,
        'admin/uploads/products/' . $filename,
        'admin/images/products/' . $filename
    ];
    
    foreach ($paths_to_check as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return 'images/no-image.png';
}

/**
 * Get user by ID
 */
function get_user_by_id($user_id) {
    global $conn;
    
    if (!$conn) return null;
    
    $stmt = $conn->prepare("SELECT * FROM user_list WHERE user_id = ? LIMIT 1");
    if (!$stmt) return null;
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

/**
 * Get current user
 */
function get_current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return get_user_by_id($_SESSION['user_id']);
}

/**
 * Generate unique receipt number
 */
function generate_receipt_number($conn) {
    $receipt_no = time();
    $i = 0;
    
    while (true) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transaction_list WHERE receipt_no = ?");
        if (!$stmt) break;
        
        $current_no = (string)$receipt_no;
        $stmt->bind_param('s', $current_no);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['count'] == 0) {
            return $current_no;
        }
        
        $i++;
        $receipt_no = time() . $i;
    }
    
    return (string)$receipt_no;
}

/**
 * Format date
 */
function format_date($date_string, $format = 'Y-m-d H:i:s') {
    if (empty($date_string)) return '';
    try {
        $date = new DateTime($date_string);
        return $date->format($format);
    } catch (Exception $e) {
        return $date_string;
    }
}

/**
 * Get days ago string
 */
function get_time_ago($datetime) {
    $time_ago = strtotime($datetime);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    
    if ($time_difference < 60) {
        return $time_difference . ' seconds ago';
    } elseif ($time_difference < 3600) {
        return floor($time_difference / 60) . ' minutes ago';
    } elseif ($time_difference < 86400) {
        return floor($time_difference / 3600) . ' hours ago';
    } elseif ($time_difference < 604800) {
        return floor($time_difference / 86400) . ' days ago';
    } else {
        return date('M d, Y', $time_ago);
    }
}
?>