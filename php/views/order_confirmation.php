<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../db.php';

// Start session and check login status
startSession();

if (!isLoggedIn()) {
    header('Location: /fooddelivery/php/views/login.php');
    exit();
}

// Get order ID from URL
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$orderId) {
    header('Location: /fooddelivery/php/views/404.php');
    exit();
}

try {
    // Initialize Order model directly
    $orderModel = new Order($pdo);

    // Get order details
    $order = $orderModel->getOrderDetails($orderId);

    if (!$order) {
        throw new Exception('Failed to retrieve order details');
    }

    // Verify the order belongs to the current user
    if ($order['user_id'] != $_SESSION['user_id']) {
        header('Location: /fooddelivery/php/views/403.php');
        exit();
    }
} catch (Exception $e) {
    error_log("Error in order confirmation: " . $e->getMessage());
    header('Location: /fooddelivery/php/views/500.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/fooddelivery/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container py-4">
        <div class="order-confirmation bg-white rounded shadow-sm p-4">
            <div class="text-center mb-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle text-success fa-3x"></i>
                </div>
                <h1 class="h3 mb-2">Order Confirmed!</h1>
                <p class="text-success mb-1">Thank you for your order</p>
                <p class="text-muted">Order ID: #<?php echo htmlspecialchars($orderId); ?></p>
            </div>

            <div class="order-details">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="h5 mb-0">Delivery Information</h2>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="h5 mb-0">Order Items</h2>
                    </div>
                    <div class="card-body p-0">
                        <?php if (isset($order['items']) && is_array($order['items'])): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        <?php foreach ($order['items'] as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex flex-column">
                                                        <span class="font-weight-bold"><?php echo htmlspecialchars($item['name']); ?></span>
                                                        <small class="text-muted"><?php echo htmlspecialchars($item['category']); ?></small>
                                                    </div>
                                                </td>
                                                <td class="text-center">×<?php echo $item['quantity']; ?></td>
                                                <td class="text-right">₹<?php echo number_format($item['price_per_unit'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h2 class="h5 mb-0">Order Summary</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Order Status</span>
                            <span class="badge badge-<?php echo strtolower($order['status']) === 'pending' ? 'warning' : 'success'; ?>">
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Method</span>
                            <span><?php echo ucfirst(htmlspecialchars($order['payment_method'] ?? 'Not specified')); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Payment Status</span>
                            <span class="badge badge-<?php echo strtolower($order['payment_status']) === 'pending' ? 'warning' : 'success'; ?>">
                                <?php echo ucfirst(htmlspecialchars($order['payment_status'])); ?>
                            </span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total Amount</strong>
                            <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <a href="/fooddelivery/php/views/index.php" class="btn btn-primary me-2">
                        <i class="fas fa-shopping-cart me-2"></i>Continue Shopping
                    </a>
                    <a href="/fooddelivery/php/views/orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>View All Orders
                    </a>
                </div>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <style>
    .order-confirmation {
        max-width: 800px;
        margin: 0 auto;
    }
    .success-icon {
        color: #28a745;
    }
    .badge {
        padding: 0.5em 1em;
        border-radius: 4px;
        font-weight: 500;
    }
    .badge-warning {
        background-color: #ffc107;
        color: #000;
    }
    .badge-success {
        background-color: #28a745;
        color: #fff;
    }
    .card {
        border: 1px solid rgba(0,0,0,.125);
        border-radius: 0.25rem;
        margin-bottom: 1rem;
    }
    .card-header {
        padding: 0.75rem 1.25rem;
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0,0,0,.125);
    }
    .card-body {
        padding: 1.25rem;
    }
    .table {
        width: 100%;
        margin-bottom: 0;
    }
    .table td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid #dee2e6;
    }
    .table tr:first-child td {
        border-top: none;
    }
    .btn {
        display: inline-block;
        font-weight: 500;
        text-align: center;
        vertical-align: middle;
        user-select: none;
        padding: 0.5rem 1rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: all 0.15s ease-in-out;
        text-decoration: none;
        margin: 0 0.25rem;
    }
    .btn-primary {
        color: #fff;
        background-color: #007bff;
        border: 1px solid #007bff;
    }
    .btn-outline-secondary {
        color: #6c757d;
        border: 1px solid #6c757d;
        background-color: transparent;
    }
    .btn:hover {
        opacity: 0.9;
    }
    .me-2 {
        margin-right: 0.5rem;
    }
    .mb-4 {
        margin-bottom: 1.5rem;
    }
    .py-4 {
        padding-top: 1.5rem;
        padding-bottom: 1.5rem;
    }
    .text-success {
        color: #28a745;
    }
    .text-muted {
        color: #6c757d;
    }
    .font-weight-bold {
        font-weight: 600;
    }
    .h3 {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .h5 {
        font-size: 1.25rem;
        margin-bottom: 0;
        font-weight: 500;
    }
    </style>
</body>
</html> 