<?php
/**
 * User Model
 * Handles all user-related database operations
 */
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new user
     * @param string $email User email
     * @param string $password User password
     * @param string $firstName User first name
     * @param string $lastName User last name
     * @param string|null $phone User phone number
     * @param string $address User address
     * @return bool Success status
     */
    public function createUser($email, $password, $firstName, $lastName, $phone, $address) {
        try {
            // Check if email already exists
            if ($this->getUserByEmail($email)) {
                error_log("Attempt to create user with existing email: $email");
                return false;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare('
                INSERT INTO users (email, password_hash, first_name, last_name, phone, address, is_active)
                VALUES (:email, :password_hash, :first_name, :last_name, :phone, :address, 1)
            ');

            return $stmt->execute([
                'email' => $email,
                'password_hash' => $passwordHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'address' => $address
            ]);
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Authenticate user
     * @param string $email User email
     * @param string $password User password
     * @return array|false User data if authenticated, false otherwise
     */
    public function authenticate($email, $password) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error authenticating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by ID
     * @param int $userId User ID
     * @return array|false User data or false if not found
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by email
     * @param string $email User email
     * @return array|false User data or false if not found
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error getting user by email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user details
     * @param int $userId User ID
     * @param array $data Updated user data
     * @return bool Success status
     */
    public function updateUser($userId, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'phone', 'address'];
            $updates = [];
            $params = ['user_id' => $userId];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "`$field` = :$field";
                    $params[$field] = $value;
                }
            }

            if (empty($updates)) {
                return false;
            }

            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE user_id = :user_id';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user password
     * @param int $userId User ID
     * @param string $newPassword New password
     * @return bool Success status
     */
    public function updatePassword($userId, $newPassword) {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare('
                UPDATE users 
                SET password_hash = :password_hash 
                WHERE user_id = :user_id
            ');
            return $stmt->execute([
                'password_hash' => $passwordHash,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deactivate user account
     * @param int $userId User ID
     * @return bool Success status
     */
    public function deactivateUser($userId) {
        try {
            $stmt = $this->pdo->prepare('UPDATE users SET is_active = 0 WHERE user_id = :user_id');
            return $stmt->execute(['user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Error deactivating user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is admin
     * @param int $userId User ID
     * @return bool True if user is admin
     */
    public function isAdmin($userId) {
        try {
            $stmt = $this->pdo->prepare('SELECT is_admin FROM users WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch();
            return $result && $result['is_admin'] == 1;
        } catch (PDOException $e) {
            error_log("Error checking admin status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users with pagination
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Array of users
     */
    public function getAllUsers($page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            $stmt = $this->pdo->prepare('
                SELECT user_id, email, first_name, last_name, phone, address, is_admin, is_active 
                FROM users 
                ORDER BY user_id DESC 
                LIMIT :limit OFFSET :offset
            ');
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting all users: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get total number of users
     * @return int Total number of users
     */
    public function getTotalUsers() {
        try {
            return $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting total users: " . $e->getMessage());
            return 0;
        }
    }
} 