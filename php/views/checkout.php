<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../db.php';

// Start session and check login status
startSession();

if (!isLoggedIn()) {
    if (isAjaxRequest()) {
        jsonResponse(['success' => false, 'error' => 'Please login to continue'], 401);
    } else {
        header('Location: /php/views/login.php');
        exit();
    }
}

$user = new User($pdo);
$cart = new Cart($pdo);

$userData = $user->getUserById($_SESSION['user_id']);
$cartItems = $cart->getCartItems($_SESSION['user_id']);
$subtotal = $cart->getCartTotal($_SESSION['user_id']);

// Calculate delivery fee based on subtotal
$deliveryFee = ($subtotal >= 500) ? 0 : 50;
$total = $subtotal + $deliveryFee;

if (empty($cartItems)) {
    header('Location: /php/views/cart.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="container">
        <section class="checkout-section">
            <h1>Checkout</h1>

            <form id="checkout-form" method="POST">
                <input type="hidden" name="action" value="createOrder">
                <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                <input type="hidden" name="payment_status" value="pending">
                
                <!-- Delivery Address -->
                <div class="form-group">
                    <h2>Delivery Address</h2>
                    <textarea name="delivery_address" rows="4" required
                        placeholder="Enter your complete delivery address including landmark"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <div class="item-details">
                                    <span class="item-name">
                                        <?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?>
                                    </span>
                                    <?php if (isset($item['instructions'])): ?>
                                        <small class="item-instructions">
                                            <?php echo htmlspecialchars($item['instructions']); ?>
                                        </small>
                                        <input type="hidden" 
                                               name="item_instructions[<?php echo $item['dish_id']; ?>]" 
                                               value="<?php echo htmlspecialchars($item['instructions']); ?>">
                                    <?php endif; ?>
                                </div>
                                <span class="item-price">
                                    ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </span>
                                <input type="hidden" name="items[<?php echo $item['dish_id']; ?>][quantity]" 
                                       value="<?php echo $item['quantity']; ?>">
                                <input type="hidden" name="items[<?php echo $item['dish_id']; ?>][price]" 
                                       value="<?php echo $item['price']; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="price-breakdown">
                        <div class="subtotal">
                            <span>Subtotal:</span>
                            <span>₹<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="delivery-fee">
                            <span>Delivery Fee:</span>
                            <span>₹<?php echo number_format($deliveryFee, 2); ?></span>
                            <?php if ($deliveryFee === 0): ?>
                                <small class="free-delivery-note">(Free delivery on orders above ₹500)</small>
                            <?php endif; ?>
                        </div>
                        <div class="total">
                            <strong>Total:</strong>
                            <strong>₹<?php echo number_format($total, 2); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="form-group">
                    <h2>Payment Method</h2>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cash" checked>
                            <span class="payment-label">Cash on Delivery</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="upi">
                            <span class="payment-label">UPI Payment</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="card">
                            <span class="payment-label">Card Payment</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Place Order</button>
            </form>
        </section>
    </main>

    <?php include 'footer.php'; ?>
    <script>
    document.getElementById('checkout-form').addEventListener('submit', async function(e) {
        e.preventDefault();

        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Processing...';

        // Clear any previous error messages
        const errorDiv = document.getElementById('checkout-error');
        if (errorDiv) {
            errorDiv.remove();
        }
        
        // Validate required fields
        const deliveryAddress = this.querySelector('textarea[name="delivery_address"]').value.trim();
        if (!deliveryAddress) {
            showError('Please enter your delivery address');
            resetButton();
            return;
        }

        try {
            const formData = new FormData(this);
            
            const response = await fetch('/fooddelivery/php/controllers/order_controller.php?action=createOrder', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await response.json();
                    throw new Error(data.error || `HTTP error! status: ${response.status}`);
                } else {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            }

            const data = await response.json();
            console.log('Response:', data); // Debug log

            if (data.success) {
                // Clear cart items from localStorage if any
                localStorage.removeItem('cartItems');
                // Redirect to order confirmation page
                window.location.href = data.redirect_url;
            } else {
                throw new Error(data.error || 'Failed to create order');
            }
        } catch (error) {
            console.error('Error:', error);
            showError(error.message || 'An error occurred while processing your order. Please try again.');
        } finally {
            resetButton();
        }
    });

    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'checkout-error';
        errorDiv.className = 'alert alert-danger';
        errorDiv.textContent = message;
        const form = document.getElementById('checkout-form');
        form.insertBefore(errorDiv, form.firstChild);
    }

    function resetButton() {
        const submitButton = document.querySelector('#checkout-form button[type="submit"]');
        submitButton.disabled = false;
        submitButton.textContent = 'Place Order';
    }
    </script>

    <style>
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }
    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }
    </style>
</body>
</html> 