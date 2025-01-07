<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once __DIR__ . '/../helpers.php';

class ContactController {
    public function handleContactForm() {
        // Start output buffering to prevent any unwanted output
        ob_start();
        
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Validate required fields
            $requiredFields = ['name', 'email', 'subject', 'message'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }

            // Validate email
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            // Sanitize input
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $phone = !empty($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';
            $subject = sanitizeInput($_POST['subject']);
            $message = sanitizeInput($_POST['message']);

            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // Initialize admin_messages array if it doesn't exist
            if (!isset($_SESSION['admin_messages'])) {
                $_SESSION['admin_messages'] = [];
            }

            // Add the message to admin_messages
            $_SESSION['admin_messages'][] = [
                'user_id' => $name . ' (' . $email . ')',
                'message' => "[{$subject}] {$message}" . ($phone ? " - Phone: {$phone}" : ""),
                'timestamp' => date('Y-m-d H:i:s'),
                'read' => false
            ];

            // Log the contact form submission
            error_log(sprintf(
                "Contact form submission - Name: %s, Email: %s, Subject: %s",
                $name,
                $email,
                $subject
            ));
            
            jsonResponse(['success' => true, 'message' => 'Message received successfully']);
            
        } catch (Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            jsonResponse([
                'success' => false, 
                'message' => 'There was an error processing your message: ' . $e->getMessage()
            ], 400);
        }
    }
}

// Handle the request
$controller = new ContactController();
$controller->handleContactForm(); 