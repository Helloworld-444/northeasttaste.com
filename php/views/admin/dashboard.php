<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once dirname(dirname(__DIR__)) . '/db.php';
require_once dirname(dirname(__DIR__)) . '/models/User.php';
require_once dirname(dirname(__DIR__)) . '/models/Order.php';
require_once dirname(dirname(__DIR__)) . '/models/Dish.php';

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/php/views/login.php');
    exit();
}

// Check if user is admin
$user = new User($pdo);
$userData = $user->getUserById($_SESSION['user_id']);
if (!$userData || !$userData['is_admin']) {
    header('Location: ' . SITE_URL . '/php/views/403.php');
    exit();
}

$order = new Order($pdo);
$dish = new Dish($pdo);

// Get total orders count
$totalOrders = $order->getTotalOrders();
if ($totalOrders === false) {
    error_log("Error fetching total orders: " . $order->getLastError());
    $totalOrders = 0;
}

// Get recent orders for display
$recentOrders = $order->getOrders(5, 0);
if ($recentOrders === false) {
    error_log("Error fetching recent orders: " . $order->getLastError());
    $recentOrders = [];
}

// Get total dishes with error handling
$allDishes = $dish->getAllAvailableDishes();
if ($allDishes === false) {
    error_log("Error fetching dishes");
    $allDishes = [];
}
$totalDishes = count($allDishes);

// Calculate total revenue
try {
    $stmt = $pdo->query('SELECT COALESCE(SUM(total_amount), 0) as total_revenue FROM orders WHERE status != "cancelled"');
    $totalRevenue = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error calculating total revenue: " . $e->getMessage());
    $totalRevenue = 0;
}

// Get count of pending orders
try {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE status = ?');
    $stmt->execute(['pending']);
    $pendingOrdersCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error counting pending orders: " . $e->getMessage());
    $pendingOrdersCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/admin.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .admin-sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #fff;
            padding: 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-main {
            flex: 1;
            padding: 2rem;
            margin-left: 250px; /* Match sidebar width */
            width: calc(100% - 250px);
            overflow-x: hidden;
        }

        .dashboard-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .dashboard-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 160px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #e67e22;
        }

        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0;
            margin-top: auto;
        }

        .recent-orders {
            background: #fff;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        .recent-orders h2 {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .admin-table {
            width: 100%;
            min-width: 800px; /* Ensure table doesn't get too squished */
            border-collapse: separate;
            border-spacing: 0;
        }

        .admin-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #e9ecef;
            white-space: nowrap;
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            color: #2c3e50;
        }

        .admin-table tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            display: inline-block;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-preparing {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .action-button {
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            background-color: #fff;
            color: #2c3e50;
            border: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .action-button:hover {
            background-color: #e67e22;
            color: #fff;
            border-color: #e67e22;
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 992px) {
            .admin-main {
                margin-left: 0;
                width: 100%;
            }

            .admin-sidebar {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .quick-actions {
                width: 100%;
            }

            .action-button {
                width: 100%;
                justify-content: center;
            }
        }

        /* Add padding to the bottom for better spacing */
        body {
            padding-bottom: 2rem;
        }

        /* Ensure footer stays at bottom */
        .admin-footer {
            margin-top: auto;
            padding: 1rem;
            background-color: #fff;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <main class="admin-main">
            <div class="dashboard-header">
                <h1>Dashboard</h1>
                <div class="quick-actions">
                    <a href="dishes.php" class="action-button">
                        <i class="fas fa-plus"></i> Add New Dish
                    </a>
                    <a href="orders.php" class="action-button">
                        <i class="fas fa-list"></i> View All Orders
                    </a>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Total Orders</h3>
                    <p class="value"><?php echo number_format($totalOrders); ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Total Dishes</h3>
                    <p class="value"><?php echo number_format($totalDishes); ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <h3>Total Revenue</h3>
                    <p class="value">₹<?php echo number_format($totalRevenue, 2); ?></p>
                </div>
                
                <div class="stat-card">
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Pending Orders</h3>
                    <p class="value"><?php echo number_format($pendingOrdersCount); ?></p>
                </div>
            </div>

            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo htmlspecialchars($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                        <?php echo htmlspecialchars($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $date = !empty($order['created_at']) ? date('M d, Y', strtotime($order['created_at'])) : 'N/A';
                                    echo htmlspecialchars($date);
                                    ?>
                                </td>
                                <td>
                                    <a href="order-details.php?id=<?php echo $order['order_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOrders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No orders found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Messages Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recent Messages</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['admin_messages']) && !empty($_SESSION['admin_messages'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>User</th>
                                        <th>Message</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_reverse($_SESSION['admin_messages']) as $index => $msg): ?>
                                        <tr class="<?= $msg['read'] ? '' : 'table-warning' ?>">
                                            <td><?= htmlspecialchars($msg['timestamp']) ?></td>
                                            <td><?= htmlspecialchars($msg['user_id']) ?></td>
                                            <td><?= htmlspecialchars($msg['message']) ?></td>
                                            <td>
                                                <span class="badge <?= $msg['read'] ? 'bg-success' : 'bg-warning' ?>">
                                                    <?= $msg['read'] ? 'Read' : 'Unread' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!$msg['read']): ?>
                                                    <button class="btn btn-sm btn-primary mark-read-btn" 
                                                            data-message-index="<?= $index ?>"
                                                            onclick="markMessageAsRead(this, <?= $index ?>)">
                                                        Mark as Read
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No messages yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function markMessageAsRead(button, messageIndex) {
                fetch('<?= SITE_URL ?>/php/controllers/message_controller.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=mark_read&message_index=${messageIndex}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const row = button.closest('tr');
                        row.classList.remove('table-warning');
                        row.querySelector('.badge').className = 'badge bg-success';
                        row.querySelector('.badge').textContent = 'Read';
                        button.remove();
                    } else {
                        throw new Error(data.error || 'Failed to mark message as read');
                    }
                })
                .catch(error => {
                    alert(error.message);
                });
            }
            </script>
        </main>
    </div>

    <?php include 'footer.php'; ?>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 