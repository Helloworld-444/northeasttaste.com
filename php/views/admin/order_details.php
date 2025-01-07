<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../helpers.php';

startSession();
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . url('php/views/login.php'));
    exit;
}

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . url('php/views/admin/orders.php'));
    exit;
}

$orderId = (int)$_GET['id'];

// Initialize Order model with PDO connection
$order = new Order($pdo);

// Get order details
$orderDetails = $order->getOrderDetails($orderId);

if (!$orderDetails) {
    header('Location: ' . url('php/views/admin/orders.php'));
    exit;
}

$pageTitle = 'Admin - Order Details #' . $orderId;
require_once dirname(__DIR__) . '/header.php';
?>

<main class="container my-5">
    <div class="mb-4">
        <a href="<?= url('php/views/admin/orders.php') ?>" class="text-decoration-none">
            <i class="fas fa-arrow-left me-2"></i>Back to Orders
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Order #<?= htmlspecialchars($orderId) ?></h1>
                <span class="text-muted">
                    <?= date('F j, Y \a\t g:i A', strtotime($orderDetails['created_at'])) ?>
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <!-- Customer Information -->
                    <div class="mb-4">
                        <h2 class="h5 mb-3">Customer Information</h2>
                        <div class="card bg-light">
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Name</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($orderDetails['first_name'] . ' ' . $orderDetails['last_name']) ?></dd>
                                    
                                    <dt class="col-sm-3">Email</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($orderDetails['email']) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status -->
                    <div class="mb-4">
                        <h2 class="h5 mb-3">Order Management</h2>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="orderStatus" class="form-label">Order Status</label>
                                            <select id="orderStatus" class="form-select" data-order-id="<?= $orderId ?>">
                                                <?php
                                                $statuses = ['pending', 'confirmed', 'preparing', 'delivered', 'cancelled'];
                                                foreach ($statuses as $status):
                                                ?>
                                                    <option value="<?= $status ?>" <?= $orderDetails['status'] === $status ? 'selected' : '' ?>>
                                                        <?= ucfirst($status) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="paymentStatus" class="form-label">Payment Status</label>
                                            <select id="paymentStatus" class="form-select" data-order-id="<?= $orderId ?>">
                                                <?php
                                                $paymentStatuses = ['pending', 'paid', 'failed'];
                                                foreach ($paymentStatuses as $status):
                                                ?>
                                                    <option value="<?= $status ?>" <?= $orderDetails['payment_status'] === $status ? 'selected' : '' ?>>
                                                        <?= ucfirst($status) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="order-items mb-4">
                        <h2 class="h5 mb-3">Order Items</h2>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderDetails['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($item['name']) ?>
                                                <div class="text-muted small"><?= htmlspecialchars($item['description']) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($item['category']) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($item['quantity']) ?></td>
                                            <td class="text-end">₹<?= number_format($item['price_per_unit'], 2) ?></td>
                                            <td class="text-end">₹<?= number_format($item['price_per_unit'] * $item['quantity'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="4" class="text-end"><strong>Total Amount:</strong></td>
                                        <td class="text-end"><strong>₹<?= number_format($orderDetails['total_amount'], 2) ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Delivery Information -->
                    <div class="order-info mb-4">
                        <h2 class="h5 mb-3">Delivery Information</h2>
                        <div class="card bg-light">
                            <div class="card-body">
                                <dl class="mb-0">
                                    <dt>Delivery Address</dt>
                                    <dd class="mb-0"><?= nl2br(htmlspecialchars($orderDetails['delivery_address'])) ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Status Information -->
                    <div class="order-info">
                        <h2 class="h5 mb-3">Current Status</h2>
                        <div class="card bg-light">
                            <div class="card-body">
                                <dl class="mb-0">
                                    <dt>Order Status</dt>
                                    <dd class="mb-3">
                                        <span class="badge bg-<?= $orderDetails['status'] === 'cancelled' ? 'danger' : 'info' ?>">
                                            <?= ucfirst($orderDetails['status']) ?>
                                        </span>
                                    </dd>

                                    <dt>Payment Status</dt>
                                    <dd class="mb-0">
                                        <span class="badge bg-<?= $orderDetails['payment_status'] === 'paid' ? 'success' : ($orderDetails['payment_status'] === 'failed' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($orderDetails['payment_status']) ?>
                                        </span>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once dirname(__DIR__) . '/footer.php'; ?>

<script>
document.getElementById('orderStatus').addEventListener('change', function() {
    updateOrderStatus(this.value, this.dataset.orderId);
});

document.getElementById('paymentStatus').addEventListener('change', function() {
    updatePaymentStatus(this.value, this.dataset.orderId);
});

function updateOrderStatus(status, orderId) {
    fetch('<?= url('php/controllers/order_controller.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=updateStatus&order_id=${orderId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update order status. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the order status. Please try again.');
    });
}

function updatePaymentStatus(status, orderId) {
    fetch('<?= url('php/controllers/order_controller.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=updatePaymentStatus&order_id=${orderId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update payment status. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the payment status. Please try again.');
    });
}
</script> 