<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/User.php';

// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// Get order ID from URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) {
    header('Location: orders.php');
    exit();
}

// Get order details
$order = new Order($pdo);
$orderDetails = $order->getOrderDetails($orderId);

if (!$orderDetails) {
    header('Location: orders.php');
    exit();
}

ob_start();
?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>

    <main class="admin-main">
        <div class="dashboard-header">
            <h1>Order Details #<?php echo $orderId; ?></h1>
            <div class="quick-actions">
                <a href="orders.php" class="action-button">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Order ID:</th>
                                <td>#<?php echo htmlspecialchars($orderDetails['order_id']); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($orderDetails['status']); ?>">
                                        <?php echo htmlspecialchars($orderDetails['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Payment Status:</th>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($orderDetails['payment_status']); ?>">
                                        <?php echo htmlspecialchars($orderDetails['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Total Amount:</th>
                                <td>₹<?php echo number_format($orderDetails['total_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Order Date:</th>
                                <td><?php echo !empty($orderDetails['created_at']) ? date('M d, Y h:i A', strtotime($orderDetails['created_at'])) : 'N/A'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Name:</th>
                                <td><?php 
                                    $firstName = isset($orderDetails['first_name']) ? $orderDetails['first_name'] : 'Guest';
                                    $lastName = isset($orderDetails['last_name']) ? $orderDetails['last_name'] : '';
                                    echo htmlspecialchars(trim($firstName . ' ' . $lastName));
                                ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars(isset($orderDetails['email']) ? $orderDetails['email'] : 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Delivery Address:</th>
                                <td><?php echo nl2br(htmlspecialchars($orderDetails['delivery_address'] ?? 'No address provided')); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Order Items</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderDetails['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td>₹<?php echo number_format($item['price_per_unit'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td class="text-end">₹<?php echo number_format($item['price_per_unit'] * $item['quantity'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                <td class="text-end"><strong>₹<?php echo number_format($orderDetails['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .admin-container {
        display: flex;
        min-height: 100vh;
        background-color: #f8f9fa;
    }

    .admin-main {
        flex: 1;
        padding: 2rem;
        margin-left: 250px;
        width: calc(100% - 250px);
    }

    .card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 1.5rem;
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        padding: 1rem 1.5rem;
    }

    .card-title {
        color: #2c3e50;
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
    }

    .card-body {
        padding: 1.5rem;
    }

    .table {
        margin-bottom: 0;
    }

    .table th {
        font-weight: 600;
        color: #2c3e50;
    }

    .table td {
        vertical-align: middle;
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

    .text-muted {
        color: #6c757d;
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
    }

    .action-button:hover {
        background-color: #e67e22;
        color: #fff;
        border-color: #e67e22;
    }

    @media (max-width: 768px) {
        .admin-main {
            margin-left: 0;
            width: 100%;
            padding: 1rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .table-responsive {
            margin: 0 -1rem;
        }
    }
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?> 