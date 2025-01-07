<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['flash_message'] = 'Please log in to view your profile.';
    $_SESSION['flash_type'] = 'warning';
    header('Location: ' . url('php/views/login.php'));
    exit();
}

$pageTitle = 'My Profile - ' . SITE_NAME;

$user = new User($pdo);
$order = new Order($pdo);

$userData = $user->getUserById($_SESSION['user_id']);
$userOrders = $order->getUserOrders($_SESSION['user_id']);

require_once 'header.php';
?>

<main class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Profile Tabs -->
            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="info-tab" data-bs-toggle="tab" href="#info" role="tab">
                        <i class="fas fa-user me-2"></i>Personal Information
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="password-tab" data-bs-toggle="tab" href="#password" role="tab">
                        <i class="fas fa-key me-2"></i>Change Password
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="orders-tab" data-bs-toggle="tab" href="#orders" role="tab">
                        <i class="fas fa-shopping-bag me-2"></i>Order History
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="profileTabsContent">
                <!-- Personal Information Tab -->
                <div class="tab-pane fade show active" id="info" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Personal Information</h2>
                            <form id="profile-form" action="<?= url('php/controllers/user_controller.php') ?>" method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?= htmlspecialchars($userData['first_name']) ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?= htmlspecialchars($userData['last_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?= htmlspecialchars($userData['email']) ?>" readonly>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Delivery Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?= htmlspecialchars($userData['address'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Change Password</h2>
                            <form id="password-form" action="<?= url('php/controllers/user_controller.php') ?>" method="POST">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="current_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$" 
                                               title="Password must be at least 8 characters long and include both letters and numbers" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="new_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength mt-2">
                                        <div class="strength-meter"></div>
                                        <span class="strength-text"></span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="confirm_password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Order History Tab -->
                <div class="tab-pane fade" id="orders" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h2 class="card-title h4 mb-4">Order History</h2>
                            <?php if (empty($userOrders)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                    <p class="lead">You haven't placed any orders yet.</p>
                                    <a href="<?= url('php/views/menu.php') ?>" class="btn btn-primary">
                                        <i class="fas fa-utensils me-2"></i>Browse Menu
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="orders-list">
                                    <?php foreach ($userOrders as $order): ?>
                                        <div class="card mb-3">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <span class="text-muted">Order #<?= $order['order_id'] ?></span>
                                                <span class="badge bg-<?= getStatusBadgeClass($order['status']) ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row mb-2">
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Ordered on</small>
                                                        <div><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></div>
                                                    </div>
                                                    <div class="col-md-6 text-md-end">
                                                        <small class="text-muted">Total Amount</small>
                                                        <div class="h5 mb-0">₹<?= number_format($order['total_amount'], 2) ?></div>
                                                    </div>
                                                </div>
                                                <div class="order-items">
                                                    <?php foreach ($order['items'] as $item): ?>
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div>
                                                                <?= htmlspecialchars($item['name']) ?> 
                                                                <span class="text-muted">× <?= $item['quantity'] ?></span>
                                                            </div>
                                                            <div>₹<?= number_format($item['price_per_unit'] * $item['quantity'], 2) ?></div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <a href="<?= url('php/views/order_details.php?id=' . $order['order_id']) ?>" 
                                                   class="btn btn-outline-primary btn-sm mt-3">
                                                    <i class="fas fa-eye me-2"></i>View Details
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php 
require_once 'footer.php';

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'confirmed':
            return 'info';
        case 'preparing':
            return 'primary';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?> 