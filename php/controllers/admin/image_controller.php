<?php
require_once __DIR__ . '/../../models/Dish.php';
require_once __DIR__ . '/../../helpers.php';

class AdminImageController {
    private $dishModel;
    private const MAX_FILE_SIZE = 5242880; // 5MB
    private const ALLOWED_TYPES = [
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/gif' => '.gif'
    ];

    public function __construct() {
        global $pdo;
        $this->dishModel = new Dish($pdo);
    }

    /**
     * Upload a new image for a dish
     */
    public function upload() {
        startSession();
        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }

        try {
            // Validate dish ID
            if (!isset($_POST['dish_id'])) {
                throw new Exception('Dish ID is required');
            }

            $dishId = (int)$_POST['dish_id'];
            if ($dishId <= 0) {
                throw new Exception('Invalid dish ID');
            }

            // Check if dish exists
            $dish = $this->dishModel->getDishById($dishId);
            if (!$dish) {
                throw new Exception('Dish not found');
            }

            // Validate file upload
            if (!isset($_FILES['image'])) {
                throw new Exception('No image file provided');
            }

            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                switch ($_FILES['image']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new Exception('File size exceeds limit of 5MB');
                    case UPLOAD_ERR_PARTIAL:
                        throw new Exception('File was only partially uploaded');
                    case UPLOAD_ERR_NO_FILE:
                        throw new Exception('No file was uploaded');
                    default:
                        throw new Exception('File upload error occurred');
                }
            }

            $file = $_FILES['image'];

            // Validate file size
            if ($file['size'] > self::MAX_FILE_SIZE) {
                throw new Exception('File size exceeds limit of 5MB');
            }

            // Validate file type
            if (!isset(self::ALLOWED_TYPES[$file['type']])) {
                throw new Exception('Invalid file type. Allowed types: JPG, PNG, GIF');
            }

            // Validate image dimensions and integrity
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception('Invalid image file');
            }

            // Read image data
            $imageData = file_get_contents($file['tmp_name']);
            if ($imageData === false) {
                throw new Exception('Failed to read image file');
            }

            // Update dish with image data
            if (!$this->dishModel->updateDishImage($dishId, $imageData, $file['type'])) {
                throw new Exception('Failed to save image');
            }

            jsonResponse([
                'success' => true,
                'message' => 'Image uploaded successfully'
            ]);

        } catch (Exception $e) {
            error_log("Error uploading image: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dish image
     */
    public function getImage() {
        try {
            if (!isset($_GET['id'])) {
                throw new Exception('Dish ID is required');
            }

            $dishId = (int)$_GET['id'];
            if ($dishId <= 0) {
                throw new Exception('Invalid dish ID');
            }

            $dish = $this->dishModel->getDishById($dishId);
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
                exit;
            }

            // Set proper content type and output image
            header('Content-Type: ' . $dish['image_type']);
            header('Content-Length: ' . strlen($dish['image_blob']));
            echo $dish['image_blob'];
            exit;
        } catch (Exception $e) {
            error_log("Error retrieving image: " . $e->getMessage());
            header('HTTP/1.0 404 Not Found');
            exit;
        }
    }

    /**
     * Delete dish image
     */
    public function deleteImage() {
        startSession();
        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }

        try {
            if (!isset($_POST['dish_id'])) {
                throw new Exception('Dish ID is required');
            }

            $dishId = (int)$_POST['dish_id'];
            if ($dishId <= 0) {
                throw new Exception('Invalid dish ID');
            }

            // Check if dish exists and has an image
            $dish = $this->dishModel->getDishById($dishId);
            if (!$dish) {
                throw new Exception('Dish not found');
            }

            if (!$dish['image_blob']) {
                throw new Exception('No image to delete');
            }

            // Clear image data
            if (!$this->dishModel->updateDishImage($dishId, null, null)) {
                throw new Exception('Failed to delete image');
            }

            jsonResponse([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);

        } catch (Exception $e) {
            error_log("Error deleting image: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

// Handle the request
if (isset($_GET['action'])) {
    $controller = new AdminImageController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'upload':
            $controller->upload();
            break;
        case 'get':
            $controller->getImage();
            break;
        case 'delete':
            $controller->deleteImage();
            break;
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
} 