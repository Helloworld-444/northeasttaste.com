<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once dirname(__DIR__) . '/helpers.php';

startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    try {
        $message = sanitizeInput($_POST['message']);
        $userId = $_SESSION['user_id'] ?? 'Guest';
        $timestamp = date('Y-m-d H:i:s');

        // Store message in session
        if (!isset($_SESSION['admin_messages'])) {
            $_SESSION['admin_messages'] = [];
        }

        $_SESSION['admin_messages'][] = [
            'user_id' => $userId,
            'message' => $message,
            'timestamp' => $timestamp,
            'read' => false
        ];

        jsonResponse(['success' => true, 'message' => 'Message sent successfully']);
    } catch (Exception $e) {
        error_log("Error sending message: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to send message'], 500);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    try {
        $messageIndex = (int)$_POST['message_index'];
        
        if (isset($_SESSION['admin_messages'][$messageIndex])) {
            $_SESSION['admin_messages'][$messageIndex]['read'] = true;
        }
        
        jsonResponse(['success' => true]);
    } catch (Exception $e) {
        error_log("Error marking message as read: " . $e->getMessage());
        jsonResponse(['success' => false, 'error' => 'Failed to mark message as read'], 500);
    }
} else {
    jsonResponse(['error' => 'Invalid request'], 400);
} 