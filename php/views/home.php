<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../models/Dish.php';
$pageTitle = 'Home - The Northeast';
require_once __DIR__ . '/header.php';

// Initialize database connection and models
try {
    require_once __DIR__ . '/../db.php';
    $dishModel = new Dish($pdo);
    $featuredDishes = $dishModel->getFeaturedDishes();
    $categories = ['Manipuri', 'Assamese', 'NagaLand', 'Mizoram', 'Meghalaya', 'Tripura', 'Sikkim', 'Arunachal Pradesh'];
} catch (Exception $e) {
    $featuredDishes = [];
    $categories = [];
    logError('Error loading data: ' . $e->getMessage());
}
?>

<main class="container my-5">
    <!-- Hero Section -->
    <section class="hero-section text-center py-5 bg-light rounded">
        <h1 class="display-4">Welcome to The Northeast</h1>
        <p class="lead">Discover authentic Northeast Indian cuisine delivered to your doorstep</p>
        <p class="mb-4">Experience the unique flavors and traditions of the Seven Sisters</p>
        <a href="<?= url('php/views/menu.php') ?>" class="btn btn-primary btn-lg">
            <i class="fas fa-utensils me-2"></i>Explore Our Menu
        </a>
    </section>

    <!-- Featured Dishes Section -->
    <?php if (!empty($featuredDishes)): ?>
    <section class="featured-dishes my-5">
        <h2 class="text-center mb-4">Featured Dishes</h2>
        <div class="row">
            <?php foreach ($featuredDishes as $dish): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if ($dish['image_blob'] && $dish['image_type']): ?>
                    <img src="data:<?= htmlspecialchars($dish['image_type']) ?>;base64,<?= base64_encode($dish['image_blob']) ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($dish['name']) ?>"
                         loading="lazy">
                    <?php else: ?>
                    <img src="<?= url('images/default-dish.jpg') ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($dish['name']) ?>"
                         loading="lazy">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($dish['name']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($dish['description']) ?></p>
                        <p class="card-text">
                            <span class="badge bg-primary"><?= htmlspecialchars($dish['category']) ?></span>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0">₹<?= number_format($dish['price'], 2) ?></span>
                            <a href="<?= url('php/views/dish_details.php?id=' . $dish['dish_id']) ?>" 
                               class="btn btn-outline-primary">
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

    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <section class="categories my-5">
        <h2 class="text-center mb-4">Explore Our Cuisines</h2>
        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
            <div class="col-md-3 col-sm-6">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="h5 mb-3"><?= htmlspecialchars($category) ?></h3>
                        <a href="<?= url('php/views/menu.php?category=' . urlencode($category)) ?>" 
                           class="btn btn-outline-primary btn-sm stretched-link">
                            Explore <?= htmlspecialchars($category) ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Special Features Section -->
    <section class="features my-5">
        <h2 class="text-center mb-4">Why Choose Us</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-truck-fast fa-3x text-primary mb-3"></i>
                        <h3 class="h5">Fast Delivery</h3>
                        <p class="card-text">Quick and reliable delivery to your doorstep</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-leaf fa-3x text-primary mb-3"></i>
                        <h3 class="h5">Fresh Ingredients</h3>
                        <p class="card-text">We use only the freshest ingredients in our dishes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-star fa-3x text-primary mb-3"></i>
                        <h3 class="h5">Authentic Taste</h3>
                        <p class="card-text">Experience genuine Northeast Indian flavors</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/footer.php'; ?> 