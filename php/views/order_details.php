<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../helpers.php';

startSession();
if (!isLoggedIn()) {
    header('Location: ' . url('php/views/login.php'));
    exit;
}

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . url('php/views/orders.php'));
    exit;
}

$orderId = (int)$_GET['id'];

// Initialize Order model with PDO connection
$order = new Order($pdo);

// Get order details
$orderDetails = $order->getOrderById($orderId);

// Check if order exists and belongs to current user
if (!$orderDetails || $orderDetails['user_id'] !== $_SESSION['user_id']) {
    header('Location: ' . url('php/views/orders.php'));
    exit;
}

// Get order items
$orderItems = $order->getOrderItems($orderId);

$pageTitle = 'Order Details #' . $orderId . ' - ' . SITE_NAME;
require_once 'header.php';
?>

<main class="container my-5">
    <div class="mb-4">
        <a href="<?= url('php/views/orders.php') ?>" class="text-decoration-none">
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
                    <!-- Order Status -->
                    <?php if ($orderDetails['status'] !== 'cancelled'): ?>
                        <div class="order-progress mb-4">
                            <?php
                            $stages = ['pending', 'confirmed', 'preparing', 'delivered'];
                            $currentStageIndex = array_search($orderDetails['status'], $stages);
                            $statusLabels = [
                                'pending' => 'Order Placed',
                                'confirmed' => 'Confirmed',
                                'preparing' => 'Preparing',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled'
                            ];
                            foreach ($stages as $index => $stage):
                                $isCompleted = $index <= $currentStageIndex;
                                $isCurrent = $index === $currentStageIndex;
                            ?>
                                <div class="progress-stage <?= $isCompleted ? 'completed' : '' ?> <?= $isCurrent ? 'current' : '' ?>">
                                    <div class="stage-dot"></div>
                                    <span class="stage-label"><?= $statusLabels[$stage] ?></span>
                                </div>
                                <?php if ($index < count($stages) - 1): ?>
                                    <div class="progress-line <?= $isCompleted ? 'completed' : '' ?>"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger mb-4">
                            <i class="fas fa-times-circle me-2"></i>This order has been cancelled
                        </div>
                    <?php endif; ?>

                    <!-- Order Items -->
                    <div class="order-items mb-4">
                        <h2 class="h5 mb-3">Order Items</h2>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['name']) ?></td>
                                            <td class="text-center"><?= htmlspecialchars($item['quantity']) ?></td>
                                            <td class="text-end">₹<?= number_format($item['price_per_unit'], 2) ?></td>
                                            <td class="text-end">₹<?= number_format($item['price_per_unit'] * $item['quantity'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                        <td class="text-end"><strong>₹<?= number_format($orderDetails['total_amount'], 2) ?></strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Order Information -->
                    <div class="order-info">
                        <h2 class="h5 mb-3">Order Information</h2>
                        <div class="card bg-light">
                            <div class="card-body">
                                <dl class="mb-0">
                                    <dt>Delivery Address</dt>
                                    <dd class="mb-3"><?= nl2br(htmlspecialchars($orderDetails['delivery_address'])) ?></dd>

                                    <dt>Payment Status</dt>
                                    <dd class="mb-3">
                                        <span class="badge bg-<?= $orderDetails['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($orderDetails['payment_status']) ?>
                                        </span>
                                    </dd>

                                    <dt>Order Status</dt>
                                    <dd class="mb-0">
                                        <span class="badge bg-<?= $orderDetails['status'] === 'cancelled' ? 'danger' : 'info' ?>">
                                            <?= ucfirst($orderDetails['status']) ?>
                                        </span>
                                    </dd>
                                </dl>
                            </div>
                        </div>

                        <?php if ($orderDetails['status'] === 'pending'): ?>
                            <div class="mt-3">
                                <button onclick="cancelOrder(<?= $orderId ?>)" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-times me-2"></i>Cancel Order
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'footer.php'; ?>

<style>
/* Reuse the same styles from orders.php */
.order-progress {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 0;
    position: relative;
}

.progress-stage {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 1;
}

.stage-dot {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background-color: #e9ecef;
    border: 2px solid #dee2e6;
    margin-bottom: 8px;
}

.stage-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-align: center;
    width: 80px;
}

.progress-line {
    flex-grow: 1;
    height: 2px;
    background-color: #e9ecef;
    margin: 0 10px;
    position: relative;
    top: -25px;
    z-index: 0;
}

.progress-stage.completed .stage-dot {
    background-color: #28a745;
    border-color: #28a745;
}

.progress-stage.current .stage-dot {
    background-color: #007bff;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.2);
}

.progress-stage.completed .stage-label,
.progress-stage.current .stage-label {
    color: #212529;
    font-weight: 500;
}

.progress-line.completed {
    background-color: #28a745;
}

.badge {
    padding: 0.5em 0.8em;
}

dt {
    font-weight: 500;
    color: #6c757d;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

dd {
    margin-bottom: 1rem;
    margin-left: 0;
}
</style>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        fetch('<?= url('php/controllers/order_controller.php') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `action=cancel&order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to cancel order. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while cancelling the order. Please try again.');
        });
    }
}
</script> 