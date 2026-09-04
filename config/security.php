<?php
/**
 * Security Configuration & Functions
 */

// Start secure session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => ($_SERVER['HTTPS'] ?? false) ? true : false,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

/**
 * Check if user is authenticated
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require authentication
 */
function require_auth() {
    if (!is_authenticated()) {
        header('Location: ./login.php');
        exit;
    }
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    $timeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 1800;
    
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > $timeout) {
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Escape HTML output
 */
function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape HTML attributes
 */
function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape URL parameters
 */
function esc_url($url) {
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function get_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate email
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Log error to file
 */
function log_error($message, $context = []) {
    $log_file = (defined('LOGS_DIR') ? LOGS_DIR : './logs') . '/error-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? ' | ' . json_encode($context) : '';
    $log_message = "[$timestamp] $message$context_str\n";
    @file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Log activity
 */
function log_activity($action, $module, $reference_id = null, $description = '') {
    global $conn;
    
    if (!$conn) return false;
    
    $user_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');
    
    $stmt = $conn->prepare(
        "INSERT INTO activity_logs (user_id, action, module, reference_id, description, ip_address, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    
    if ($stmt) {
        $stmt->bind_param('ississs', $user_id, $action, $module, $reference_id, $description, $ip_address, $timestamp);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}
?>