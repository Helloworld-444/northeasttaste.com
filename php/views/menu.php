<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/models/Dish.php';
require_once dirname(__DIR__) . '/helpers.php';

startSession();

$dishModel = new Dish($pdo);

// Get category from query string
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

// Get page number from query string
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12; // Number of dishes per page

// Get dishes with pagination
$dishes = $dishModel->getAllDishes($page, $perPage, $category);
$totalDishes = $dishModel->getTotalDishes($category);
$totalPages = ceil($totalDishes / $perPage);

// Get all available categories
$categories = [
    'Manipuri',
    'Assamese',
    'NagaLand',
    'Mizoram',
    'Meghalaya',
    'Tripura',
    'Sikkim',
    'Arunachal Pradesh'
];

include 'header.php';
?>

<div class="container mt-4">
    <!-- Category Filter -->
    <div class="row mb-4">
        <div class="col">
            <div class="btn-group">
                <a href="menu.php" class="btn btn-outline-primary <?php echo !$category ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="menu.php?category=<?php echo urlencode($cat); ?>" 
                       class="btn btn-outline-primary <?php echo $category === $cat ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($cat); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Dishes Grid -->
    <div class="row">
        <?php if (empty($dishes)): ?>
            <div class="col">
                <div class="alert alert-info">
                    No dishes found<?php echo $category ? ' in ' . htmlspecialchars($category) . ' category' : ''; ?>.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($dishes as $dish): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if ($dish['image_blob']): ?>
                            <img src="data:<?php echo htmlspecialchars($dish['image_type']); ?>;base64,<?php echo base64_encode($dish['image_blob']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($dish['name']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($dish['name']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($dish['description']); ?></p>
                            <p class="card-text">
                                <strong>Price: </strong>₹<?php echo number_format($dish['price'], 2); ?>
                            </p>
                            <?php if ($dish['available']): ?>
                                <form action="/fooddelivery/php/controllers/cart_controller.php" method="POST">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="dish_id" value="<?php echo $dish['dish_id']; ?>">
                                    <div class="input-group mb-3">
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="10">
                                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Currently Unavailable</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="row mt-4">
            <div class="col">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?> 
</main>

<?php require_once 'footer.php'; ?> 