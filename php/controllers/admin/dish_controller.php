<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once dirname(dirname(__DIR__)) . '/db.php';
require_once dirname(dirname(__DIR__)) . '/models/Dish.php';
require_once dirname(dirname(__DIR__)) . '/helpers.php';

class DishController {
    private $pdo;
    private $dishModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->dishModel = new Dish($pdo);
    }

    public function handleRequest() {
        startSession();
        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
            return;
        }

        $action = $_POST['action'] ?? ($_GET['action'] ?? null);

        switch ($action) {
            case 'add':
                $this->addDish();
                break;
            case 'edit':
                $this->editDish();
                break;
            case 'delete':
                $this->deleteDish();
                break;
            case 'toggle_availability':
                $this->toggleAvailability();
                break;
            case 'get':
                $this->getDish();
                break;
            default:
                jsonResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }

    public function addDish() {
        try {
            if (!isset($_POST['name'], $_POST['description'], $_POST['price'], $_POST['category'])) {
                throw new Exception('Missing required fields');
            }

            $name = $_POST['name'];
            $description = $_POST['description'];
            $price = $_POST['price'];
            $category = $_POST['category'];
            
            $imageBlob = null;
            $imageType = null;
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageBlob = file_get_contents($_FILES['image']['tmp_name']);
                $imageType = $_FILES['image']['type'];
            }

            $success = $this->dishModel->createDish(
                $name,
                $description,
                $price,
                $category,
                $imageBlob,
                $imageType
            );

            if ($success) {
                jsonResponse(['success' => true, 'message' => 'Dish created successfully']);
            } else {
                throw new Exception('Failed to create dish');
            }
        } catch (Exception $e) {
            error_log("Error in addDish: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function editDish() {
        try {
            if (!isset($_POST['dish_id'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['category'])) {
                throw new Exception('Missing required fields');
            }

            $dishId = $_POST['dish_id'];
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);
            $price = (float)$_POST['price'];
            $category = sanitizeInput($_POST['category']);

            // First update the basic dish information
            $success = $this->dishModel->updateDish($dishId, $name, $description, $price, $category);
            if (!$success) {
                throw new Exception('Failed to update dish');
            }

            // Handle image update if a new image is uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imageBlob = file_get_contents($_FILES['image']['tmp_name']);
                $imageType = $_FILES['image']['type'];
                
                $success = $this->dishModel->updateImage($dishId, $imageBlob, $imageType);
                if (!$success) {
                    throw new Exception('Failed to update dish image');
                }
            }

            jsonResponse(['success' => true, 'message' => 'Dish updated successfully']);
        } catch (Exception $e) {
            error_log("Error in editDish: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    private function deleteDish() {
        try {
            $dishId = $_GET['id'] ?? null;
            if (!$dishId) {
                throw new Exception('Missing dish ID');
            }

            if ($this->dishModel->deleteDish($dishId)) {
                header('Location: /fooddelivery/php/views/admin/dishes.php');
                exit();
            } else {
                throw new Exception('Failed to delete dish');
            }
        } catch (Exception $e) {
            error_log("Error deleting dish: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header('Location: /fooddelivery/php/views/admin/dishes.php');
            exit();
        }
    }

    private function getDish() {
        try {
            $dishId = $_GET['id'] ?? null;
            if (!$dishId) {
                throw new Exception('Missing dish ID');
            }

            $dish = $this->dishModel->getDishById($dishId);
            if (!$dish) {
                throw new Exception('Dish not found');
            }

            jsonResponse(['success' => true, 'dish' => $dish]);
        } catch (Exception $e) {
            error_log("Error getting dish: " . $e->getMessage());
            jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function toggleAvailability() {
        try {
            $dishId = $_POST['dish_id'] ?? null;
            if (!$dishId) {
                throw new Exception('Missing dish ID');
            }

            $success = $this->dishModel->toggleAvailability($dishId);
            if (!$success) {
                throw new Exception('Failed to update dish availability');
            }

            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            error_log("Error toggling dish availability: " . $e->getMessage());
            jsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}

// Handle the request
$controller = new DishController();
$controller->handleRequest(); 