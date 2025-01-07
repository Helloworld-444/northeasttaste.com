<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../models/Dish.php';
require_once __DIR__ . '/../db.php';

// Get dish ID from URL
$dishId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$dishId) {
    $_SESSION['flash_message'] = 'Invalid dish ID';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . url('php/views/menu.php'));
    exit();
}

try {
    $dish = new Dish($pdo);
    $dishDetails = $dish->getDishById($dishId);

    if (!$dishDetails) {
        $_SESSION['flash_message'] = 'Dish not found';
        $_SESSION['flash_type'] = 'error';
        header('Location: ' . url('php/views/menu.php'));
        exit();
    }

    // Fetch related dishes
    $relatedDishes = $dish->getRelatedDishes($dishId, $dishDetails['category'], 3);

    $pageTitle = htmlspecialchars($dishDetails['name']) . ' - ' . SITE_NAME;
    require_once 'header.php';
} catch (Exception $e) {
    logError('Error loading dish details: ' . $e->getMessage());
    $_SESSION['flash_message'] = 'Error loading dish details';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . url('php/views/menu.php'));
    exit();
}
?>

<main class="container my-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('index.php') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('php/views/menu.php') ?>">Menu</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($dishDetails['name']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Dish Image -->
        <div class="col-md-6 mb-4">
            <?php if ($dishDetails['image_blob'] && $dishDetails['image_type']): ?>
                <img src="data:<?= htmlspecialchars($dishDetails['image_type']) ?>;base64,<?= base64_encode($dishDetails['image_blob']) ?>" 
                     class="img-fluid rounded shadow" 
                     alt="<?= htmlspecialchars($dishDetails['name']) ?>">
            <?php else: ?>
                <img src="<?= url('images/default-dish.jpg') ?>" 
                     class="img-fluid rounded shadow" 
                     alt="<?= htmlspecialchars($dishDetails['name']) ?>">
            <?php endif; ?>
        </div>

        <!-- Dish Details -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h2 mb-2"><?= htmlspecialchars($dishDetails['name']) ?></h1>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary">
                            <?= htmlspecialchars($dishDetails['category']) ?>
                        </span>
                        <?php if ($dishDetails['available']): ?>
                            <span class="badge bg-success ms-2">Available</span>
                        <?php else: ?>
                            <span class="badge bg-danger ms-2">Currently Unavailable</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <h2 class="h4 text-primary mb-3">₹<?= number_format($dishDetails['price'], 2) ?></h2>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($dishDetails['description'])) ?></p>
                    </div>

                    <?php if ($dishDetails['available']): ?>
                        <form id="addToCartForm" class="mb-4">
                            <input type="hidden" name="dish_id" value="<?= $dishDetails['dish_id'] ?>">
                            
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <div class="input-group" style="width: 140px;">
                                    <button type="button" class="btn btn-outline-secondary" id="decreaseQuantity">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" id="quantity" 
                                           name="quantity" value="1" min="1" max="10" readonly>
                                    <button type="button" class="btn btn-outline-secondary" id="increaseQuantity">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="instructions" class="form-label">Special Instructions (Optional)</label>
                                <textarea class="form-control" id="instructions" name="instructions" 
                                          rows="2" placeholder="Any special requests or dietary requirements?"></textarea>
                            </div>

                            <div class="d-flex align-items-center gap-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                                <span class="h5 mb-0" id="totalPrice">
                                    Total: ₹<?= number_format($dishDetails['price'], 2) ?>
                                </span>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i>This dish is currently unavailable.
                        </div>
                    <?php endif; ?>

                    <!-- Additional Information -->
                    <div class="mt-4">
                        <h3 class="h5 mb-3">About this Dish</h3>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-pepper-hot text-danger me-2"></i>
                                    <span>Spice Level: Medium</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock text-info me-2"></i>
                                    <span>Prep Time: 20-30 mins</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-leaf text-success me-2"></i>
                                    <span>Vegetarian</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-fire text-warning me-2"></i>
                                    <span>Calories: 350</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Dishes -->
    <?php if (!empty($relatedDishes)): ?>
    <section class="related-dishes mt-5">
        <h2 class="h4 mb-4">You Might Also Like</h2>
        <div class="row">
            <?php foreach ($relatedDishes as $relatedDish): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if ($relatedDish['image_blob'] && $relatedDish['image_type']): ?>
                        <img src="data:<?= htmlspecialchars($relatedDish['image_type']) ?>;base64,<?= base64_encode($relatedDish['image_blob']) ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($relatedDish['name']) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <img src="<?= url('images/default-dish.jpg') ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($relatedDish['name']) ?>"
                             loading="lazy">
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="h6 mb-2"><?= htmlspecialchars($relatedDish['name']) ?></h3>
                        <p class="small text-muted mb-2"><?= substr(htmlspecialchars($relatedDish['description']), 0, 100) ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-primary">₹<?= number_format($relatedDish['price'], 2) ?></span>
                            <a href="<?= url('php/views/dish_details.php?id=' . $relatedDish['dish_id']) ?>" 
                               class="btn btn-outline-primary btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php require_once 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addToCartForm');
    const quantityInput = document.getElementById('quantity');
    const totalPriceElement = document.getElementById('totalPrice');
    const price = <?= json_encode($dishDetails['price']) ?>;

    // Update cart count on page load
    updateCartCount();

    // Function to update cart count
    async function updateCartCount() {
        try {
            const response = await fetch('<?= url('php/controllers/cart_controller.php') ?>?action=getCount', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to get cart count');
            }

            const result = await response.json();
            const cartCountElement = document.getElementById('cartCount');
            if (cartCountElement && result.count !== undefined) {
                cartCountElement.textContent = result.count;
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    }

    if (form) {
        // Quantity controls
        document.getElementById('decreaseQuantity')?.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
                updateTotal();
            }
        });

        document.getElementById('increaseQuantity')?.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue < 10) {
                quantityInput.value = currentValue + 1;
                updateTotal();
            }
        });

        // Update total price
        function updateTotal() {
            const quantity = parseInt(quantityInput.value);
            const total = price * quantity;
            totalPriceElement.textContent = `Total: ₹${total.toFixed(2)}`;
        }

        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validate quantity
            const quantity = parseInt(quantityInput.value);
            if (isNaN(quantity) || quantity < 1 || quantity > 10) {
                showNotification('Please enter a valid quantity (1-10)', 'error');
                return;
            }

            // Get special instructions
            const instructions = document.getElementById('instructions').value.trim();
            if (instructions.length > 500) {
                showNotification('Special instructions are too long. Please keep it under 500 characters.', 'error');
                return;
            }

            try {
                const formData = new FormData(this);
                formData.append('action', 'add');

                const response = await fetch('<?= url('php/controllers/cart_controller.php') ?>', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server response:', text);
                    throw new Error('Failed to add item to cart');
                }

                // Redirect to cart page
                window.location.href = '<?= url('php/views/cart.php') ?>';
            } catch (error) {
                console.error('Error:', error);
                showNotification(error.message || 'An error occurred while adding to cart', 'error');
            }
        });
    }

    // Notification function with improved styling
    function showNotification(message, type = 'info') {
        // Remove any existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        
        // Add icon based on notification type
        const icon = document.createElement('i');
        icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        notification.appendChild(icon);

        const textSpan = document.createElement('span');
        textSpan.textContent = ' ' + message;
        notification.appendChild(textSpan);

        document.body.appendChild(notification);

        // Add entry animation
        notification.classList.add('notification--show');

        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
</script>

<style>
.dish-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 4px;
    color: #fff;
    z-index: 1050;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    transform: translateX(120%);
    transition: transform 0.3s ease-out;
}

.notification--show {
    transform: translateX(0);
}

.notification--hide {
    transform: translateX(120%);
}

.notification--success {
    background-color: #28a745;
}

.notification--error {
    background-color: #dc3545;
}

.cart-update-animation {
    animation: bounce 0.5s ease-in-out;
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.breadcrumb-item a {
    color: #6c757d;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: #007bff;
}

.card {
    border: none;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn-outline-secondary:focus {
    box-shadow: none;
}

.input-group-text {
    background-color: transparent;
}
</style> 