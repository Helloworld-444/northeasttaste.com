<?php
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../helpers.php';

class AdminUserController {
    private $userModel;
    private const ITEMS_PER_PAGE = 20;

    public function __construct() {
        global $pdo;
        $this->userModel = new User($pdo);
    }

    /**
     * List all users with pagination
     */
    public function index() {
        startSession();
        if (!isAdmin()) {
            redirect(SITE_URL . '/views/403.php');
            exit();
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : self::ITEMS_PER_PAGE;
        $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : null;

        try {
            $users = $this->userModel->getAllUsers($page, $limit, $search);
            $totalUsers = $this->userModel->getTotalUsers($search);
            $totalPages = ceil($totalUsers / $limit);

            if (isset($_GET['ajax'])) {
                jsonResponse([
                    'success' => true,
                    'users' => $users,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_users' => $totalUsers
                    ]
                ]);
            } else {
                require '../../views/admin/users.php';
            }
        } catch (Exception $e) {
            error_log("Error listing users: " . $e->getMessage());
            if (isset($_GET['ajax'])) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Failed to retrieve users'
                ], 500);
            } else {
                $error = 'Failed to retrieve users';
                require '../../views/admin/users.php';
            }
        }
    }

    /**
     * Get user details
     */
    public function getUser() {
        startSession();
        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
        }

        try {
            $userId = (int)$_GET['id'];
            if (!$userId) {
                throw new Exception('Invalid user ID');
            }

            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                throw new Exception('User not found');
            }

            jsonResponse([
                'success' => true,
                'user' => $user
            ]);
        } catch (Exception $e) {
            error_log("Error getting user details: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus() {
        startSession();
        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
        }

        try {
            $userId = (int)$_POST['user_id'];
            if (!$userId) {
                throw new Exception('Invalid user ID');
            }

            // Don't allow deactivating own account
            if ($userId === $_SESSION['user_id']) {
                throw new Exception('Cannot deactivate your own account');
            }

            if ($this->userModel->toggleStatus($userId)) {
                jsonResponse([
                    'success' => true,
                    'message' => 'User status updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update user status');
            }
        } catch (Exception $e) {
            error_log("Error toggling user status: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user details
     */
    public function update() {
        startSession();
        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
        }

        try {
            $userId = (int)$_POST['user_id'];
            if (!$userId) {
                throw new Exception('Invalid user ID');
            }

            $data = [
                'first_name' => sanitizeInput($_POST['first_name']),
                'last_name' => sanitizeInput($_POST['last_name']),
                'email' => sanitizeInput($_POST['email']),
                'phone' => sanitizeInput($_POST['phone']),
                'address' => sanitizeInput($_POST['address']),
                'is_admin' => isset($_POST['is_admin']) ? true : false
            ];

            // Validate data
            $this->validateUserData($data);

            // Check if email is already taken by another user
            $existingUser = $this->userModel->getUserByEmail($data['email']);
            if ($existingUser && $existingUser['user_id'] !== $userId) {
                throw new Exception('Email is already taken');
            }

            if ($this->userModel->updateUser($userId, $data)) {
                jsonResponse([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update user');
            }
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user (soft delete)
     */
    public function delete() {
        startSession();
        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
        }

        try {
            $userId = (int)$_POST['user_id'];
            if (!$userId) {
                throw new Exception('Invalid user ID');
            }

            // Don't allow deleting own account
            if ($userId === $_SESSION['user_id']) {
                throw new Exception('Cannot delete your own account');
            }

            if ($this->userModel->deleteUser($userId)) {
                jsonResponse([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete user');
            }
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate user data
     */
    private function validateUserData($data) {
        $errors = [];

        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }

        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        }

        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($data['address'])) {
            $errors[] = 'Address is required';
        }

        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        return true;
    }
}

// Handle the request
if (isset($_GET['action'])) {
    $controller = new AdminUserController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'index':
            $controller->index();
            break;
        case 'get':
            $controller->getUser();
            break;
        case 'toggle':
            $controller->toggleStatus();
            break;
        case 'update':
            $controller->update();
            break;
        case 'delete':
            $controller->delete();
            break;
        default:
            jsonResponse(['error' => 'Action not found'], 404);
    }
} 