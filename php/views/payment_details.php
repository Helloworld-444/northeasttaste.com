<?php require_once 'header.php'; ?>

<div class="container payment-details">
    <h1 class="page-title">Payment Details</h1>

    <div class="payment-info">
        <div class="card">
            <div class="card-header">
                <h2>Payment #<?= htmlspecialchars($payment['payment_id']) ?></h2>
                <span class="payment-status status-<?= htmlspecialchars($payment['status']) ?>">
                    <?= ucfirst(htmlspecialchars($payment['status'])) ?>
                </span>
            </div>
            
            <div class="card-body">
                <div class="info-row">
                    <label>Amount:</label>
                    <span>₹<?= htmlspecialchars(number_format($payment['amount'], 2)) ?></span>
                </div>
                
                <div class="info-row">
                    <label>Payment Method:</label>
                    <span><?= ucfirst(htmlspecialchars($payment['method'])) ?></span>
                </div>
                
                <?php if ($payment['transaction_id']): ?>
                <div class="info-row">
                    <label>Transaction ID:</label>
                    <span><?= htmlspecialchars($payment['transaction_id']) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <label>Order ID:</label>
                    <span>
                        <a href="<?= SITE_URL ?>/views/order_details.php?id=<?= htmlspecialchars($payment['order_id']) ?>">
                            #<?= htmlspecialchars($payment['order_id']) ?>
                        </a>
                    </span>
                </div>
            </div>

            <?php if (isAdmin()): ?>
            <div class="card-footer">
                <form id="updatePaymentForm" class="inline-form">
                    <input type="hidden" name="payment_id" value="<?= htmlspecialchars($payment['payment_id']) ?>">
                    
                    <div class="form-group">
                        <label for="status">Update Status:</label>
                        <select name="status" id="status" class="form-control">
                            <option value="pending" <?= $payment['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="completed" <?= $payment['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="failed" <?= $payment['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="refunded" <?= $payment['status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="transaction_id">Transaction ID:</label>
                        <input type="text" name="transaction_id" id="transaction_id" 
                               class="form-control" 
                               value="<?= htmlspecialchars($payment['transaction_id'] ?? '') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Payment</button>
                    
                    <?php if ($payment['status'] === 'completed'): ?>
                    <button type="button" class="btn btn-danger" id="refundBtn">Process Refund</button>
                    <?php endif; ?>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const updateForm = document.getElementById('updatePaymentForm');
    const refundBtn = document.getElementById('refundBtn');

    if (updateForm) {
        updateForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const response = await fetch('<?= SITE_URL ?>/controllers/payment_controller.php?action=updateStatus', {
                    method: 'POST',
                    body: new FormData(this)
                });
                
                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                } else {
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update payment status');
            }
        });
    }

    if (refundBtn) {
        refundBtn.addEventListener('click', async function() {
            if (!confirm('Are you sure you want to process this refund?')) {
                return;
            }
            
            try {
                const response = await fetch('<?= SITE_URL ?>/controllers/payment_controller.php?action=processRefund', {
                    method: 'POST',
                    body: new FormData(updateForm)
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
    }
});
</script>

<?php require_once 'footer.php'; ?> 