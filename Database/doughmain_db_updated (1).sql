-- phpMyAdmin SQL Dump
-- Updated Database with Image Support and Sale Feature
-- Version: 2.0
-- Date: December 02, 2025
--
-- INSTALLATION INSTRUCTIONS:
-- 1. Create a folder named 'uploads' in your project root
-- 2. Create a subfolder 'uploads/products' for product images
-- 3. Import this SQL file to update your database
-- 4. Replace the old controller and JavaScript files with the updated versions

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `doughmain_db`
--

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `sp_add_to_cart`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_to_cart` (IN `p_user_id` INT, IN `p_product_id` INT, IN `p_quantity` INT)
BEGIN
    DECLARE existing_quantity INT DEFAULT 0;
    
    -- Check if item already exists in cart
    SELECT quantity INTO existing_quantity 
    FROM cart 
    WHERE user_id = p_user_id AND product_id = p_product_id;
    
    IF existing_quantity > 0 THEN
        -- Update quantity
        UPDATE cart 
        SET quantity = quantity + p_quantity,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = p_user_id AND product_id = p_product_id;
    ELSE
        -- Insert new item
        INSERT INTO cart (user_id, product_id, quantity)
        VALUES (p_user_id, p_product_id, p_quantity);
    END IF;
END$$

DROP PROCEDURE IF EXISTS `sp_create_order`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_create_order` (IN `p_user_id` INT, IN `p_delivery_option` VARCHAR(20), IN `p_payment_method` VARCHAR(20), IN `p_delivery_address` TEXT, IN `p_contact_number` VARCHAR(20), OUT `p_order_id` INT)
BEGIN
    DECLARE v_subtotal DECIMAL(10,2) DEFAULT 0;
    DECLARE v_delivery_fee DECIMAL(10,2) DEFAULT 20.00;
    DECLARE v_total DECIMAL(10,2) DEFAULT 0;
    DECLARE v_order_number VARCHAR(50);
    
    -- Calculate subtotal from cart
    SELECT SUM(p.discounted_price * c.quantity) INTO v_subtotal
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = p_user_id;
    
    -- Calculate total
    SET v_total = v_subtotal + v_delivery_fee;
    
    -- Generate order number
    SET v_order_number = CONCAT('DM-', YEAR(CURDATE()), '-', LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Create order
    INSERT INTO orders (
        user_id, order_number, subtotal, delivery_fee, total_amount,
        delivery_option, payment_method, delivery_address, contact_number
    ) VALUES (
        p_user_id, v_order_number, v_subtotal, v_delivery_fee, v_total,
        p_delivery_option, p_payment_method, p_delivery_address, p_contact_number
    );
    
    SET p_order_id = LAST_INSERT_ID();
    
    -- Move cart items to order_items
    INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
    SELECT 
        p_order_id,
        c.product_id,
        c.quantity,
        p.discounted_price,
        (p.discounted_price * c.quantity)
    FROM cart c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.user_id = p_user_id;
    
    -- Update product stock
    UPDATE products p
    JOIN cart c ON p.product_id = c.product_id
    SET p.stock_quantity = p.stock_quantity - c.quantity
    WHERE c.user_id = p_user_id;
    
    -- Clear cart
    DELETE FROM cart WHERE user_id = p_user_id;
    
    -- Create notification
    INSERT INTO notifications (user_id, title, message, type)
    VALUES (
        p_user_id,
        'Order Placed Successfully',
        CONCAT('Your order ', v_order_number, ' has been placed successfully!'),
        'order'
    );
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `bakeries`
--

DROP TABLE IF EXISTS `bakeries`;
CREATE TABLE IF NOT EXISTS `bakeries` (
  `bakery_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `description` text,
  `opening_hours` varchar(100) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT '0.0',
  `logo_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`bakery_id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bakeries`
--

INSERT INTO `bakeries` (`bakery_id`, `name`, `address`, `contact_number`, `email`, `description`, `opening_hours`, `rating`, `logo_image`, `created_at`, `is_active`) VALUES
(1, 'Golden Bakery', '123 Main Street, Manila', '09123456789', 'golden@bakery.com', 'Traditional Filipino bakery since 1985', '6:00 AM - 8:00 PM', 4.5, NULL, '2025-11-17 17:04:11', 1),
(2, 'Sunrise Bakery', '456 Sunset Blvd, Quezon City', '09234567890', 'sunrise@bakery.com', 'Fresh bread daily, specializing in pastries', '5:30 AM - 9:00 PM', 4.7, NULL, '2025-11-17 17:04:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_cart_user_updated` (`user_id`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text,
  `display_order` int DEFAULT '0',
  `icon_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `display_order`, `icon_image`) VALUES
(1, 'All', 'All bread products', 1, NULL),
(2, 'Pandesal', 'Classic Filipino bread rolls', 2, NULL),
(3, 'Sweet', 'Sweet bread varieties', 3, NULL),
(4, 'Savory', 'Savory bread options', 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
CREATE TABLE IF NOT EXISTS `favorites` (
  `favorite_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`favorite_id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','promotion','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT '20.00',
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_option` enum('delivery','pickup') DEFAULT 'delivery',
  `payment_method` enum('cod','gcash','card') DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','preparing','ready','delivering','completed','cancelled') DEFAULT 'pending',
  `delivery_address` text,
  `contact_number` varchar(20) DEFAULT NULL,
  `notes` text,
  `ordered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_user` (`user_id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`order_status`),
  KEY `idx_ordered_at` (`ordered_at`),
  KEY `idx_orders_user_status` (`user_id`,`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `order_items`
--
DROP TRIGGER IF EXISTS `trg_calculate_order_item_subtotal`;
DELIMITER $$
CREATE TRIGGER `trg_calculate_order_item_subtotal` BEFORE INSERT ON `order_items` FOR EACH ROW BEGIN
    SET NEW.subtotal = NEW.unit_price * NEW.quantity;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `bakery_id` int NOT NULL,
  `category_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `original_price` decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) NOT NULL,
  `discount_percentage` int NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `stock_quantity` int DEFAULT '0',
  `expiry_date` date DEFAULT NULL,
  `is_on_sale` tinyint(1) DEFAULT '0',
  `sale_start_date` timestamp NULL DEFAULT NULL,
  `sale_end_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_available` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`product_id`),
  KEY `idx_bakery` (`bakery_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_available` (`is_available`),
  KEY `idx_on_sale` (`is_on_sale`),
  KEY `idx_products_available_bakery` (`is_available`,`bakery_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `bakery_id`, `category_id`, `name`, `description`, `original_price`, `discounted_price`, `discount_percentage`, `image_url`, `stock_quantity`, `expiry_date`, `is_on_sale`, `sale_start_date`, `sale_end_date`, `created_at`, `updated_at`, `is_available`) VALUES
(1, 1, 2, 'Pandesal', 'Classic Filipino breakfast bread', 50.00, 30.00, 40, 'uploads/products/pandesal.jpg', 50, '2025-12-05', 1, '2025-12-02 00:00:00', '2025-12-05 23:59:59', '2025-11-17 17:04:11', '2025-12-02 10:00:00', 1),
(2, 1, 2, 'Monay', 'Traditional Filipino bread roll', 40.00, 25.00, 37, 'uploads/products/monay.jpg', 30, '2025-12-05', 1, '2025-12-02 00:00:00', '2025-12-05 23:59:59', '2025-11-17 17:04:11', '2025-12-02 10:00:00', 1),
(3, 2, 3, 'Spanish Bread', 'Sweet bread with buttery filling', 60.00, 35.00, 42, 'uploads/products/spanish-bread.jpg', 40, '2025-12-05', 1, '2025-12-02 00:00:00', '2025-12-05 23:59:59', '2025-11-17 17:04:11', '2025-12-02 10:00:00', 1),
(4, 2, 3, 'Ensaymada', 'Buttery brioche with cheese topping', 70.00, 40.00, 43, 'uploads/products/ensaymada.jpg', 25, '2025-12-05', 1, '2025-12-02 00:00:00', '2025-12-05 23:59:59', '2025-11-17 17:04:11', '2025-12-02 10:00:00', 1),
(5, 1, 3, 'Pan de Coco', 'Coconut-filled sweet bread', 45.00, 28.00, 38, 'uploads/products/pan-de-coco.jpg', 35, '2025-12-05', 0, NULL, NULL, '2025-11-17 17:04:11', '2025-12-02 10:00:00', 1),
(6, 2, 4, 'Cheese Bread', 'Soft bread with cheese filling', 75.00, 45.00, 40, 'uploads/products/cheese-bread.jpg', 20, '2025-12-05', 0, NULL, NULL, '2025-11-17 17:04:11', '2025-12-02 10:00:00', 1);

--
-- Triggers `products`
--
DROP TRIGGER IF EXISTS `trg_update_product_availability`;
DELIMITER $$
CREATE TRIGGER `trg_update_product_availability` BEFORE UPDATE ON `products` FOR EACH ROW BEGIN
    IF NEW.stock_quantity <= 0 THEN
        SET NEW.is_available = FALSE;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `order_id` int NOT NULL,
  `rating` int NOT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `order_id` (`order_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `address`, `contact_number`, `profile_image`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'Juan Dela Cruz', 'juan@email.com', 'password123', '123 Street, Manila', '09171234567', NULL, '2025-11-17 17:04:11', '2025-11-17 17:16:14', 1),
(2, 'Maria Santos', 'maria@email.com', 'password123', '456 Avenue, Quezon City', '09181234567', NULL, '2025-11-17 17:04:11', '2025-11-17 17:16:14', 1),
(3, 'Pedro Reyes', 'pedro@email.com', 'password123', '789 Road, Makati', '09191234567', NULL, '2025-11-17 17:04:11', '2025-11-17 17:16:14', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_cart_summary`
--
DROP VIEW IF EXISTS `vw_cart_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_cart_summary` AS 
SELECT 
    `c`.`cart_id` AS `cart_id`, 
    `c`.`user_id` AS `user_id`, 
    `u`.`name` AS `user_name`, 
    `p`.`name` AS `product_name`, 
    `p`.`discounted_price` AS `discounted_price`, 
    `c`.`quantity` AS `quantity`, 
    (`p`.`discounted_price` * `c`.`quantity`) AS `subtotal`, 
    `b`.`name` AS `bakery_name` 
FROM (((`cart` `c` 
    JOIN `users` `u` ON((`c`.`user_id` = `u`.`user_id`))) 
    JOIN `products` `p` ON((`c`.`product_id` = `p`.`product_id`))) 
    JOIN `bakeries` `b` ON((`p`.`bakery_id` = `b`.`bakery_id`)));

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_order_summary`
--
DROP VIEW IF EXISTS `vw_order_summary`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_order_summary` AS 
SELECT 
    `o`.`order_id` AS `order_id`, 
    `o`.`order_number` AS `order_number`, 
    `u`.`name` AS `customer_name`, 
    `u`.`email` AS `customer_email`, 
    `o`.`total_amount` AS `total_amount`, 
    `o`.`order_status` AS `order_status`, 
    `o`.`payment_status` AS `payment_status`, 
    `o`.`delivery_option` AS `delivery_option`, 
    `o`.`ordered_at` AS `ordered_at`, 
    COUNT(`oi`.`order_item_id`) AS `total_items` 
FROM ((`orders` `o` 
    JOIN `users` `u` ON((`o`.`user_id` = `u`.`user_id`))) 
    JOIN `order_items` `oi` ON((`o`.`order_id` = `oi`.`order_id`))) 
GROUP BY `o`.`order_id`;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_products_full`
--
DROP VIEW IF EXISTS `vw_products_full`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_products_full` AS 
SELECT 
    `p`.`product_id` AS `product_id`, 
    `p`.`name` AS `product_name`, 
    `p`.`description` AS `description`, 
    `p`.`original_price` AS `original_price`, 
    `p`.`discounted_price` AS `discounted_price`, 
    `p`.`discount_percentage` AS `discount_percentage`, 
    `p`.`image_url` AS `image_url`, 
    `p`.`stock_quantity` AS `stock_quantity`, 
    `p`.`is_available` AS `is_available`, 
    `p`.`is_on_sale` AS `is_on_sale`,
    `b`.`name` AS `bakery_name`, 
    `b`.`address` AS `bakery_address`, 
    `c`.`name` AS `category_name` 
FROM ((`products` `p` 
    JOIN `bakeries` `b` ON((`p`.`bakery_id` = `b`.`bakery_id`))) 
    JOIN `categories` `c` ON((`p`.`category_id` = `c`.`category_id`)));

--
-- Constraints for dumped tables
--

ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`bakery_id`) REFERENCES `bakeries` (`bakery_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE;

ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;