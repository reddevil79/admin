<?php
/**
 * Application Configuration
 */

define('APP_NAME', 'Inventory Management System');
define('APP_VERSION', '2.0.0');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');

// Paths
define('APP_ROOT', dirname(dirname(__FILE__)));
define('UPLOADS_DIR', APP_ROOT . '/uploads');
define('PRODUCTS_IMG_DIR', UPLOADS_DIR . '/products');
define('LOGS_DIR', APP_ROOT . '/logs');

// Security
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('PASSWORD_MIN_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// File Upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Pagination
define('ITEMS_PER_PAGE', 20);
define('DEFAULT_TIMEZONE', 'UTC');

date_default_timezone_set(DEFAULT_TIMEZONE);

// Create required directories
if (!file_exists(UPLOADS_DIR)) {
    @mkdir(UPLOADS_DIR, 0755, true);
}
if (!file_exists(PRODUCTS_IMG_DIR)) {
    @mkdir(PRODUCTS_IMG_DIR, 0755, true);
}
if (!file_exists(LOGS_DIR)) {
    @mkdir(LOGS_DIR, 0755, true);
}
?>