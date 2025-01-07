<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once dirname(dirname(__DIR__)) . '/db.php';
require_once dirname(dirname(__DIR__)) . '/models/User.php';
require_once __DIR__ . '/../../models/Order.php';

$user = new User($pdo);
$order = new Order($pdo);
$users = $user->getAllUsers();

ob_start();
?>

<div class="admin-container">
    <div class="admin-card">
        <div class="card-header">
            <h2>Manage Users</h2>
            <div class="stats">
                <p>Total Users: <?php echo count($users); ?></p>
            </div>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Orders</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $userData): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($userData['email']); ?></td>
                        <td><?php echo htmlspecialchars($userData['phone'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($userData['address'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            try {
                                $orderCount = $order->getOrderCountByUser($userData['user_id']);
                                if ($orderCount > 0): 
                                ?>
                                    <a href="orders.php?user_id=<?php echo $userData['user_id']; ?>" 
                                       class="order-link">
                                        View Orders (<?php echo $orderCount; ?>)
                                    </a>
                                <?php else: ?>
                                    No Orders
                                <?php endif;
                            } catch (Exception $e) {
                                echo 'Error loading orders';
                                error_log("Error getting order count: " . $e->getMessage());
                            }
                            ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $userData['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $userData['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $userData['is_admin'] ? 'admin' : 'user'; ?>">
                                <?php echo $userData['is_admin'] ? 'Admin' : 'User'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" 
                                    onclick="toggleUserStatus(<?php echo $userData['user_id']; ?>, <?php echo $userData['is_active']; ?>)">
                                <?php echo $userData['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                            <?php if (!$userData['is_admin']): ?>
                            <button class="btn btn-sm btn-secondary" 
                                    onclick="toggleAdminRole(<?php echo $userData['user_id']; ?>, <?php echo $userData['is_admin']; ?>)">
                                Make Admin
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleUserStatus(userId, currentStatus) {
    if (confirm('Are you sure you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this user?')) {
        fetch('users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=toggle_status&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update user status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating user status');
        });
    }
}

function toggleAdminRole(userId, currentStatus) {
    if (confirm('Are you sure you want to ' + (currentStatus ? 'remove admin role from' : 'make this user an admin?'))) {
        fetch('users.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=toggle_admin&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update user role: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating user role');
        });
    }
}
</script>

<style>
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.admin-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 20px auto;
    padding: 20px;
    width: 100%;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.stats {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 0.9em;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto;
}

.admin-table th,
.admin-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    vertical-align: middle;
}

.admin-table th {
    background-color: #f5f5f5;
    font-weight: 600;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.status-badge.admin {
    background-color: #e3f2fd;
    color: #1565c0;
}

.status-badge.user {
    background-color: #f5f5f5;
    color: #616161;
}

.status-badge.active {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.status-badge.inactive {
    background-color: #ffebee;
    color: #c62828;
}

.order-link {
    color: #1976d2;
    text-decoration: none;
}

.order-link:hover {
    text-decoration: underline;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.875rem;
    margin: 0 2px;
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?> 