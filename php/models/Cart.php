<?php
/**
 * Cart Model
 * Handles all cart-related database operations
 */
class Cart {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Add item to cart
     * @param int $userId User ID
     * @param int $dishId Dish ID
     * @param int $quantity Quantity to add
     * @return bool Success status
     */
    public function addItem($userId, $dishId, $quantity = 1) {
        try {
            // Check if item already exists in cart
            $stmt = $this->pdo->prepare('SELECT * FROM cart_items WHERE user_id = ? AND dish_id = ?');
            $stmt->execute([$userId, $dishId]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingItem) {
                // Update quantity if item exists
                $newQuantity = $existingItem['quantity'] + $quantity;
                $stmt = $this->pdo->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND dish_id = ?');
                return $stmt->execute([$newQuantity, $userId, $dishId]);
            } else {
                // Insert new item if it doesn't exist
                $stmt = $this->pdo->prepare('INSERT INTO cart_items (user_id, dish_id, quantity) VALUES (?, ?, ?)');
                return $stmt->execute([$userId, $dishId, $quantity]);
            }
        } catch (PDOException $e) {
            error_log("Error adding item to cart: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update item quantity in cart
     * @param int $userId User ID
     * @param int $dishId Dish ID
     * @param int $quantity New quantity
     * @return bool Success status
     */
    public function updateQuantity($userId, $dishId, $quantity) {
        try {
            $stmt = $this->pdo->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND dish_id = ?');
            return $stmt->execute([$quantity, $userId, $dishId]);
        } catch (PDOException $e) {
            error_log("Error updating cart quantity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove item from cart
     * @param int $userId User ID
     * @param int $dishId Dish ID
     * @return bool Success status
     */
    public function removeItem($userId, $dishId) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE user_id = ? AND dish_id = ?');
            return $stmt->execute([$userId, $dishId]);
        } catch (PDOException $e) {
            error_log("Error removing item from cart: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all items in user's cart with dish details
     * @param int $userId User ID
     * @return array Array of cart items with dish details
     */
    public function getCartItems($userId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT c.*, d.name, d.price, d.image_blob, d.image_type 
                FROM cart_items c 
                JOIN dishes d ON c.dish_id = d.dish_id 
                WHERE c.user_id = ?
            ');
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cart items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear all items from user's cart
     * @param int $userId User ID
     * @return bool Success status
     */
    public function clearCart($userId) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate total price of items in cart
     * @param int $userId User ID
     * @return float Total price
     */
    public function getCartTotal($userId) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT SUM(c.quantity * d.price) as total 
                FROM cart_items c 
                JOIN dishes d ON c.dish_id = d.dish_id 
                WHERE c.user_id = ?
            ');
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error calculating cart total: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get number of unique items in cart
     * @param int $userId User ID
     * @return int Number of unique items
     */
    public function getCartCount($userId) {
        try {
            $stmt = $this->pdo->prepare('SELECT SUM(quantity) as count FROM cart_items WHERE user_id = ?');
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error getting cart count: " . $e->getMessage());
            return 0;
        }
    }
} 