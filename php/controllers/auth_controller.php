<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php';

class AuthController {
    private $userModel;

    public function __construct() {
        global $pdo;
        $this->userModel = new User($pdo);
    }

    public function login() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            }

            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Validation
            if (empty($email) || empty($password)) {
                jsonResponse(['success' => false, 'message' => 'Email and password are required']);
            }

            $user = $this->userModel->authenticate($email, $password);

            if ($user) {
                startSession();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['is_admin'] = (int)$user['is_admin'];

                jsonResponse([
                    'success' => true,
                    'message' => 'Login successful!',
                    'data' => [
                        'redirect' => $user['is_admin'] ? url('php/views/admin/dashboard.php') : url('index.php'),
                        'user_id' => $user['user_id']
                    ]
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ], 401);
            }
        } catch (Exception $e) {
            logError('Login error: ' . $e->getMessage());
            jsonResponse([
                'success' => false,
                'message' => 'An error occurred during login. Please try again.'
            ], 500);
        }
    }

    public function register() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            }

            // Get and sanitize input
            $firstName = sanitizeInput($_POST['first_name'] ?? '');
            $lastName = sanitizeInput($_POST['last_name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $phone = sanitizeInput($_POST['phone'] ?? '');
            $address = sanitizeInput($_POST['address'] ?? '');

            // Validation
            $errors = [];
            if (empty($firstName)) $errors['first_name'] = 'First name is required';
            if (empty($lastName)) $errors['last_name'] = 'Last name is required';
            if (empty($email)) $errors['email'] = 'Email is required';
            if (empty($password)) $errors['password'] = 'Password is required';
            if (empty($address)) $errors['address'] = 'Address is required';
            if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format';
            if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters long';

            if (!empty($errors)) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
            }

            if ($this->userModel->createUser($email, $password, $firstName, $lastName, $phone, $address)) {
                startSession();
                $user = $this->userModel->authenticate($email, $password);
                if ($user) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['is_admin'] = $user['is_admin'] ?? 0;
                }

                jsonResponse([
                    'success' => true,
                    'message' => 'Registration successful!',
                    'data' => [
                        'redirect' => url('index.php')
                    ]
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Registration failed. Email might already be in use.'
                ], 400);
            }
        } catch (Exception $e) {
            logError('Registration error: ' . $e->getMessage());
            jsonResponse([
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.'
            ], 500);
        }
    }

    public function logout() {
        try {
            startSession();
            
            // Clear all session data
            session_unset();
            session_destroy();
            
            // Clear session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            // Start a new session for flash message
            session_start();
            $_SESSION['flash_message'] = 'You have been successfully logged out.';
            $_SESSION['flash_type'] = 'success';
            
            // Redirect to home page
            header('Location: ' . url('index.php'));
            exit();
        } catch (Exception $e) {
            logError('Logout error: ' . $e->getMessage());
            header('Location: ' . url('index.php'));
            exit();
        }
    }
}

// Initialize error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logError("PHP Error [$errno]: $errstr in $errfile on line $errline");
    if (isAjaxRequest()) {
        jsonResponse([
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again.'
        ], 500);
    }
    return true;
});

// Handle the request
try {
    if (isset($_GET['action'])) {
        $controller = new AuthController();
        $action = $_GET['action'];
        
        switch ($action) {
            case 'login':
                $controller->login();
                break;
            case 'register':
                $controller->register();
                break;
            case 'logout':
                $controller->logout();
                break;
            default:
                if (isAjaxRequest()) {
                    jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
                } else {
                    header('Location: ' . url('index.php'));
                    exit();
                }
        }
    } else {
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => 'No action specified'], 400);
        } else {
            header('Location: ' . url('index.php'));
            exit();
        }
    }
} catch (Exception $e) {
    logError('Auth Controller error: ' . $e->getMessage());
    if (isAjaxRequest()) {
        jsonResponse([
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again.'
        ], 500);
    } else {
        header('Location: ' . url('index.php'));
        exit();
    }
} 