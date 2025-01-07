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

$order = new Order($pdo);
$orders = $order->getUserOrders($_SESSION['user_id']);

$pageTitle = 'Your Orders - ' . SITE_NAME;
require_once 'header.php';
?>

<main class="container my-5">
    <section class="orders-section">
        <h1 class="mb-4">Your Orders</h1>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <div class="empty-orders">
                    <img src="<?= url('images/empty-orders.png') ?>" alt="No orders" class="empty-state-image mb-4" style="max-width: 200px;">
                    <h2 class="h4 mb-3">No Orders Yet</h2>
                    <p class="text-muted mb-4">Looks like you haven't placed any orders yet. Explore our delicious Northeast dishes!</p>
                    <a href="<?= url('php/views/menu.php') ?>" class="btn btn-primary">
                        <i class="fas fa-utensils me-2"></i>Browse Menu
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3 class="h5 mb-0">Order #<?= htmlspecialchars($order['order_id']) ?></h3>
                                    <span class="order-date text-muted">
                                        <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <!-- Order Status Progress -->
                                        <?php if ($order['status'] !== 'cancelled'): ?>
                                            <div class="order-progress mb-4">
                                                <?php
                                                $stages = ['pending', 'confirmed', 'preparing', 'delivered'];
                                                $currentStageIndex = array_search($order['status'], $stages);
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
                                            <div class="alert alert-danger mb-3">
                                                <i class="fas fa-times-circle me-2"></i>This order has been cancelled
                                            </div>
                                        <?php endif; ?>

                                        <!-- Order Details -->
                                        <div class="order-details">
                                            <div class="mb-3">
                                                <strong>Delivery Address:</strong><br>
                                                <?= nl2br(htmlspecialchars($order['delivery_address'])) ?>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Total Amount:</strong>
                                                <span class="text-primary">₹<?= number_format($order['total_amount'], 2) ?></span>
                                            </div>
                                            <div>
                                                <strong>Payment Status:</strong>
                                                <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                                    <?= ucfirst($order['payment_status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="order-actions text-md-end mt-3 mt-md-0">
                                            <a href="<?= url('php/views/order_details.php?id=' . $order['order_id']) ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <button onclick="cancelOrder(<?= $order['order_id'] ?>)" 
                                                        class="btn btn-outline-danger mt-2">
                                                    <i class="fas fa-times me-2"></i>Cancel Order
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once 'footer.php'; ?>

<style>
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

.empty-state-image {
    max-width: 200px;
    opacity: 0.7;
}

.order-card {
    transition: transform 0.2s ease-in-out;
}

.order-card:hover {
    transform: translateY(-2px);
}

.order-date {
    font-size: 0.875rem;
}

.badge {
    padding: 0.5em 0.8em;
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