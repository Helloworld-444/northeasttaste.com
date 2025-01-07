<?php
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../helpers.php';

class PaymentController {
    private $paymentModel;
    private $orderModel;
    private const VALID_PAYMENT_METHODS = ['cash', 'card', 'upi'];
    private const VALID_PAYMENT_STATUSES = ['pending', 'completed', 'failed', 'refunded'];

    public function __construct() {
        global $pdo;
        $this->paymentModel = new Payment($pdo);
        $this->orderModel = new Order($pdo);
    }

    /**
     * Process a new payment
     */
    public function processPayment() {
        startSession();
        header('Content-Type: application/json');

        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Please login to process payment'], 401);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }

        try {
            // Validate required fields
            if (!isset($_POST['order_id']) || !isset($_POST['payment_method'])) {
                throw new Exception('Missing required payment information');
            }

            $orderId = (int)$_POST['order_id'];
            $method = sanitizeInput($_POST['payment_method']);
            
            // Validate order exists and belongs to user
            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                throw new Exception('Order not found');
            }

            if ($order['user_id'] != $_SESSION['user_id']) {
                throw new Exception('Unauthorized access to order');
            }

            // Check if order is already paid
            if ($order['payment_status'] === 'paid') {
                throw new Exception('Order is already paid');
            }

            // Validate payment method
            if (!in_array($method, self::VALID_PAYMENT_METHODS)) {
                throw new Exception('Invalid payment method');
            }

            // Validate payment amount against order total
            if (!$this->paymentModel->validatePaymentAmount($orderId, $order['total_amount'])) {
                throw new Exception('Invalid payment amount');
            }

            // Begin transaction
            $this->paymentModel->beginTransaction();

            try {
                // Create payment record
                if (!$this->paymentModel->createPayment($orderId, $order['total_amount'], $method)) {
                    throw new Exception('Payment processing failed');
                }

                // Update order payment status and status
                if (!$this->orderModel->updatePaymentStatus($orderId, 'paid')) {
                    throw new Exception('Failed to update order payment status');
                }
                if (!$this->orderModel->updateStatus($orderId, 'confirmed')) {
                    throw new Exception('Failed to update order status');
                }

                // Commit transaction
                $this->paymentModel->commit();
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'data' => [
                        'order_id' => $orderId,
                        'redirect' => SITE_URL . '/views/order_confirmation.php?order_id=' . $orderId
                    ]
                ]);
            } catch (Exception $e) {
                $this->paymentModel->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Payment processing error: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus() {
        startSession();
        header('Content-Type: application/json');

        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }

        try {
            // Validate required fields
            if (!isset($_POST['payment_id']) || !isset($_POST['status'])) {
                throw new Exception('Missing required payment information');
            }

            $paymentId = (int)$_POST['payment_id'];
            $status = sanitizeInput($_POST['status']);
            $transactionId = isset($_POST['transaction_id']) ? sanitizeInput($_POST['transaction_id']) : null;

            // Validate payment exists
            $payment = $this->paymentModel->getPaymentById($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }

            // Validate status
            if (!in_array($status, self::VALID_PAYMENT_STATUSES)) {
                throw new Exception('Invalid payment status');
            }

            // Begin transaction
            $this->paymentModel->beginTransaction();

            try {
                // Update payment status
                if (!$this->paymentModel->updateStatus($paymentId, $status, $transactionId)) {
                    throw new Exception('Failed to update payment status');
                }

                // If payment is completed, update order status
                if ($status === 'completed') {
                    if (!$this->orderModel->updateStatus($payment['order_id'], 'confirmed')) {
                        throw new Exception('Failed to update order status');
                    }
                    if (!$this->orderModel->updatePaymentStatus($payment['order_id'], 'paid')) {
                        throw new Exception('Failed to update order payment status');
                    }
                }

                // Commit transaction
                $this->paymentModel->commit();
                
                jsonResponse([
                    'success' => true,
                    'message' => 'Payment status updated successfully'
                ]);
            } catch (Exception $e) {
                $this->paymentModel->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Payment status update error: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process refund for a payment
     */
    public function processRefund() {
        startSession();
        header('Content-Type: application/json');

        if (!isAdmin()) {
            jsonResponse(['error' => 'Unauthorized access'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }

        try {
            // Validate required fields
            if (!isset($_POST['payment_id'])) {
                throw new Exception('Payment ID is required');
            }

            $paymentId = (int)$_POST['payment_id'];
            $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : null;

            // Validate payment exists and can be refunded
            $payment = $this->paymentModel->getPaymentById($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }

            if ($payment['status'] !== 'completed') {
                throw new Exception('Only completed payments can be refunded');
            }

            // Begin transaction
            $this->paymentModel->beginTransaction();

            try {
                // Process refund
                if (!$this->paymentModel->processRefund($paymentId, $reason)) {
                    throw new Exception('Failed to process refund');
                }

                // Update order status to cancelled
                if (!$this->orderModel->updateStatus($payment['order_id'], 'cancelled')) {
                    throw new Exception('Failed to update order status');
                }

                // Commit transaction
                $this->paymentModel->commit();

                jsonResponse([
                    'success' => true,
                    'message' => 'Refund processed successfully'
                ]);
            } catch (Exception $e) {
                $this->paymentModel->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Refund processing error: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails($paymentId) {
        startSession();
        
        try {
            if (!isLoggedIn()) {
                redirect(SITE_URL . '/views/login.php');
                exit();
            }

            if (!is_numeric($paymentId) || $paymentId <= 0) {
                throw new Exception('Invalid payment ID');
            }
            
            $payment = $this->paymentModel->getPaymentById($paymentId);
            if (!$payment) {
                throw new Exception('Payment not found');
            }

            // Check if user has access to this payment
            $order = $this->orderModel->getOrderById($payment['order_id']);
            if (!$order) {
                throw new Exception('Order not found');
            }

            if (!isAdmin() && $order['user_id'] != $_SESSION['user_id']) {
                redirect(SITE_URL . '/views/403.php');
                exit();
            }

            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                jsonResponse([
                    'success' => true,
                    'payment' => $payment,
                    'order' => $order
                ]);
            } else {
                require '../views/payment_details.php';
            }
        } catch (Exception $e) {
            error_log("Error getting payment details: " . $e->getMessage());
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                jsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            } else {
                redirect(SITE_URL . '/views/404.php');
            }
        }
    }

    /**
     * Get payments by status
     */
    public function getPaymentsByStatus() {
        startSession();
        
        try {
            if (!isAdmin()) {
                if (isset($_GET['ajax'])) {
                    jsonResponse(['error' => 'Unauthorized access'], 403);
                } else {
                    redirect(SITE_URL . '/views/403.php');
                }
                return;
            }
            
            $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'pending';
            
            // Validate status
            if (!in_array($status, self::VALID_PAYMENT_STATUSES)) {
                throw new Exception('Invalid payment status');
            }

            $payments = $this->paymentModel->getPaymentsByStatus($status);
            
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                jsonResponse([
                    'success' => true,
                    'payments' => $payments
                ]);
            } else {
                require '../views/admin/payments.php';
            }
        } catch (Exception $e) {
            error_log("Error getting payments: " . $e->getMessage());
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                jsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            } else {
                $error = 'Failed to retrieve payments';
                require '../views/admin/payments.php';
            }
        }
    }
}

// Handle the request
if (isset($_GET['action'])) {
    $controller = new PaymentController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'process':
            $controller->processPayment();
            break;
        case 'update_status':
            $controller->updatePaymentStatus();
            break;
        case 'refund':
            $controller->processRefund();
            break;
        case 'details':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            $controller->getPaymentDetails($id);
            break;
        case 'list':
            $controller->getPaymentsByStatus();
            break;
        default:
            header('Content-Type: application/json');
            http_response_code(404);
            jsonResponse(['error' => 'Action not found'], 404);
    }
} 