<?php
require_once dirname(__DIR__) . '/php/config/config.php';

/**
 * URL Helper Functions
 */
function base_url() {
    return BASE_URL;
}

function url($path = '') {
    return rtrim(base_url(), '/') . '/' . ltrim($path, '/');
}

function current_path() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return str_replace(base_url(), '', $path) ?: '/';
}

function is_current_path($path) {
    return current_path() === '/' . ltrim($path, '/');
}

function generate_slug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

/**
 * Input sanitization function
 */
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

/**
 * Session management functions
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (isAjaxRequest()) {
            jsonResponse(['error' => 'Authentication required'], 401);
        } else {
            redirect('/php/views/login.php');
        }
    }
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireAdmin() {
    if (!isAdmin()) {
        if (isAjaxRequest()) {
            jsonResponse(['error' => 'Admin access required'], 403);
        } else {
            redirect('/php/views/403.php');
        }
    }
}

/**
 * Response helpers
 */
function jsonResponse($data, $statusCode = 200) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    // Prevent PHP errors from being displayed
    @ini_set('display_errors', 0);
    
    // Set headers to prevent caching
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-Type: application/json; charset=utf-8');
    
    // Set HTTP response code
    http_response_code($statusCode);
    
    try {
        // Encode with error checking
        $json = json_encode($data);
        if ($json === false) {
            throw new Exception(json_last_error_msg());
        }
        echo $json;
    } catch (Exception $e) {
        // Log the error
        error_log("JSON encoding error: " . $e->getMessage());
        // Send a clean error response
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error occurred while processing the response'
        ]);
    }
    exit;
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Flash message helpers
 */
function setFlashMessage($message, $type = 'info') {
    startSession();
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    startSession();
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * File handling functions
 */
function validateImage($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = MAX_FILE_SIZE;

    if (!isset($file['error']) || is_array($file['error'])) {
        return 'Invalid file parameters.';
    }

    if ($file['size'] > $maxSize) {
        return 'File is too large. Maximum size is ' . ($maxSize / 1024 / 1024) . 'MB.';
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return 'Invalid file type. Allowed types: JPG, PNG, GIF.';
    }

    return true;
}

/**
 * Response formatting functions
 */
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

/**
 * Error handling functions
 */
function logError($message, $context = []) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . " - Context: " . json_encode($context));
} 