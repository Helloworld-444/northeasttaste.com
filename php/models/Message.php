<?php
class Message {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createMessage($name, $email, $subject, $message) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO messages (name, email, subject, message)
                VALUES (:name, :email, :subject, :message)
            ');
            
            return $stmt->execute([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message
            ]);
        } catch (PDOException $e) {
            error_log("Error creating message: " . $e->getMessage());
            return false;
        }
    }

    public function getRecentMessages($limit = 10) {
        try {
            $stmt = $this->pdo->prepare('
                SELECT * FROM messages 
                ORDER BY created_at DESC 
                LIMIT :limit
            ');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting recent messages: " . $e->getMessage());
            return [];
        }
    }

    public function markAsRead($messageId) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE messages 
                SET is_read = 1 
                WHERE message_id = :message_id
            ');
            
            return $stmt->execute(['message_id' => $messageId]);
        } catch (PDOException $e) {
            error_log("Error marking message as read: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadCount() {
        try {
            $stmt = $this->pdo->query('
                SELECT COUNT(*) FROM messages 
                WHERE is_read = 0
            ');
            
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
} 