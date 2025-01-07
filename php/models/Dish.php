<?php
/**
 * Dish Model
 * Handles all dish-related database operations
 */
class Dish {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new dish
     * @param string $name Dish name
     * @param string $description Dish description
     * @param float $price Dish price
     * @param string $category Dish category
     * @param string|null $imageBlob Image binary data
     * @param string|null $imageType Image MIME type
     * @return bool Success status
     */
    public function createDish($name, $description, $price, $category, $imageBlob = null, $imageType = null) {
        // Validate inputs
        if (empty($name) || empty($description) || !is_numeric($price) || $price <= 0) {
            error_log("Invalid dish data provided: name={$name}, price={$price}");
            return false;
        }

        if (!in_array($category, $this->getDishCategories())) {
            error_log("Invalid category provided: " . $category);
            return false;
        }

        try {
            $sql = 'INSERT INTO dishes (name, description, price, category, image_blob, image_type) VALUES (?, ?, ?, ?, ?, ?)';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$name, $description, $price, $category, $imageBlob, $imageType]);
        } catch (PDOException $e) {
            error_log("Error creating dish: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update dish details
     * @param int $dishId Dish ID
     * @param string $name Dish name
     * @param string $description Dish description
     * @param float $price Dish price
     * @param string $category Dish category
     * @param string|null $imageBlob Image binary data
     * @param string|null $imageType Image MIME type
     * @return bool Success status
     */
    public function updateDish($dishId, $name, $description, $price, $category, $imageBlob = null, $imageType = null) {
        // Validate inputs
        if (!is_numeric($dishId) || $dishId <= 0 || empty($name) || empty($description) || !is_numeric($price) || $price <= 0) {
            error_log("Invalid dish update data provided: id={$dishId}, name={$name}, price={$price}");
            return false;
        }

        if (!in_array($category, $this->getDishCategories())) {
            error_log("Invalid category provided for update: " . $category);
            return false;
        }

        try {
            if ($imageBlob !== null && $imageType !== null) {
                $sql = 'UPDATE dishes SET name = ?, description = ?, price = ?, category = ?, image_blob = ?, image_type = ? WHERE dish_id = ?';
                $params = [$name, $description, $price, $category, $imageBlob, $imageType, $dishId];
            } else {
                $sql = 'UPDATE dishes SET name = ?, description = ?, price = ?, category = ? WHERE dish_id = ?';
                $params = [$name, $description, $price, $category, $dishId];
            }
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating dish: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get dish by ID
     * @param int $dishId Dish ID
     * @return array|false Dish data or false if not found
     */
    public function getDishById($dishId) {
        if (!is_numeric($dishId) || $dishId <= 0) {
            error_log("Invalid dish ID provided: " . $dishId);
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM dishes WHERE dish_id = ?');
            $stmt->execute([$dishId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting dish by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all dishes with optional pagination and category filter
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param string|null $category Category filter
     * @return array Array of dishes
     */
    public function getAllDishes($page = 1, $perPage = 12, $category = null) {
        try {
            $offset = ($page - 1) * $perPage;
            $params = [];
            
            $sql = 'SELECT * FROM dishes WHERE 1=1';
            
            if ($category) {
                $sql .= ' AND category = ?';
                $params[] = $category;
            }
            
            $sql .= ' ORDER BY name ASC LIMIT ? OFFSET ?';
            $params[] = $perPage;
            $params[] = $offset;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting dishes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search dishes by name or description
     * @param string $query Search query
     * @return array Array of matching dishes
     */
    public function searchDishes($query) {
        if (empty($query)) {
            return [];
        }
        
        try {
            $sql = 'SELECT * FROM dishes WHERE name LIKE ? OR description LIKE ? ORDER BY name ASC';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['%' . $query . '%', '%' . $query . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching dishes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Toggle dish availability
     * @param int $dishId Dish ID
     * @return bool Success status
     */
    public function toggleAvailability($dishId) {
        if (!is_numeric($dishId) || $dishId <= 0) {
            error_log("Invalid dish ID provided for toggle: " . $dishId);
            return false;
        }

        try {
            $sql = 'UPDATE dishes SET available = NOT available WHERE dish_id = ?';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$dishId]);
        } catch (PDOException $e) {
            error_log("Error toggling dish availability: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all available dish categories
     * @return array Array of categories
     */
    public function getDishCategories() {
        return [
            'Manipuri',
            'Assamese',
            'NagaLand',
            'Mizoram',
            'Meghalaya',
            'Tripura',
            'Sikkim',
            'Arunachal Pradesh'
        ];
    }

    /**
     * Get total number of dishes
     * @param string|null $category Category filter
     * @return int Total number of dishes
     */
    public function getTotalDishes($category = null) {
        try {
            $sql = 'SELECT COUNT(*) FROM dishes WHERE 1=1';
            $params = [];
            
            if ($category) {
                $sql .= ' AND category = ?';
                $params[] = $category;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total dishes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all available dishes without pagination (for dashboard use)
     * @return array Array of all available dishes
     */
    public function getAllAvailableDishes() {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM dishes 
                WHERE available = 1 
                ORDER BY name ASC
            ');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting all available dishes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get featured dishes
     * @param int $limit Number of dishes to return
     * @return array Array of featured dishes
     */
    public function getFeaturedDishes($limit = 6) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM dishes 
                WHERE available = 1 
                ORDER BY RAND() 
                LIMIT :limit
            ');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting featured dishes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update dish image
     * @param int $dishId Dish ID
     * @param string $imageBlob Image binary data
     * @param string $imageType Image MIME type
     * @return bool Success status
     */
    public function updateDishImage($dishId, $imageBlob, $imageType) {
        if (!is_numeric($dishId) || $dishId <= 0 || empty($imageBlob) || empty($imageType)) {
            error_log("Invalid image update data provided: id={$dishId}");
            return false;
        }

        try {
            $sql = 'UPDATE dishes SET image_blob = ?, image_type = ? WHERE dish_id = ?';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$imageBlob, $imageType, $dishId]);
        } catch (PDOException $e) {
            error_log("Error updating dish image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get related dishes from the same category
     * @param int $dishId Current dish ID to exclude
     * @param string $category Category to match
     * @param int $limit Number of dishes to return
     * @return array Array of related dishes
     */
    public function getRelatedDishes($dishId, $category, $limit = 3) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM dishes 
                WHERE dish_id != :dish_id 
                AND category = :category 
                AND available = 1 
                ORDER BY RAND() 
                LIMIT :limit
            ');
            
            $stmt->bindValue(':dish_id', $dishId, PDO::PARAM_INT);
            $stmt->bindValue(':category', $category, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting related dishes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete a dish
     * @param int $dishId Dish ID to delete
     * @return bool Success status
     */
    public function deleteDish($dishId) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM dishes WHERE dish_id = ?');
            return $stmt->execute([$dishId]);
        } catch (PDOException $e) {
            error_log("Error deleting dish: " . $e->getMessage());
            return false;
        }
    }
}