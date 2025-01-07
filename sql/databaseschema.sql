-- -----------------------------------------------------
-- Database `fooddelivery`
-- -----------------------------------------------------
CREATE DATABASE IF NOT EXISTS `fooddelivery` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fooddelivery`;

-- -----------------------------------------------------
-- Table `users`
-- -----------------------------------------------------
CREATE TABLE `users` (
  `user_id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `phone` VARCHAR(20),
  `address` TEXT NOT NULL,
  `is_admin` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `dishes`
-- -----------------------------------------------------
CREATE TABLE `dishes` (
  `dish_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `category` ENUM('Manipuri','Assamese','NagaLand','Mizoram','Meghalaya','Tripura','Sikkim','Arunachal Pradesh') NOT NULL,
  `image_url` VARCHAR(255),
  `image_blob` MEDIUMBLOB,
  `image_type` VARCHAR(50),
  `available` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`dish_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `orders`
-- -----------------------------------------------------
CREATE TABLE `orders` (
  `order_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `status` ENUM('pending','confirmed','preparing','delivered','cancelled') DEFAULT 'pending',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `payment_status` ENUM('pending','paid','failed') DEFAULT 'pending',
  `delivery_address` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `order_items`
-- -----------------------------------------------------
CREATE TABLE `order_items` (
  `order_item_id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `dish_id` INT NOT NULL,
  `quantity` INT DEFAULT 1,
  `price_per_unit` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `dish_id` (`dish_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) 
    REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`dish_id`) 
    REFERENCES `dishes` (`dish_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `cart_items`
-- -----------------------------------------------------
CREATE TABLE `cart_items` (
  `cart_item_id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `dish_id` INT NOT NULL,
  `quantity` INT DEFAULT 1,
  `instructions` TEXT NULL,
  PRIMARY KEY (`cart_item_id`),
  UNIQUE KEY `user_dish` (`user_id`, `dish_id`),
  KEY `dish_id` (`dish_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`dish_id`) 
    REFERENCES `dishes` (`dish_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `payments`
-- -----------------------------------------------------
CREATE TABLE `payments` (
  `payment_id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `method` ENUM('cash','card','upi') NOT NULL,
  `status` ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
  `transaction_id` VARCHAR(100),
  PRIMARY KEY (`payment_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) 
    REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Table `messages`
-- -----------------------------------------------------
CREATE TABLE `messages` (
  `message_id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Seed Data
-- -----------------------------------------------------

-- Users
INSERT INTO `users` (`email`, `password_hash`, `first_name`, `last_name`, `phone`, `address`, `is_admin`, `is_active`) VALUES
('admin@northeast.com', '$2y$10$8K1p/a4K4N6XO4oD4Nj8a.8A4MgR0DUQF.7mGAeDR', 'Admin', 'User', '9876543210', 'Imphal, Manipur', 1, 1),
('john@example.com', '$2y$10$8K1p/a4K4N6XO4oD4Nj8a.8A4MgR0DUQF.7mGAeDR', 'John', 'Doe', '1234567890', 'Guwahati, Assam', 0, 1),
('mary@example.com', '$2y$10$8K1p/a4K4N6XO4oD4Nj8a.8A4MgR0DUQF.7mGAeDR', 'Mary', 'Smith', '2345678901', 'Shillong, Meghalaya', 0, 1);

-- Dishes
INSERT INTO `dishes` (`name`, `description`, `price`, `category`, `image_url`, `available`) VALUES
('Eromba', 'Traditional Manipuri dish made with boiled vegetables, fermented fish, and chili', 180.00, 'Manipuri', 'eromba.jpg', 1),
('Kangshoi', 'A light and healthy Manipuri stew made with seasonal vegetables', 150.00, 'Manipuri', 'kangshoi.jpg', 1),
('Chamthong', 'Aromatic Manipuri vegetable stew', 160.00, 'Manipuri', 'chamthong.jpg', 1),
('Masor Tenga', 'Sour fish curry, an Assamese delicacy', 220.00, 'Assamese', 'masor_tenga.jpg', 1),
('Khar', 'Traditional Assamese starter made with raw papaya', 120.00, 'Assamese', 'khar.jpg', 1);

-- Orders
INSERT INTO `orders` (`user_id`, `status`, `total_amount`, `payment_status`, `delivery_address`) VALUES
(2, 'delivered', 520.00, 'paid', 'Guwahati, Assam'),
(3, 'preparing', 360.00, 'paid', 'Shillong, Meghalaya'),
(2, 'pending', 400.00, 'pending', 'Guwahati, Assam');

-- Order Items
INSERT INTO `order_items` (`order_id`, `dish_id`, `quantity`, `price_per_unit`) VALUES
(1, 1, 2, 180.00),
(1, 4, 1, 160.00),
(2, 2, 2, 150.00),
(3, 3, 2, 160.00);

-- Cart Items
INSERT INTO `cart_items` (`user_id`, `dish_id`, `quantity`) VALUES
(2, 3, 1),
(2, 5, 2),
(3, 1, 1);

-- Payments
INSERT INTO `payments` (`order_id`, `amount`, `method`, `status`, `transaction_id`) VALUES
(1, 520.00, 'card', 'completed', 'TXN123456'),
(2, 360.00, 'upi', 'completed', 'UPI789012'),
(3, 400.00, 'cash', 'pending', NULL);

