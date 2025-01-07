<?php
class Category {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all categories with dish counts
     * @return array Array of categories
     */
    public function getAllCategories() {
        try {
            $stmt = $this->pdo->query('
                SELECT category, COUNT(*) as dish_count 
                FROM dishes 
                WHERE available = TRUE 
                GROUP BY category 
                ORDER BY category ASC
            ');
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform the results to match the expected format
            return array_map(function($row) {
                return [
                    'category_id' => $row['category'],  // Using category name as ID since it's an ENUM
                    'name' => $row['category'],
                    'description' => $this->getCategoryDescription($row['category']),
                    'product_count' => $row['dish_count']
                ];
            }, $results);
        } catch (PDOException $e) {
            logError('Error getting categories: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get category description based on the category name
     * @param string $category Category name
     * @return string Category description
     */
    private function getCategoryDescription($category) {
        $descriptions = [
            'Manipuri' => 'Traditional dishes from Manipur cuisine',
            'Assamese' => 'Authentic Assamese delicacies',
            'NagaLand' => 'Flavorful dishes from Nagaland',
            'Mizoram' => 'Unique Mizo culinary experiences',
            'Meghalaya' => 'Distinctive Meghalayan cuisine',
            'Tripura' => 'Traditional Tripuri dishes',
            'Sikkim' => 'Authentic Sikkimese specialties',
            'Arunachal Pradesh' => 'Traditional dishes from Arunachal Pradesh'
        ];
        return $descriptions[$category] ?? 'Explore our delicious dishes';
    }

    /**
     * Get category by ID (in this case, by name since we're using ENUM)
     * @param string $category Category name
     * @return array|false Category data or false if not found
     */
    public function getCategoryById($category) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT category, COUNT(*) as dish_count 
                FROM dishes 
                WHERE category = :category AND available = TRUE 
                GROUP BY category
            ');
            $stmt->execute(['category' => $category]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'category_id' => $result['category'],
                    'name' => $result['category'],
                    'description' => $this->getCategoryDescription($result['category']),
                    'product_count' => $result['dish_count']
                ];
            }
            return false;
        } catch (PDOException $e) {
            logError('Error getting category by ID: ' . $e->getMessage());
            return false;
        }
    }
} 