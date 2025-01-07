<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../models/DishImage.php';

$dishImage = new DishImage($pdo);

// Handle image requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $image = $dishImage->getImage($_GET['id']);
    
    if ($image) {
        // Set the content type header based on the stored image type
        header('Content-Type: ' . $image['image_type']);
        // Output the image data
        echo $image['image_data'];
        exit;
    } else {
        // If image not found, return a 404
        header("HTTP/1.0 404 Not Found");
        echo "Image not found";
        exit;
    }
}

// Handle image uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false];
    
    if (!isset($_POST['dish_id']) || !isset($_FILES['image'])) {
        $response['error'] = 'Missing required parameters';
        echo json_encode($response);
        exit;
    }

    $dishId = $_POST['dish_id'];
    $file = $_FILES['image'];
    $isPrimary = isset($_POST['is_primary']) ? (bool)$_POST['is_primary'] : false;

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        $response['error'] = 'Invalid file type. Only JPG, PNG and GIF are allowed.';
        echo json_encode($response);
        exit;
    }

    // Read the file data
    $imageData = file_get_contents($file['tmp_name']);
    if ($imageData === false) {
        $response['error'] = 'Failed to read image file';
        echo json_encode($response);
        exit;
    }

    // Add the image to the database
    if ($dishImage->addImage($dishId, $imageData, $file['type'], $isPrimary)) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Failed to save image';
    }

    echo json_encode($response);
    exit;
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $response = ['success' => false];
    
    // Parse the DELETE request body
    parse_str(file_get_contents('php://input'), $deleteData);
    
    if (!isset($deleteData['image_id'])) {
        $response['error'] = 'Missing image ID';
        echo json_encode($response);
        exit;
    }

    if ($dishImage->deleteImage($deleteData['image_id'])) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Failed to delete image';
    }

    echo json_encode($response);
    exit;
}

// If we get here, it's an invalid request
header("HTTP/1.0 400 Bad Request");
echo "Invalid request";
exit; 