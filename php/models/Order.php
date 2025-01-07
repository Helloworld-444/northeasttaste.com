<?php
/**
 * Order Model
 * Handles all order-related database operations
 */
class Order {
    private $pdo;
    private $lastError;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createOrder($orderData) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO orders (user_id, delivery_address, total_amount, status, payment_status)
                VALUES (:user_id, :delivery_address, :total_amount, :status, :payment_status)
            ');

            $result = $stmt->execute([
                'user_id' => $orderData['user_id'],
                'delivery_address' => $orderData['delivery_address'],
                'total_amount' => $orderData['total_amount'],
                'status' => $orderData['status'],
                'payment_status' => $orderData['payment_status']
            ]);

            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }

    public function addOrderItem($orderId, $dishId, $quantity, $price) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO order_items (order_id, dish_id, quantity, price_per_unit)
                VALUES (:order_id, :dish_id, :quantity, :price_per_unit)
            ');

            return $stmt->execute([
                'order_id' => $orderId,
                'dish_id' => $dishId,
                'quantity' => $quantity,
                'price_per_unit' => $price
            ]);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error adding order item: " . $e->getMessage());
            return false;
        }
    }

    public function getOrderDetails($orderId) {
        try {
            // Get order details with user information
            $stmt = $this->pdo->prepare('
                SELECT o.*, 
                       u.first_name, u.last_name, u.email,
                       COALESCE(p.method, "N/A") as payment_method,
                       COALESCE(p.status, "pending") as payment_status
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.user_id
                LEFT JOIN payments p ON o.order_id = p.order_id 
                WHERE o.order_id = :order_id
            ');
            $stmt->execute(['order_id' => $orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return false;
            }

            // Get order items with dish information
            $stmt = $this->pdo->prepare('
                SELECT oi.*, d.name, d.description, d.category 
                FROM order_items oi 
                JOIN dishes d ON oi.dish_id = d.dish_id 
                WHERE oi.order_id = :order_id
            ');
            $stmt->execute(['order_id' => $orderId]);
            $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Set default values for missing user information
            $order['first_name'] = $order['first_name'] ?? 'Guest';
            $order['last_name'] = $order['last_name'] ?? '';
            $order['email'] = $order['email'] ?? 'N/A';

            return $order;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error getting order details: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get orders for a specific user with pagination and status filter
     * @param int $userId User ID
     * @param int $page Current page number
     * @param int $limit Items per page
     * @param string|null $status Order status filter
     * @return array|false Array of orders or false on failure
     */
    public function getUserOrders($userId, $page = 1, $limit = 10, $status = null) {
        try {
            $offset = ($page - 1) * $limit;
            $params = ['user_id' => $userId];
            
            $sql = 'SELECT o.*, u.first_name, u.last_name, u.email 
                   FROM orders o 
                   LEFT JOIN users u ON o.user_id = u.user_id 
                   WHERE o.user_id = :user_id';
            
            if ($status) {
                $sql .= ' AND o.status = :status';
                $params['status'] = $status;
            }
            
            $sql .= ' ORDER BY o.order_id DESC LIMIT :limit OFFSET :offset';
            
            $stmt = $this->pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($orders) . " orders");
            
            return $orders;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error getting user orders: " . $e->getMessage());
            return false;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Get the number of orders for a specific user
     * @param int $userId User ID
     * @return int Number of orders
     */
    public function getOrderCountByUser($userId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT COUNT(*) as count 
                FROM orders 
                WHERE user_id = :user_id
            ');
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting order count: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get orders with pagination
     */
    public function getOrders($limit = 10, $offset = 0) {
        $stmt = $this->pdo->prepare('
            SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.user_id 
            ORDER BY o.order_id DESC 
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total number of orders
     */
    public function getTotalOrders() {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM orders');
        return $stmt->fetchColumn();
    }

    /**
     * Update order status
     * @param int $orderId Order ID
     * @param string $status New status
     * @return bool True on success, false on failure
     */
    public function updateStatus($orderId, $status) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                UPDATE orders 
                SET status = :status 
                WHERE order_id = :order_id
            ');
            
            $result = $stmt->execute([
                'order_id' => $orderId,
                'status' => $status
            ]);

            // If order is being marked as delivered, also update payment status to paid
            if ($result && $status === 'delivered') {
                $stmt = $this->pdo->prepare('
                    UPDATE orders 
                    SET payment_status = :payment_status 
                    WHERE order_id = :order_id AND payment_status = :current_status
                ');
                
                $stmt->execute([
                    'order_id' => $orderId,
                    'payment_status' => 'paid',
                    'current_status' => 'pending'
                ]);
            }

            $this->pdo->commit();
            return $result;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->lastError = $e->getMessage();
            error_log("Error updating order status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order by ID
     * @param int $orderId Order ID
     * @return array|false Order data or false if not found
     */
    public function getOrderById($orderId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT o.*, u.first_name, u.last_name, u.email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.user_id 
                WHERE o.order_id = :order_id
            ');
            $stmt->execute(['order_id' => $orderId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error getting order by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total number of orders for a user with optional status filter
     * @param int $userId User ID
     * @param string|null $status Order status filter
     * @return int Total number of orders
     */
    public function getTotalUserOrders($userId, $status = null) {
        try {
            $sql = 'SELECT COUNT(*) FROM orders WHERE user_id = :user_id';
            $params = ['user_id' => $userId];
            
            if ($status) {
                $sql .= ' AND status = :status';
                $params['status'] = $status;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error getting total user orders: " . $e->getMessage());
            return 0;
        }
    }

    public function getOrderItems($orderId) {
        $stmt = $this->pdo->prepare('
            SELECT oi.*, d.name 
            FROM order_items oi
            JOIN dishes d ON oi.dish_id = d.dish_id
            WHERE oi.order_id = :order_id
        ');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update payment status
     * @param int $orderId Order ID
     * @param string $status New payment status
     * @return bool True on success, false on failure
     */
    public function updatePaymentStatus($orderId, $status) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE orders 
                SET payment_status = :payment_status 
                WHERE order_id = :order_id
            ');
            
            $result = $stmt->execute([
                'order_id' => $orderId,
                'payment_status' => $status
            ]);

            if (!$result) {
                $this->lastError = "Failed to update payment status";
                error_log("Failed to update payment status for order $orderId");
                return false;
            }

            return true;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("Error updating payment status: " . $e->getMessage());
            return false;
        }
    }
} 