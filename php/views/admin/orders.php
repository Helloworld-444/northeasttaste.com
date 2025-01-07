<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once dirname(dirname(__DIR__)) . '/db.php';
require_once dirname(dirname(__DIR__)) . '/models/Order.php';
require_once dirname(dirname(__DIR__)) . '/helpers.php';

startSession();
requireAdmin();

$order = new Order($pdo);

// Get page number from query string
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10; // Number of orders per page

// Get orders with pagination
$orders = $order->getOrders($perPage, ($page - 1) * $perPage);
$totalOrders = $order->getTotalOrders();
$totalPages = ceil($totalOrders / $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders - <?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/admin.css">
    <style>
        .table td, .table th {
            vertical-align: middle;
            padding: 0.75rem;
        }
        .table td select {
            min-width: 120px;
        }
        .customer-info {
            margin-bottom: 0;
        }
        .customer-email {
            font-size: 0.85em;
            color: #6c757d;
        }
        .status-select {
            width: 140px !important;
        }
        .admin-main {
            padding: 2rem;
            margin-left: 250px; /* Match sidebar width */
            width: calc(100% - 250px);
        }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-right: 1rem;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .btn-info {
            color: white;
        }
        .container-fluid {
            padding-right: 1rem;
            padding-left: 1rem;
        }
        @media (max-width: 768px) {
            .admin-main {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>

        <main class="admin-main">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Manage Orders</h1>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="alert alert-info">No orders found.</div>
                <?php else: ?>
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Total Amount</th>
                                        <th>Order Status</th>
                                        <th>Payment Status</th>
                                        <th>Order Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?= htmlspecialchars($order['order_id']) ?></td>
                                            <td>
                                                <p class="customer-info"><?= htmlspecialchars(trim($order['first_name'] . ' ' . $order['last_name'])) ?></p>
                                                <span class="customer-email"><?= htmlspecialchars($order['email']) ?></span>
                                            </td>
                                            <td>₹<?= htmlspecialchars(number_format($order['total_amount'], 2)) ?></td>
                                            <td>
                                                <select class="form-select form-select-sm status-select order-status" data-order-id="<?= $order['order_id'] ?>">
                                                    <?php foreach (['pending', 'confirmed', 'preparing', 'delivered', 'cancelled'] as $status): ?>
                                                        <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                                            <?= ucfirst($status) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm status-select payment-status" data-order-id="<?= $order['order_id'] ?>">
                                                    <?php foreach (['pending', 'paid', 'failed'] as $status): ?>
                                                        <option value="<?= $status ?>" <?= $order['payment_status'] === $status ? 'selected' : '' ?>>
                                                            <?= ucfirst($status) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($order['created_at']))) ?></td>
                                            <td>
                                                <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page - 1) ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= ($page + 1) ?>">Next</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.querySelectorAll('.order-status').forEach(select => {
        select.addEventListener('change', function() {
            const previousValue = this.getAttribute('data-previous-value') || this.value;
            updateOrderStatus(this.value, this.dataset.orderId, this, previousValue);
        });
    });

    document.querySelectorAll('.payment-status').forEach(select => {
        select.addEventListener('change', function() {
            const previousValue = this.getAttribute('data-previous-value') || this.value;
            updatePaymentStatus(this.value, this.dataset.orderId, this, previousValue);
        });
    });

    function updateOrderStatus(status, orderId, selectElement, previousValue) {
        selectElement.setAttribute('data-previous-value', previousValue);
        selectElement.disabled = true;

        fetch(`${window.location.protocol}//${window.location.host}/fooddelivery/php/controllers/order_controller.php?action=updateStatus`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `order_id=${orderId}&status=${status}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            selectElement.disabled = false;
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update order status. Please try again.');
                selectElement.value = previousValue;
            }
        })
        .catch(error => {
            selectElement.disabled = false;
            console.error('Error:', error);
            alert('An error occurred while updating the order status. Please try again.');
            selectElement.value = previousValue;
        });
    }

    function updatePaymentStatus(status, orderId, selectElement, previousValue) {
        selectElement.setAttribute('data-previous-value', previousValue);
        selectElement.disabled = true;

        fetch(`${window.location.protocol}//${window.location.host}/fooddelivery/php/controllers/order_controller.php?action=updatePaymentStatus`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `order_id=${orderId}&status=${status}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            selectElement.disabled = false;
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update payment status. Please try again.');
                selectElement.value = previousValue;
            }
        })
        .catch(error => {
            selectElement.disabled = false;
            console.error('Error:', error);
            alert('An error occurred while updating the payment status. Please try again.');
            selectElement.value = previousValue;
        });
    }
    </script>
</body>
</html> 