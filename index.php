<?php
/**
 * Main entry point for the application
 */
require_once __DIR__ . '/php/config/config.php';
require_once __DIR__ . '/php/helpers.php';

// Start session if not already started
startSession();

// Define routes
$routes = [
    '/' => '/php/views/home.php',
    '/index.php' => '/php/views/home.php',
    '/menu' => '/php/views/menu.php',
    '/dish' => '/php/views/dish_details.php',
    '/login' => '/php/views/login.php',
    '/profile' => '/php/views/profile.php',
    '/orders' => '/php/views/orders.php',
    '/order' => '/php/views/order_details.php',
    '/cart' => '/php/views/cart.php',
    '/checkout' => '/php/views/checkout.php',
    '/payment' => '/php/views/payment_details.php',
    '/about' => '/php/views/about.php',
    '/contact' => '/php/views/contact.php',
    '/terms' => '/php/views/terms.php',
    '/privacy' => '/php/views/privacy.php',
    '/logout' => '/php/controllers/auth_controller.php?action=logout'
];

// Protected routes that require authentication
$protected_routes = [
    '/profile',
    '/orders',
    '/order',
    '/cart',
    '/checkout',
    '/payment'
];

// Admin-only routes
$admin_routes = [
    '/admin/dashboard',
    '/admin/users',
    '/admin/dishes',
    '/admin/orders',
    '/admin/payments'
];

// Get the current path
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/fooddelivery', '', $path); // Remove the subdirectory from path

// If path is empty or just '/', set it to root
if (empty($path) || $path === '/') {
    $path = '/';
}

// Check if the current route requires authentication
if (in_array($path, $protected_routes) && !isLoggedIn()) {
    $_SESSION['flash_message'] = 'Please log in to access this page.';
    $_SESSION['flash_type'] = 'warning';
    $_SESSION['redirect_after_login'] = $path;
    header('Location: ' . url('/login'));
    exit();
}

// Check if the current route is admin-only
if (strpos($path, '/admin/') === 0 && (!isLoggedIn() || !isAdmin())) {
    $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ' . url('/'));
    exit();
}

// Check if route exists
if (isset($routes[$path])) {
    $view_file = __DIR__ . $routes[$path];
    if (file_exists($view_file)) {
        require_once $view_file;
    } else {
        // Log the error
        error_log("Error: View file not found: {$view_file}");
        
        // Show user-friendly error page
        http_response_code(404);
        $pageTitle = 'Page Not Found';
        require_once __DIR__ . '/php/views/header.php';
        echo '<div class="container my-5 text-center">';
        echo '<h1>404 - Page Not Found</h1>';
        echo '<p>The page you are looking for could not be found.</p>';
        echo '<a href="' . url('/') . '" class="btn btn-primary">Return to Home</a>';
        echo '</div>';
        require_once __DIR__ . '/php/views/footer.php';
    }
} else {
    // For undefined routes, show 404 page
    http_response_code(404);
    $pageTitle = 'Page Not Found';
    require_once __DIR__ . '/php/views/header.php';
    echo '<div class="container my-5 text-center">';
    echo '<h1>404 - Page Not Found</h1>';
    echo '<p>The page you are looking for could not be found.</p>';
    echo '<a href="' . url('/') . '" class="btn btn-primary">Return to Home</a>';
    echo '</div>';
    require_once __DIR__ . '/php/views/footer.php';
} 