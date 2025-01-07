<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Please log in to view your cart.';
    $_SESSION['flash_type'] = 'warning';
    $_SESSION['redirect_after_login'] = '/fooddelivery/php/views/cart.php';
    header('Location: ' . url('php/views/login.php'));
    exit();
}

$pageTitle = 'Shopping Cart - ' . SITE_NAME;

try {
    $cart = new Cart($pdo);
    $cartItems = $cart->getCartItems($_SESSION['user_id']);
    $total = $cart->getCartTotal($_SESSION['user_id']);
} catch (Exception $e) {
    logError('Error loading cart: ' . $e->getMessage());
    $cartItems = [];
    $total = 0;
}

require_once 'header.php';
?>

<main class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <!-- Cart Items -->
            <?php if (empty($cartItems)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h2 class="h4">Your cart is empty</h2>
                    <p class="text-muted mb-4">Add some delicious dishes to your cart</p>
                    <a href="<?= url('php/views/menu.php') ?>" class="btn btn-primary">
                        <i class="fas fa-utensils me-2"></i>Browse Menu
                    </a>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h1 class="h4 mb-0">Shopping Cart</h1>
                    </div>
                    <div class="card-body p-0">
                        <div class="cart-items">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item border-bottom p-3" data-dish-id="<?= $item['dish_id'] ?>">
                                    <div class="row align-items-center">
                                        <!-- Item Image -->
                                        <div class="col-md-2 mb-2 mb-md-0">
                                            <?php if ($item['image_blob'] && $item['image_type']): ?>
                                                <img src="data:<?= htmlspecialchars($item['image_type']) ?>;base64,<?= base64_encode($item['image_blob']) ?>" 
                                                     class="img-fluid rounded" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                                     loading="lazy">
                                            <?php else: ?>
                                                <img src="<?= url('images/default-dish.jpg') ?>" 
                                                     class="img-fluid rounded" 
                                                     alt="<?= htmlspecialchars($item['name']) ?>"
                                                     loading="lazy">
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Item Details -->
                                        <div class="col-md-4 mb-2 mb-md-0">
                                            <h3 class="h6 mb-1"><?= htmlspecialchars($item['name']) ?></h3>
                                            <?php if (!empty($item['category'])): ?>
                                                <span class="badge bg-primary">
                                                    <?= htmlspecialchars($item['category']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['instructions'])): ?>
                                                <p class="small text-muted mt-2 mb-0">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <?= htmlspecialchars($item['instructions']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Quantity Controls -->
                                        <div class="col-md-3 mb-2 mb-md-0">
                                            <div class="input-group input-group-sm" style="width: 120px;">
                                                <button type="button" class="btn btn-outline-secondary decrease-quantity" 
                                                        data-dish-id="<?= $item['dish_id'] ?>">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" class="form-control text-center item-quantity" 
                                                       value="<?= $item['quantity'] ?>" min="1" max="10" readonly>
                                                <button type="button" class="btn btn-outline-secondary increase-quantity"
                                                        data-dish-id="<?= $item['dish_id'] ?>">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Price and Remove -->
                                        <div class="col-md-3 text-md-end">
                                            <div class="mb-2">
                                                <span class="h6">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-item" 
                                                    data-dish-id="<?= $item['dish_id'] ?>">
                                                <i class="fas fa-trash-alt me-1"></i>Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($cartItems)): ?>
            <!-- Order Summary -->
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Order Summary</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>₹<?= number_format($total, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Delivery Fee</span>
                            <span>₹50.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total</strong>
                            <strong class="h5 mb-0">₹<?= number_format($total + 50, 2) ?></strong>
                        </div>
                        <a href="/fooddelivery/php/views/checkout.php" class="btn btn-primary w-100">
                            <i class="fas fa-shopping-bag me-2"></i>Proceed to Checkout
                        </a>
                        <a href="/fooddelivery/php/views/menu.php" class="btn btn-outline-primary w-100 mt-2">
                            <i class="fas fa-utensils me-2"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    document.querySelectorAll('.decrease-quantity').forEach(button => {
        button.addEventListener('click', function() {
            updateQuantity(this.dataset.dishId, 'decrease');
        });
    });

    document.querySelectorAll('.increase-quantity').forEach(button => {
        button.addEventListener('click', function() {
            updateQuantity(this.dataset.dishId, 'increase');
        });
    });

    // Remove item
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                removeFromCart(this.dataset.dishId);
            }
        });
    });

    // Update quantity
    async function updateQuantity(dishId, action) {
        try {
            const response = await fetch('<?= url('php/controllers/cart_controller.php?action=update') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `dish_id=${dishId}&action=${action}`
            });

            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                throw new Error(result.message || 'Failed to update cart');
            }
        } catch (error) {
            showNotification(error.message, 'error');
        }
    }

    // Remove from cart
    async function removeFromCart(dishId) {
        try {
            const response = await fetch('<?= url('php/controllers/cart_controller.php?action=remove') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `dish_id=${dishId}`
            });

            const result = await response.json();
            
            if (result.success) {
                location.reload();
            } else {
                throw new Error(result.message || 'Failed to remove item from cart');
            }
        } catch (error) {
            showNotification(error.message, 'error');
        }
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }
});
</script> 