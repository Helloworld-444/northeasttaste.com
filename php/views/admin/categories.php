<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once dirname(dirname(__DIR__)) . '/db.php';

// Get categories from ENUM
$stmt = $pdo->query("SHOW COLUMNS FROM dishes LIKE 'category'");
$enumColumn = $stmt->fetch(PDO::FETCH_ASSOC);
preg_match("/^enum\((.*)\)$/", $enumColumn['Type'], $matches);
$categories = array_map(function($val) { 
    return trim($val, "'"); 
}, explode(',', $matches[1]));

// Get dish count for each category
$dishCounts = [];
foreach ($categories as $category) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM dishes WHERE category = ?");
    $stmt->execute([$category]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $dishCounts[$category] = $result['count'];
}

ob_start();
?>

<div class="admin-container">
    <div class="admin-card">
        <div class="card-header">
            <h2>Categories</h2>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Number of Dishes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category); ?></td>
                        <td>Dishes from <?php echo htmlspecialchars($category); ?></td>
                        <td><?php echo $dishCounts[$category]; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Hidden div to store category data for JavaScript -->
<div id="categoriesData" style="display: none;">
    <?php foreach ($categories as $category): ?>
        <div data-category-id="<?php echo htmlspecialchars($category); ?>" 
             data-category-name="<?php echo htmlspecialchars($category); ?>">
        </div>
    <?php endforeach; ?>
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

.admin-table th {
    background-color: #f5f5f5;
    font-weight: 600;
}

.admin-table tr:hover {
    background-color: #f8f9fa;
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?> 