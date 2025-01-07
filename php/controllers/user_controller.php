<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers.php';

class UserController {
    private $userModel;

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require '../views/register.php';
            return;
        }

        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);

        // Validation
        $errors = [];
        if (empty($firstName)) $errors['first_name'] = 'First name is required';
        if (empty($lastName)) $errors['last_name'] = 'Last name is required';
        if (empty($email)) $errors['email'] = 'Email is required';
        if (empty($password)) $errors['password'] = 'Password is required';
        if (empty($address)) $errors['address'] = 'Address is required';

        if (!empty($errors)) {
            require '../views/register.php';
            return;
        }

        if ($this->userModel->createUser($email, $password, $firstName, $lastName, $phone, $address)) {
            header('Location: ' . SITE_URL . '/views/login.php?registered=1');
            exit();
        } else {
            $error = 'Registration failed. Please try again.';
            require '../views/register.php';
        }
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require '../views/login.php';
            return;
        }

        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];

        $user = $this->userModel->authenticate($email, $password);

        if ($user) {
            startSession();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['user_name'] = $user['first_name'];

            if ($user['is_admin']) {
                header('Location: ' . SITE_URL . '/views/admin/dashboard.php');
            } else {
                header('Location: ' . SITE_URL . '/index.php');
            }
            exit();
        } else {
            $error = 'Invalid email or password';
            require '../views/login.php';
        }
    }

    public function logout() {
        startSession();
        session_destroy();
        header('Location: ' . SITE_URL . '/index.php');
        exit();
    }

    public function profile() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user_id'];
            $data = [
                'first_name' => sanitizeInput($_POST['first_name']),
                'last_name' => sanitizeInput($_POST['last_name']),
                'phone' => sanitizeInput($_POST['phone']),
                'address' => sanitizeInput($_POST['address'])
            ];

            if ($this->userModel->updateUser($userId, $data)) {
                $success = 'Profile updated successfully';
            } else {
                $error = 'Failed to update profile';
            }
        }

        $user = $this->userModel->getUserById($_SESSION['user_id']);
        require '../views/profile.php';
    }

    public function changePassword() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
        }

        $userId = $_SESSION['user_id'];
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];

        $user = $this->userModel->getUserById($userId);
        if (!password_verify($currentPassword, $user['password_hash'])) {
            jsonResponse(['error' => 'Current password is incorrect'], 400);
        }

        if ($this->userModel->updatePassword($userId, $newPassword)) {
            jsonResponse(['message' => 'Password updated successfully']);
        } else {
            jsonResponse(['error' => 'Failed to update password'], 500);
        }
    }

    public function deactivateAccount() {
        requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
        }

        $userId = $_SESSION['user_id'];
        if ($this->userModel->deactivateUser($userId)) {
            session_destroy();
            jsonResponse(['message' => 'Account deactivated successfully']);
        } else {
            jsonResponse(['error' => 'Failed to deactivate account'], 500);
        }
    }
} 