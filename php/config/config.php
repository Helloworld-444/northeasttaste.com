<?php
// Session configuration - must be before session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
}

// Site configuration
if (!defined('SITE_NAME')) define('SITE_NAME', 'The Northeast');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/fooddelivery');
if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost/fooddelivery');

// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'fooddelivery');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Upload directory
if (!defined('UPLOAD_DIR')) {
    $uploadDir = dirname(dirname(__DIR__)) . '/uploads';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
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