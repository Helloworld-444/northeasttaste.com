<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/db.php';
require_once dirname(dirname(__DIR__)) . '/models/Order.php';
require_once dirname(dirname(__DIR__)) . '/helpers.php';

class AdminOrderController {
    private $orderModel;
    private const VALID_ORDER_STATUSES = ['pending', 'confirmed', 'preparing', 'delivered', 'cancelled'];
    private const VALID_PAYMENT_STATUSES = ['pending', 'paid', 'failed'];

    public function __construct() {
        global $pdo;
        $this->orderModel = new Order($pdo);
    }

    /**
     * Update order status and handle payment status
     */
    public function updateStatus() {
        // Ensure clean output buffer
        while (ob_get_level()) ob_end_clean();
        
        // Set CORS headers
        header('Access-Control-Allow-Origin: ' . SITE_URL);
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Content-Type: application/json');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        try {
            startSession();
            if (!isAdmin()) {
                throw new Exception('Unauthorized access');
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            // Get JSON input if content type is application/json
            $contentType = isset($_SERVER['CONTENT_TYPE']) ? trim($_SERVER['CONTENT_TYPE']) : '';
            if (stripos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON data');
                }
                $_POST = $input;
            }

            // Log the incoming request data
            error_log("Received order update request - Data: " . print_r($_POST, true));

            // Validate required fields
            if (!isset($_POST['order_id'])) {
                throw new Exception('Order ID is required');
            }
            if (!isset($_POST['status'])) {
                throw new Exception('Status is required');
            }

            $orderId = (int)$_POST['order_id'];
            $status = trim($_POST['status']);

            error_log("Processing order update - ID: $orderId, Status: $status");

            if ($orderId <= 0) {
                throw new Exception('Invalid order ID');
            }

            if (!in_array($status, self::VALID_ORDER_STATUSES)) {
                throw new Exception('Invalid order status');
            }

            // Get current order details
            $currentOrder = $this->orderModel->getOrderById($orderId);
            if (!$currentOrder) {
                throw new Exception('Order not found');
            }

            // Begin transaction
            $this->orderModel->beginTransaction();

            try {
                // Update order status
                $result = $this->orderModel->updateStatus($orderId, $status);
                if (!$result) {
                    throw new Exception('Failed to update order status: ' . $this->orderModel->getLastError());
                }

                // Update payment status if order is delivered
                if ($status === 'delivered' && $currentOrder['payment_status'] === 'pending') {
                    $paymentResult = $this->orderModel->updatePaymentStatus($orderId, 'paid');
                    if (!$paymentResult) {
                        throw new Exception('Failed to update payment status');
                    }
                }

                // Commit transaction
                $this->orderModel->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Order status updated successfully',
                    'order' => [
                        'id' => $orderId,
                        'status' => $status,
                        'payment_status' => $status === 'delivered' ? 'paid' : $currentOrder['payment_status']
                    ]
                ]);
            } catch (Exception $e) {
                $this->orderModel->rollback();
                throw $e;
            }
        } catch (Exception $e) {
            error_log("Error in order controller: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Get order details
     */
    public function getOrder() {
        header('Content-Type: application/json');
        
        try {
            startSession();
            if (!isAdmin()) {
                throw new Exception('Unauthorized access');
            }

            if (!isset($_GET['id'])) {
                throw new Exception('Order ID is required');
            }

            $orderId = (int)$_GET['id'];
            if ($orderId <= 0) {
                throw new Exception('Invalid order ID');
            }

            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                throw new Exception('Order not found');
            }

            echo json_encode([
                'success' => true,
                'order' => $order
            ]);
        } catch (Exception $e) {
            error_log("Error getting order details: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Handle the request
if (isset($_REQUEST['action'])) {
    $controller = new AdminOrderController();
    $action = $_REQUEST['action'];

    switch ($action) {
        case 'update_status':
            $controller->updateStatus();
            break;
        case 'get_order':
            $controller->getOrder();
            break;
        default:
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
    }
}