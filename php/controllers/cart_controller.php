<?php
require_once dirname(dirname(__DIR__)) . '/php/config/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/models/Cart.php';
require_once dirname(__DIR__) . '/models/Dish.php';
require_once dirname(__DIR__) . '/helpers.php';

class CartController {
    private $cartModel;
    private $dishModel;
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->cartModel = new Cart($pdo);
        $this->dishModel = new Dish($pdo);
    }

    public function handleRequest() {
        startSession();
        
        $action = $_POST['action'] ?? ($_GET['action'] ?? null);

        switch ($action) {
            case 'add':
                $this->addToCart();
                break;
            case 'update':
                $this->updateCart();
                break;
            case 'remove':
                $this->removeFromCart();
                break;
            case 'get':
                $this->getCartItems();
                break;
            default:
                jsonResponse(['error' => 'Invalid action'], 400);
                break;
        }
    }

    private function addToCart() {
        try {
            if (!isset($_POST['dish_id'])) {
                throw new Exception('Missing dish ID');
            }

            $dishId = (int)$_POST['dish_id'];
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
            
            // Validate dish exists and is available
            $dish = $this->dishModel->getDishById($dishId);
            if (!$dish || !$dish['available']) {
                throw new Exception('Dish is not available');
            }

            // Add to cart
            if (isset($_SESSION['user_id'])) {
                // Logged in user - store in database
                $success = $this->cartModel->addItem($_SESSION['user_id'], $dishId, $quantity);
            } else {
                // Guest user - store in session
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['dish_id'] === $dishId) {
                        $item['quantity'] += $quantity;
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'dish_id' => $dishId,
                        'quantity' => $quantity
                    ];
                }
                
                $success = true;
            }

            if ($success) {
                // Check if it's an AJAX request
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    jsonResponse([
                        'success' => true,
                        'message' => 'Item added to cart successfully',
                        'cart_count' => $this->getCartCount()
                    ]);
                } else {
                    // Regular form submission - redirect to cart page
                    $_SESSION['flash_message'] = 'Item added to cart successfully';
                    $_SESSION['flash_type'] = 'success';
                    header('Location: ' . url('php/views/cart.php'));
                    exit();
                }
            } else {
                throw new Exception('Failed to add item to cart');
            }
        } catch (Exception $e) {
            error_log("Error adding to cart: " . $e->getMessage());
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                jsonResponse([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            } else {
                $_SESSION['flash_message'] = $e->getMessage();
                $_SESSION['flash_type'] = 'error';
                header('Location: ' . $_SERVER['HTTP_REFERER']);
                exit();
            }
        }
    }

    private function updateCart() {
        try {
            if (!isset($_POST['dish_id'], $_POST['quantity'])) {
                throw new Exception('Missing required fields');
            }

            $dishId = (int)$_POST['dish_id'];
            $quantity = (int)$_POST['quantity'];

            if ($quantity < 1) {
                throw new Exception('Invalid quantity');
            }

            if (isset($_SESSION['user_id'])) {
                $success = $this->cartModel->updateQuantity($_SESSION['user_id'], $dishId, $quantity);
            } else {
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['dish_id'] === $dishId) {
                        $item['quantity'] = $quantity;
                        break;
                    }
                }
                $success = true;
            }

            if ($success) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Cart updated',
                    'cartCount' => $this->getCartCount()
                ]);
            } else {
                throw new Exception('Failed to update cart');
            }
        } catch (Exception $e) {
            error_log("Error updating cart: " . $e->getMessage());
            jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function removeFromCart() {
        try {
            if (!isset($_POST['dish_id'])) {
                throw new Exception('Missing dish ID');
            }

            $dishId = (int)$_POST['dish_id'];

            if (isset($_SESSION['user_id'])) {
                $success = $this->cartModel->removeItem($_SESSION['user_id'], $dishId);
            } else {
                $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($dishId) {
                    return $item['dish_id'] !== $dishId;
                });
                $success = true;
            }

            if ($success) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'cartCount' => $this->getCartCount()
                ]);
            } else {
                throw new Exception('Failed to remove item from cart');
            }
        } catch (Exception $e) {
            error_log("Error removing from cart: " . $e->getMessage());
            jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function getCartItems() {
        try {
            if (isset($_SESSION['user_id'])) {
                $items = $this->cartModel->getCartItems($_SESSION['user_id']);
            } else {
                $items = $_SESSION['cart'] ?? [];
            }

            jsonResponse([
                'success' => true,
                'items' => $items,
                'cartCount' => $this->getCartCount()
            ]);
        } catch (Exception $e) {
            error_log("Error getting cart items: " . $e->getMessage());
            jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function getCartCount() {
        if (isset($_SESSION['user_id'])) {
            return $this->cartModel->getCartCount($_SESSION['user_id']);
        } else {
            return array_sum(array_column($_SESSION['cart'] ?? [], 'quantity'));
        }
    }
}

// Handle the request
$controller = new CartController();
$controller->handleRequest(); 