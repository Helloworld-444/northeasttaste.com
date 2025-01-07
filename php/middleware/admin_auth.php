<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../models/User.php';

// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /fooddelivery/php/views/login.php');
    exit();
}

// Check if user is admin
$user = new User($pdo);
$userData = $user->getUserById($_SESSION['user_id']);
if (!$userData || !$userData['is_admin']) {
    header('Location: /fooddelivery/php/views/403.php');
    exit();
} 