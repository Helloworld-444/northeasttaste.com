<?php
require_once __DIR__ . '/../models/Dish.php';
require_once __DIR__ . '/../helpers.php';

class DishController {
    private $dishModel;
    private const VALID_CATEGORIES = [
        'Manipuri', 'Assamese', 'NagaLand', 'Mizoram', 
        'Meghalaya', 'Tripura', 'Sikkim', 'Arunachal Pradesh'
    ];
    private const ITEMS_PER_PAGE = 12;

    public function __construct() {
        global $pdo;
        $this->dishModel = new Dish($pdo);
    }

    /**
     * Display list of dishes with pagination and category filtering
     */
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;
        
        // Validate category if provided
        if ($category && !in_array($category, self::VALID_CATEGORIES)) {
            redirect(SITE_URL . '/views/404.php');
            exit();
        }

        try {
            $dishes = $this->dishModel->getAllDishes($page, self::ITEMS_PER_PAGE, $category);
            $totalDishes = $this->dishModel->getTotalDishes($category);
            $totalPages = ceil($totalDishes / self::ITEMS_PER_PAGE);

            if (isset($_GET['ajax'])) {
                jsonResponse([
                    'success' => true,
                    'dishes' => $dishes,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_dishes' => $totalDishes
                    ]
                ]);
            } else {
                require '../views/menu.php';
            }
        } catch (Exception $e) {
            error_log("Error getting dishes: " . $e->getMessage());
            if (isset($_GET['ajax'])) {
                jsonResponse(['error' => 'Failed to load dishes'], 500);
            } else {
                $_SESSION['error'] = 'Failed to load dishes';
                redirect(SITE_URL . '/views/menu.php');
            }
        }
    }

    /**
     * Search dishes by name or description
     */
    public function search() {
        $query = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';
        $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

        if (empty($query)) {
            jsonResponse(['error' => 'Search query is required'], 400);
            return;
        }

        // Validate category if provided
        if ($category && !in_array($category, self::VALID_CATEGORIES)) {
            jsonResponse(['error' => 'Invalid category'], 400);
            return;
        }

        try {
            $results = $this->dishModel->searchDishes($query);
            if ($category) {
                $results = array_filter($results, function($dish) use ($category) {
                    return $dish['category'] === $category;
                });
            }
            jsonResponse([
                'success' => true,
                'dishes' => array_values($results)
            ]);
        } catch (Exception $e) {
            error_log("Error searching dishes: " . $e->getMessage());
            jsonResponse(['error' => 'Failed to search dishes'], 500);
        }
    }

    /**
     * Display dish details
     */
    public function details($id) {
        if (!is_numeric($id) || $id <= 0) {
            if (isset($_GET['ajax'])) {
                jsonResponse(['error' => 'Invalid dish ID'], 400);
            } else {
                redirect(SITE_URL . '/views/404.php');
            }
            return;
        }

        try {
            $dish = $this->dishModel->getDishById($id);
            if (!$dish) {
                if (isset($_GET['ajax'])) {
                    jsonResponse(['error' => 'Dish not found'], 404);
                } else {
                    redirect(SITE_URL . '/views/404.php');
                }
                return;
            }

            if (isset($_GET['ajax'])) {
                jsonResponse([
                    'success' => true,
                    'dish' => $dish
                ]);
            } else {
                require '../views/dish_details.php';
            }
        } catch (Exception $e) {
            error_log("Error getting dish details: " . $e->getMessage());
            if (isset($_GET['ajax'])) {
                jsonResponse(['error' => 'Failed to load dish details'], 500);
            } else {
                $_SESSION['error'] = 'Failed to load dish details';
                redirect(SITE_URL . '/views/menu.php');
            }
        }
    }

    /**
     * Get dish image
     */
    public function getImage($id) {
        if (!is_numeric($id) || $id <= 0) {
            header('HTTP/1.1 400 Bad Request');
            exit();
        }

        try {
            $dish = $this->dishModel->getDishById($id);
            if (!$dish || !$dish['image_blob'] || !$dish['image_type']) {
                throw new Exception('Image not found');
            }

            // Set cache headers for better performance
            $etag = md5($dish['image_blob']);
            header('ETag: "' . $etag . '"');
            header('Cache-Control: public, max-age=86400'); // Cache for 24 hours

            // Check if the image has been modified
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == '"' . $etag . '"') {
                header('HTTP/1.1 304 Not Modified');
                exit();
            }

            header('Content-Type: ' . $dish['image_type']);
            echo $dish['image_blob'];
        } catch (Exception $e) {
            error_log("Error getting dish image: " . $e->getMessage());
            header('HTTP/1.1 404 Not Found');
            exit();
        }
    }

    /**
     * Filter dishes by category
     */
    public function filterByCategory() {
        $category = sanitizeInput($_GET['category']);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        if (empty($category) || !in_array($category, self::VALID_CATEGORIES)) {
            jsonResponse(['error' => 'Invalid category'], 400);
        }

        try {
            $dishes = $this->dishModel->getAllDishes($page, self::ITEMS_PER_PAGE, $category);
            $totalDishes = $this->dishModel->getTotalDishes($category);
            $totalPages = ceil($totalDishes / self::ITEMS_PER_PAGE);

            jsonResponse([
                'success' => true,
                'dishes' => $dishes,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_dishes' => $totalDishes
                ]
            ]);
        } catch (Exception $e) {
            error_log("Error filtering dishes: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => 'Failed to filter dishes'
            ], 500);
        }
    }

    /**
     * Toggle dish availability
     */
    public function toggleAvailability() {
        startSession();
        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
        }

        $dishId = (int)$_POST['dish_id'];
        
        try {
            if ($this->dishModel->toggleAvailability($dishId)) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Dish availability updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update dish availability');
            }
        } catch (Exception $e) {
            error_log("Error toggling dish availability: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => 'Failed to update dish availability'
            ], 500);
        }
    }

    /**
     * Create a new dish
     */
    public function create() {
        startSession();
        if (!isAdmin()) {
            redirect(SITE_URL . '/views/403.php');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require '../views/admin/dish_form.php';
            return;
        }

        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $price = (float)$_POST['price'];
        $category = sanitizeInput($_POST['category']);

        // Validation
        $errors = [];
        if (empty($name)) $errors['name'] = 'Name is required';
        if (empty($description)) $errors['description'] = 'Description is required';
        if ($price <= 0) $errors['price'] = 'Price must be greater than 0';
        if (empty($category)) $errors['category'] = 'Category is required';
        if (!in_array($category, self::VALID_CATEGORIES)) {
            $errors['category'] = 'Invalid category selected';
        }

        // Handle image upload
        $imageBlob = null;
        $imageType = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                $errors['image'] = 'Invalid image type. Only JPG, PNG and GIF are allowed.';
            } elseif ($_FILES['image']['size'] > $maxSize) {
                $errors['image'] = 'Image size must be less than 5MB';
            } else {
                try {
                    $imageInfo = getimagesize($_FILES['image']['tmp_name']);
                    if ($imageInfo === false) {
                        $errors['image'] = 'Invalid image file';
                    } else {
                        $imageBlob = file_get_contents($_FILES['image']['tmp_name']);
                        $imageType = $_FILES['image']['type'];
                    }
                } catch (Exception $e) {
                    error_log("Error processing image upload: " . $e->getMessage());
                    $errors['image'] = 'Failed to process image';
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            redirect(SITE_URL . '/admin/dishes/create');
            return;
        }

        try {
            if ($this->dishModel->createDish($name, $description, $price, $category, $imageBlob, $imageType)) {
                $_SESSION['success'] = 'Dish created successfully';
                redirect(SITE_URL . '/admin/dishes');
            } else {
                throw new Exception('Failed to create dish');
            }
        } catch (Exception $e) {
            error_log("Error creating dish: " . $e->getMessage());
            $_SESSION['error'] = 'Failed to create dish';
            redirect(SITE_URL . '/admin/dishes/create');
        }
    }

    /**
     * Update an existing dish
     */
    public function update($id) {
        startSession();
        if (!isAdmin()) {
            redirect(SITE_URL . '/views/403.php');
            exit();
        }
        
        try {
            $dish = $this->dishModel->getDishById($id);
            if (!$dish) {
                redirect(SITE_URL . '/views/404.php');
                exit();
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                require '../views/admin/dish_form.php';
                return;
            }

            $data = [
                'name' => sanitizeInput($_POST['name']),
                'description' => sanitizeInput($_POST['description']),
                'price' => (float)$_POST['price'],
                'category' => sanitizeInput($_POST['category'])
            ];

            // Validation
            $errors = [];
            if (empty($data['name'])) $errors['name'] = 'Name is required';
            if (empty($data['description'])) $errors['description'] = 'Description is required';
            if ($data['price'] <= 0) $errors['price'] = 'Price must be greater than 0';
            if (!in_array($data['category'], self::VALID_CATEGORIES)) {
                $errors['category'] = 'Invalid category selected';
            }

            if (!empty($errors)) {
                require '../views/admin/dish_form.php';
                return;
            }

            // Handle image update if new image is uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $maxSize = 5 * 1024 * 1024; // 5MB

                if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                    $errors['image'] = 'Invalid image type. Only JPG, PNG and GIF are allowed.';
                } elseif ($_FILES['image']['size'] > $maxSize) {
                    $errors['image'] = 'Image size must be less than 5MB';
                } else {
                    $data['image_blob'] = file_get_contents($_FILES['image']['tmp_name']);
                    $data['image_type'] = $_FILES['image']['type'];
                }

                if (!empty($errors)) {
                    require '../views/admin/dish_form.php';
                    return;
                }
            }

            if ($this->dishModel->updateDish($id, $data)) {
                redirect(SITE_URL . '/views/admin/dishes.php?updated=1');
                exit();
            } else {
                throw new Exception('Failed to update dish');
            }
        } catch (Exception $e) {
            error_log("Error updating dish: " . $e->getMessage());
            $error = 'An error occurred while updating the dish';
            require '../views/admin/dish_form.php';
        }
    }
}

// Handle the request
if (isset($_GET['action'])) {
    $controller = new DishController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'index':
            $controller->index();
            break;
        case 'search':
            $controller->search();
            break;
        case 'details':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $controller->details($id);
            break;
        case 'image':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $controller->getImage($id);
            break;
        case 'filter':
            $controller->filterByCategory();
            break;
        case 'toggle':
            $controller->toggleAvailability();
            break;
        case 'create':
            $controller->create();
            break;
        case 'update':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $controller->update($id);
            break;
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
} 