<?php
// Session configuration - must be before session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1); // Enabled for HTTPS
}

// Site configuration
if (!defined('SITE_NAME')) define('SITE_NAME', 'The Northeast');
if (!defined('SITE_URL')) define('SITE_URL', 'https://your-domain.com'); // Replace with your actual domain
if (!defined('BASE_URL')) define('BASE_URL', 'https://your-domain.com'); // Replace with your actual domain

// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'your_db_name'); // Replace with your production database name
if (!defined('DB_USER')) define('DB_USER', 'your_db_user'); // Replace with your production database user
if (!defined('DB_PASS')) define('DB_PASS', 'your_db_password'); // Replace with your production database password

// Error reporting (disabled for production)
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(dirname(__DIR__)) . '/logs/error.log');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Upload directory
if (!defined('UPLOAD_DIR')) {
    $uploadDir = dirname(dirname(__DIR__)) . '/uploads';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true); // More restrictive permissions for production
    }
    define('UPLOAD_DIR', $uploadDir);
}

// Maximum upload size
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Allowed image types
if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', [
        'image/jpeg',
        'image/png',
        'image/gif'
    ]);
}

// Order statuses
if (!defined('ORDER_STATUSES')) {
    define('ORDER_STATUSES', [
        'pending',
        'confirmed',
        'preparing',
        'delivered',
        'cancelled'
    ]);
}

// Payment methods
if (!defined('PAYMENT_METHODS')) {
    define('PAYMENT_METHODS', [
        'cash',
        'card',
        'upi'
    ]);
}

// Payment statuses
if (!defined('PAYMENT_STATUSES')) {
    define('PAYMENT_STATUSES', [
        'pending',
        'completed',
        'failed',
        'refunded'
    ]);
}

// Dish categories
if (!defined('DISH_CATEGORIES')) {
    define('DISH_CATEGORIES', [
        'Manipuri',
        'Assamese',
        'NagaLand',
        'Mizoram',
        'Meghalaya',
        'Tripura',
        'Sikkim',
        'Arunachal Pradesh'
    ]);
}

// Security headers
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
} 