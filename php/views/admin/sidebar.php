<?php
// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="admin-sidebar">
    <div class="admin-logo">
        <h2>Admin Panel</h2>
    </div>
    <ul class="admin-menu">
        <li>
            <a href="/fooddelivery/php/views/admin/dashboard.php" 
               class="<?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                Dashboard
            </a>
        </li>
        <li>
            <a href="/fooddelivery/php/views/admin/orders.php"
               class="<?php echo $currentPage === 'orders' ? 'active' : ''; ?>">
                Orders
            </a>
        </li>
        <li>
            <a href="/fooddelivery/php/views/admin/dishes.php"
               class="<?php echo $currentPage === 'dishes' ? 'active' : ''; ?>">
                Dishes
            </a>
        </li>
        <li>
            <a href="/fooddelivery/php/views/admin/categories.php"
               class="<?php echo $currentPage === 'categories' ? 'active' : ''; ?>">
                Categories
            </a>
        </li>
        <li>
            <a href="/fooddelivery/php/views/admin/users.php"
               class="<?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                Users
            </a>
        </li>
        <li class="menu-divider"></li>
        <li>
            <a href="/fooddelivery/php/views/index.php">View Site</a>
        </li>
        <li>
            <a href="/fooddelivery/php/controllers/auth_controller.php?action=logout">Logout</a>
        </li>
    </ul>
</nav> 