<?php
// Prevent PHP errors from being displayed
ini_set('display_errors', 0);
require_once dirname(__DIR__) . '/config/config.php';
error_reporting(E_ALL);

// Start output buffering immediately
ob_start();

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Dish.php';
require_once __DIR__ . '/../helpers.php';

// Start session at the beginning
startSession();

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// Log any buffered output
$output = ob_get_clean();
if (!empty($output)) {
    error_log("Unexpected output before class definition: " . $output);
}
ob_start();

class OrderController {
    private $orderModel;
    private $cartModel;
    private $paymentModel;
    private $dishModel;
    private $pdo;
    private const VALID_ORDER_STATUSES = ['pending', 'confirmed', 'preparing', 'delivered', 'cancelled'];

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->orderModel = new Order($pdo);
        $this->cartModel = new Cart($pdo);
        $this->paymentModel = new Payment($pdo);
        $this->dishModel = new Dish($pdo);
    }

    /**
     * Create a new order
     */
    public function createOrder() {
        startSession();

        if (!isLoggedIn()) {
            error_log("User not logged in when attempting to create order");
            return jsonResponse(['error' => 'You must be logged in to create an order'], 401);
        }

        try {
            $userId = $_SESSION['user_id'];
            $cartItems = $this->cartModel->getCartItems($userId);

            if (empty($cartItems)) {
                error_log("Attempted to create order with empty cart for user: " . $userId);
                return jsonResponse(['error' => 'Your cart is empty'], 400);
            }

            // Calculate total amount
            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            // Start transaction
            $this->pdo->beginTransaction();

            // Create order
            $orderData = [
                'user_id' => $userId,
                'delivery_address' => $_POST['delivery_address'] ?? '',
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'pending'
            ];

            $orderId = $this->orderModel->createOrder($orderData);

            if (!$orderId) {
                throw new Exception("Failed to create order: " . $this->orderModel->getLastError());
            }

            // Add order items
            foreach ($cartItems as $item) {
                $success = $this->orderModel->addOrderItem(
                    $orderId,
                    $item['dish_id'],
                    $item['quantity'],
                    $item['price']
                );

                if (!$success) {
                    throw new Exception("Failed to add order item: " . $this->orderModel->getLastError());
                }
            }

            // Create payment record
            $paymentData = [
                'order_id' => $orderId,
                'amount' => $totalAmount,
                'method' => $_POST['payment_method'] ?? 'cash',
                'status' => 'pending'
            ];

            $paymentSuccess = $this->paymentModel->createPayment($paymentData);

            if (!$paymentSuccess) {
                throw new Exception("Failed to create payment record: " . $this->paymentModel->getLastError());
            }

            // Clear cart
            $cartCleared = $this->cartModel->clearCart($userId);

            if (!$cartCleared) {
                throw new Exception("Failed to clear cart: " . $this->cartModel->getLastError());
            }

            // Commit transaction
            $this->pdo->commit();

            // Return success response with order ID and redirect URL
            return jsonResponse([
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId,
                'redirect_url' => '/fooddelivery/php/views/order_confirmation.php?order_id=' . $orderId
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log("Error creating order: " . $e->getMessage());
            return jsonResponse(['error' => 'An error occurred while processing your order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get order details
     */
    public function getOrderDetails($orderId) {
        startSession();

        if (!isLoggedIn()) {
            return jsonResponse(['error' => 'You must be logged in to view order details'], 401);
        }

        try {
            $userId = $_SESSION['user_id'];
            $orderDetails = $this->orderModel->getOrderDetails($orderId);

            if (!$orderDetails) {
                return jsonResponse(['error' => 'Order not found'], 404);
            }

            // Verify the order belongs to the current user
            if ($orderDetails['user_id'] != $userId) {
                return jsonResponse(['error' => 'Unauthorized access to order details'], 403);
            }

            // If this is an AJAX request, return JSON
            if (isset($_GET['ajax'])) {
                return jsonResponse(['success' => true, 'order' => $orderDetails]);
            }

            // For direct access, return the order details array
            return $orderDetails;

        } catch (Exception $e) {
            error_log("Error getting order details: " . $e->getMessage());
            if (isset($_GET['ajax'])) {
                return jsonResponse(['error' => 'An error occurred while retrieving order details'], 500);
            }
            throw $e;
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus() {
        startSession();
        if (!isAdmin()) {
            return jsonResponse(['success' => false, 'error' => 'Unauthorized access'], 403);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
        }

        $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : '';

        if (!$orderId) {
            return jsonResponse(['success' => false, 'error' => 'Invalid order ID']);
        }

        // Validate status
        if (!in_array($status, self::VALID_ORDER_STATUSES)) {
            return jsonResponse(['success' => false, 'error' => 'Invalid order status']);
        }

        try {
            if ($this->orderModel->updateStatus($orderId, $status)) {
                return jsonResponse([
                    'success' => true,
                    'message' => 'Order status updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update order status');
            }
        } catch (Exception $e) {
            error_log("Error updating order status: " . $e->getMessage());
            return jsonResponse([
                'success' => false,
                'error' => 'Failed to update order status'
            ], 500);
        }
    }

    /**
     * Cancel order
     */
    public function cancelOrder() {
        startSession();
        if (!isLoggedIn()) {
            jsonResponse(['error' => 'Please login to cancel order'], 401);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Invalid request method'], 405);
        }

        $orderId = (int)$_POST['order_id'];
        
        try {
            $order = $this->orderModel->getOrderById($orderId);

            // Verify order belongs to user
            if (!$order || $order['user_id'] != $_SESSION['user_id']) {
                jsonResponse(['error' => 'Invalid order'], 400);
            }

            // Only pending orders can be cancelled
            if ($order['status'] !== 'pending') {
                jsonResponse(['error' => 'Order cannot be cancelled'], 400);
            }

            if ($this->orderModel->updateStatus($orderId, 'cancelled')) {
                // If payment exists and is completed, process refund
                $payment = $this->paymentModel->getPaymentByOrderId($orderId);
                if ($payment && $payment['status'] === 'completed') {
                    $this->paymentModel->processRefund($payment['payment_id'], 'Order cancelled by user');
                }

                jsonResponse([
                    'success' => true,
                    'message' => 'Order cancelled successfully'
                ]);
            } else {
                throw new Exception('Failed to cancel order');
            }
        } catch (Exception $e) {
            error_log("Error cancelling order: " . $e->getMessage());
            jsonResponse([
                'success' => false,
                'error' => 'Failed to cancel order'
            ], 500);
        }
    }

    /**
     * Get user orders
     */
    public function getUserOrders() {
        startSession();
        if (!isLoggedIn()) {
            redirect(SITE_URL . '/views/login.php');
            exit();
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : null;

        try {
            if ($status && !in_array($status, self::VALID_ORDER_STATUSES)) {
                throw new Exception('Invalid order status');
            }

            $orders = $this->orderModel->getUserOrders($_SESSION['user_id'], $page, $limit, $status);
            $totalOrders = $this->orderModel->getTotalUserOrders($_SESSION['user_id'], $status);
            $totalPages = ceil($totalOrders / $limit);

            if (isset($_GET['ajax'])) {
                jsonResponse([
                    'success' => true,
                    'orders' => $orders,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_orders' => $totalOrders
                    ]
                ]);
            } else {
                require '../views/orders.php';
            }
        } catch (Exception $e) {
            error_log("Error getting user orders: " . $e->getMessage());
            if (isset($_GET['ajax'])) {
                jsonResponse([
                    'success' => false,
                    'error' => 'Failed to retrieve orders'
                ], 500);
            } else {
                $error = 'Failed to retrieve orders';
                require '../views/orders.php';
            }
        }
    }
}

// Handle incoming requests
try {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    if (!isset($_GET['action'])) {
        // If no action is specified, this might be a direct include from a view
        return;
    }

    $controller = new OrderController($pdo);
    $action = $_GET['action'];

    // Set JSON content type for AJAX requests
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
    }

    // Check admin access for protected actions
    if (in_array($action, ['updateStatus', 'updatePaymentStatus']) && !isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    switch ($action) {
        case 'createOrder':
            echo $controller->createOrder();
            break;
        case 'updateStatus':
            echo $controller->updateOrderStatus();
            break;
        case 'getOrderDetails':
            if (!isset($_GET['order_id'])) {
                throw new Exception("No order ID specified");
            }
            $result = $controller->getOrderDetails($_GET['order_id']);
            if (isset($_GET['ajax'])) {
                echo $result;
            }
            break;
        case 'updatePaymentStatus':
            if (!isAdmin()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
                exit;
            }

            $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
            $status = isset($_POST['status']) ? $_POST['status'] : '';

            if (!$orderId || !in_array($status, ['pending', 'paid', 'failed'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }

            try {
                $order = new Order($pdo);
                $result = $order->updatePaymentStatus($orderId, $status);
                header('Content-Type: application/json');
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Payment status updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
                }
            } catch (Exception $e) {
                error_log("Error updating payment status: " . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'An error occurred while updating payment status']);
            }
            exit;
            break;
        default:
            throw new Exception("Invalid action: " . $action);
    }
} catch (Throwable $e) {
    // Log the error with full details
    error_log("Error in order controller: " . $e->getMessage() . "\n" . 
              "Stack trace: " . $e->getTraceAsString() . "\n" . 
              "POST data: " . print_r($_POST, true));
    
    // Return error response
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'An error occurred while processing your request: ' . $e->getMessage()
        ]);
    } else {
        // For direct access, throw the error to be handled by the calling script
        throw $e;
    }
} 