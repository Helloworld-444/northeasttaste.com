<?php
require_once __DIR__ . '/../helpers.php';
startSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Northeast - Authentic Northeast Indian Cuisine</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= url('css/style.css') ?>" rel="stylesheet">
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all dropdowns
            document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    var dropdown = new bootstrap.Dropdown(element);
                    dropdown.toggle();
                });
            });

            // Initialize cart count
            updateCartCount();
        });

        // Function to update cart count
        function updateCartCount() {
            fetch('<?= url('php/controllers/cart_controller.php?action=count') ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cartCount').textContent = data.count;
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        }
    </script>
</head>
<body>
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <!-- Logo -->
                <a class="navbar-brand" href="<?= url('/') ?>">
                    <img src="<?= url('images/logo.png') ?>" alt="The Northeast Logo" height="40">
                    The Northeast
                </a>

                <!-- Mobile Toggle Button -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <!-- Navigation Items -->
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('/') ? 'active' : '' ?>" href="<?= url('/') ?>">
                                <i class="fas fa-home"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('menu') ? 'active' : '' ?>" href="<?= url('php/views/menu.php') ?>">
                                <i class="fas fa-utensils"></i> Menu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('about') ? 'active' : '' ?>" href="<?= url('php/views/about.php') ?>">
                                <i class="fas fa-info-circle"></i> About
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('contact') ? 'active' : '' ?>" href="<?= url('php/views/contact.php') ?>">
                                <i class="fas fa-envelope"></i> Contact
                            </a>
                        </li>
                    </ul>

                    <!-- User Menu -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Cart -->
                        <li class="nav-item">
                            <a class="nav-link <?= is_current_path('cart') ? 'active' : '' ?>" href="<?= url('php/views/cart.php') ?>">
                                <i class="fas fa-shopping-cart"></i> Cart
                                <span class="cart-count badge bg-primary" id="cartCount">0</span>
                            </a>
                        </li>

                        <?php if (isLoggedIn()): ?>
                            <!-- Logged In User Menu -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user"></i> My Account
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li>
                                        <a class="dropdown-item" href="<?= url('php/views/profile.php') ?>">
                                            <i class="fas fa-user-circle"></i> Profile
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('php/views/orders.php') ?>">
                                            <i class="fas fa-list"></i> My Orders
                                        </a>
                                    </li>
                                    <?php if (isAdmin()): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="<?= url('php/views/admin/dashboard.php') ?>">
                                                <i class="fas fa-cog"></i> Admin Panel
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?= url('php/controllers/auth_controller.php?action=logout') ?>">
                                            <i class="fas fa-sign-out-alt"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <!-- Login/Register Link -->
                            <li class="nav-item">
                                <a class="nav-link <?= is_current_path('login') ? 'active' : '' ?>" href="<?= url('php/views/login.php') ?>">
                                    <i class="fas fa-sign-in-alt"></i> Login / Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show m-3">
            <?= $_SESSION['flash_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php 
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>
</body>
</html> 