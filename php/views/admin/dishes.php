<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once dirname(dirname(__DIR__)) . '/db.php';
require_once dirname(dirname(__DIR__)) . '/models/Dish.php';

$dish = new Dish($pdo);
$dishes = $dish->getAllDishes();

// Ensure categories are available
$stmt = $pdo->query("SHOW COLUMNS FROM dishes LIKE 'category'");
$enumColumn = $stmt->fetch(PDO::FETCH_ASSOC);
preg_match("/^enum\((.*)\)$/", $enumColumn['Type'], $matches);
$enumStr = $matches[1];
$categories = str_getcsv($enumStr, ',', "'");

// Debug categories
error_log('Available categories: ' . print_r($categories, true));

ob_start();
?>

<!-- Categories Data (Hidden) -->
<div id="categoriesData" style="display: none;">
    <?php foreach ($categories as $category): ?>
        <div data-category-id="<?php echo htmlspecialchars($category); ?>" 
             data-category-name="<?php echo htmlspecialchars($category); ?>"></div>
    <?php endforeach; ?>
</div>

<div class="admin-container">
    <div class="admin-card">
        <div class="card-header">
            <h2>Manage Dishes</h2>
            <button class="btn btn-primary" onclick="showAddDishModal()">Add New Dish</button>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dishes as $dish): ?>
                    <tr>
                        <td>
                            <?php if (!empty($dish['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($dish['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($dish['name']); ?>"
                                     class="dish-thumbnail">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($dish['name']); ?></td>
                        <td><?php echo htmlspecialchars($dish['category']); ?></td>
                        <td>₹<?php echo number_format($dish['price'], 2); ?></td>
                        <td>
                            <span class="status-badge <?php echo $dish['available'] ? 'available' : 'unavailable'; ?>">
                                <?php echo $dish['available'] ? 'Available' : 'Unavailable'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-small btn-primary" 
                                    onclick='editDish(<?php echo json_encode([
                                        "dish_id" => $dish["dish_id"],
                                        "name" => $dish["name"],
                                        "category" => $dish["category"],
                                        "price" => $dish["price"],
                                        "description" => $dish["description"],
                                        "available" => $dish["available"]
                                    ]); ?>)'>
                                Edit
                            </button>
                            <button class="btn btn-small btn-danger" 
                                    onclick="deleteDish(<?php echo $dish['dish_id']; ?>)">
                                Delete
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Dish Modal -->
<div id="dishModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Dish</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form id="dishForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="dish_id" name="dish_id">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="name">Dish Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="price">Price (₹)</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small class="form-text text-muted">
                    Allowed types: JPG, PNG, GIF. Max size: 5MB
                </small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="available" name="available" value="1" checked>
                    Available
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Dish</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-container {
    max-width: 1000px;
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

.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto;
    table-layout: fixed;
}

.admin-table th,
.admin-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    vertical-align: middle;
}

.admin-table th:nth-child(1), 
.admin-table td:nth-child(1) { width: 100px; }
.admin-table th:nth-child(2), 
.admin-table td:nth-child(2) { width: 20%; }
.admin-table th:nth-child(3), 
.admin-table td:nth-child(3) { width: 20%; }
.admin-table th:nth-child(4), 
.admin-table td:nth-child(4) { width: 15%; }
.admin-table th:nth-child(5), 
.admin-table td:nth-child(5) { width: 15%; }
.admin-table th:nth-child(6), 
.admin-table td:nth-child(6) { width: 150px; }

.admin-table th {
    background-color: #f5f5f5;
    font-weight: 600;
}

.dish-thumbnail {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.status-badge.available {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.status-badge.unavailable {
    background-color: #ffebee;
    color: #c62828;
}

.btn-small {
    padding: 4px 8px;
    margin: 0 2px;
    font-size: 0.9em;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    width: 90%;
    max-width: 500px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.close {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 24px;
    cursor: pointer;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-group input[type="file"] {
    display: block;
    margin-top: 5px;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 0.875em;
    color: #6c757d;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
}

.btn-primary {
    background-color: #e67e22;
    color: white;
}

.btn-primary:hover {
    background-color: #d35400;
}
</style>

<!-- Include consolidated admin JavaScript files -->
<script src="<?php echo SITE_URL; ?>/js/admin/dishes.js"></script>
<script src="<?php echo SITE_URL; ?>/js/admin/dish_form.js"></script>
<script src="<?php echo SITE_URL; ?>/js/admin/dish_images.js"></script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?> 