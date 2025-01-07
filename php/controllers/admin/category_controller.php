<?php
require_once dirname(dirname(dirname(__DIR__))) . '/php/config/config.php';
require_once dirname(dirname(__DIR__)) . '/db.php';
require_once dirname(dirname(__DIR__)) . '/helpers.php';

// Start session and check admin access
session_start();
if (!isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get the list of available categories from the ENUM
        $stmt = $pdo->query("SHOW COLUMNS FROM dishes LIKE 'category'");
        $enumColumn = $stmt->fetch(PDO::FETCH_ASSOC);
        preg_match("/^enum\((.*)\)$/", $enumColumn['Type'], $matches);
        $validCategories = array_map(function($val) { 
            return trim($val, "'"); 
        }, explode(',', $matches[1]));
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'list':
                // Return the list of categories from ENUM
                echo json_encode([
                    'success' => true,
                    'categories' => array_map(function($cat) {
                        return [
                            'name' => $cat,
                            'description' => 'Dishes from ' . $cat
                        ];
                    }, $validCategories)
                ]);
                break;
                
            case 'add':
            case 'edit':
            case 'update':
            case 'delete':
                // These operations are not allowed since categories are fixed in ENUM
                throw new Exception('Categories are predefined and cannot be modified. Please contact the database administrator to modify the category list.');
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    error_log('Category Controller Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 