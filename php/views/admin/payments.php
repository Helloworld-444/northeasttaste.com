<?php 
require_once '../header.php';
requireAdmin();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-2">
            <?php require_once 'sidebar.php'; ?>
        </div>
        
        <div class="col-md-10">
            <h1 class="page-title">Payment Management</h1>

            <div class="filter-section mb-4">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary filter-btn active" data-status="all">All</button>
                    <button type="button" class="btn btn-outline-primary filter-btn" data-status="pending">Pending</button>
                    <button type="button" class="btn btn-outline-success filter-btn" data-status="completed">Completed</button>
                    <button type="button" class="btn btn-outline-danger filter-btn" data-status="failed">Failed</button>
                    <button type="button" class="btn btn-outline-warning filter-btn" data-status="refunded">Refunded</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Transaction ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= htmlspecialchars($payment['payment_id']) ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/views/order_details.php?id=<?= htmlspecialchars($payment['order_id']) ?>">
                                    #<?= htmlspecialchars($payment['order_id']) ?>
                                </a>
                            </td>
                            <td>₹<?= htmlspecialchars(number_format($payment['amount'], 2)) ?></td>
                            <td><?= ucfirst(htmlspecialchars($payment['method'])) ?></td>
                            <td>
                                <span class="badge status-<?= htmlspecialchars($payment['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($payment['status'])) ?>
                                </span>
                            </td>
                            <td><?= $payment['transaction_id'] ? htmlspecialchars($payment['transaction_id']) : '-' ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/views/payment_details.php?id=<?= htmlspecialchars($payment['payment_id']) ?>" 
                                   class="btn btn-sm btn-info">
                                    View
                                </a>
                                
                                <?php if ($payment['status'] === 'completed'): ?>
                                <button type="button" 
                                        class="btn btn-sm btn-danger refund-btn"
                                        data-payment-id="<?= htmlspecialchars($payment['payment_id']) ?>">
                                    Refund
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const tableBody = document.getElementById('paymentsTableBody');

    filterButtons.forEach(button => {
        button.addEventListener('click', async function() {
            // Update active state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            const status = this.dataset.status;
            
            try {
                const response = await fetch(`<?= SITE_URL ?>/controllers/payment_controller.php?action=getPaymentsByStatus&status=${status}&ajax=1`);
                const data = await response.json();
                
                if (data.payments) {
                    updatePaymentsTable(data.payments);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to fetch payments');
            }
        });
    });

    document.querySelectorAll('.refund-btn').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to process this refund?')) {
                return;
            }

            const paymentId = this.dataset.paymentId;
            
            try {
                const response = await fetch('<?= SITE_URL ?>/controllers/payment_controller.php?action=processRefund', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `payment_id=${paymentId}`
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                } else {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to process refund');
            }
        });
    });

    function updatePaymentsTable(payments) {
        tableBody.innerHTML = payments.map(payment => `
            <tr>
                <td>${payment.payment_id}</td>
                <td>
                    <a href="<?= SITE_URL ?>/views/order_details.php?id=${payment.order_id}">
                        #${payment.order_id}
                    </a>
                </td>
                <td>₹${Number(payment.amount).toFixed(2)}</td>
                <td>${payment.method.charAt(0).toUpperCase() + payment.method.slice(1)}</td>
                <td>
                    <span class="badge status-${payment.status}">
                        ${payment.status.charAt(0).toUpperCase() + payment.status.slice(1)}
                    </span>
                </td>
                <td>${payment.transaction_id || '-'}</td>
                <td>
                    <a href="<?= SITE_URL ?>/views/payment_details.php?id=${payment.payment_id}" 
                       class="btn btn-sm btn-info">
                        View
                    </a>
                    ${payment.status === 'completed' ? `
                        <button type="button" 
                                class="btn btn-sm btn-danger refund-btn"
                                data-payment-id="${payment.payment_id}">
                            Refund
                        </button>
                    ` : ''}
                </td>
            </tr>
        `).join('');

        // Reattach event listeners to new refund buttons
        document.querySelectorAll('.refund-btn').forEach(button => {
            button.addEventListener('click', handleRefund);
        });
    }
});
</script>

<?php require_once '../footer.php'; ?> 