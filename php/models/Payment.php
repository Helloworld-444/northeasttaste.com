<?php
/**
 * Payment Model
 * Handles all payment-related database operations
 */
class Payment {
    private $pdo;
    private $lastError;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->lastError = null;
    }

    public function getLastError() {
        return $this->lastError;
    }

    protected function setLastError($error) {
        $this->lastError = $error;
        error_log("Payment model error: " . print_r($error, true));
    }

    /**
     * Create a new payment record
     * @param array $paymentData Payment data including order_id, amount, method, status
     * @return bool Success status
     */
    public function createPayment($paymentData) {
        try {
            error_log("Creating payment with data: " . print_r($paymentData, true));
            
            $stmt = $this->pdo->prepare('
                INSERT INTO payments (order_id, amount, method, status)
                VALUES (:order_id, :amount, :method, :status)
            ');

            $result = $stmt->execute([
                'order_id' => $paymentData['order_id'],
                'amount' => $paymentData['amount'],
                'method' => $paymentData['method'],
                'status' => $paymentData['status']
            ]);

            if (!$result) {
                $this->setLastError([
                    'message' => 'Failed to execute payment creation query',
                    'sqlError' => $stmt->errorInfo()
                ]);
                return false;
            }

            return true;
        } catch (PDOException $e) {
            $this->setLastError([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sqlState' => $e->errorInfo[0] ?? null
            ]);
            return false;
        }
    }

    /**
     * Get payment by order ID
     * @param int $orderId Order ID
     * @return array|false Payment data or false on failure
     */
    public function getPaymentByOrderId($orderId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM payments 
                WHERE order_id = :order_id 
                ORDER BY payment_id DESC 
                LIMIT 1
            ');

            $stmt->execute(['order_id' => $orderId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->setLastError([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sqlState' => $e->errorInfo[0] ?? null
            ]);
            return false;
        }
    }

    /**
     * Update payment status
     * @param int $paymentId Payment ID
     * @param string $status New status
     * @return bool Success status
     */
    public function updatePaymentStatus($paymentId, $status) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE payments 
                SET status = :status 
                WHERE payment_id = :payment_id
            ');

            return $stmt->execute([
                'payment_id' => $paymentId,
                'status' => $status
            ]);
        } catch (PDOException $e) {
            $this->setLastError([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sqlState' => $e->errorInfo[0] ?? null
            ]);
            return false;
        }
    }

    /**
     * Process refund for a payment
     * @param int $paymentId Payment ID
     * @param string $reason Reason for refund
     * @return bool Success status
     */
    public function processRefund($paymentId, $reason = '') {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE payments 
                SET status = :status, 
                    refund_reason = :reason 
                WHERE payment_id = :payment_id 
                AND status = :current_status
            ');

            return $stmt->execute([
                'payment_id' => $paymentId,
                'status' => 'refunded',
                'reason' => $reason,
                'current_status' => 'completed'
            ]);
        } catch (PDOException $e) {
            $this->setLastError([
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sqlState' => $e->errorInfo[0] ?? null
            ]);
            return false;
        }
    }
} 