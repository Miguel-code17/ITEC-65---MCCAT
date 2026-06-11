-- Consolidated OLTP + OLAP schema for MCCAT ordering system
-- Generated: 2026-06-12

-- Drop existing DBs (use with caution in production)
DROP DATABASE IF EXISTS `ordering_dw`;
DROP DATABASE IF EXISTS `ordering_system`;

-- --------------------------------------------------------------------
-- OLTP: ordering_system
-- --------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `ordering_system`
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
USE `ordering_system`;

-- Users table (customers / admins)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foods / menu
CREATE TABLE IF NOT EXISTS `foods` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `category` VARCHAR(100) DEFAULT NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `popular` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders (transactions)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_created_at` (`created_at`),
  KEY `idx_orders_status` (`status`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `food_id` INT UNSIGNED NOT NULL,
  `food_name` VARCHAR(150) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `line_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_food` (`food_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_order_items_food` FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lightweight audit/logs (optional)
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(20) NOT NULL,
  `message` TEXT NOT NULL,
  `context` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- OLAP: ordering_dw (aggregates and time-series)
-- --------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `ordering_dw`
  DEFAULT CHARACTER SET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
USE `ordering_dw`;

-- Daily sales summary (one row per date)
CREATE TABLE IF NOT EXISTS `daily_sales` (
  `sales_date` DATE NOT NULL,
  `total_orders` INT NOT NULL DEFAULT 0,
  `total_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_items` INT NOT NULL DEFAULT 0,
  `avg_order_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `completed_orders` INT NOT NULL DEFAULT 0,
  `pending_orders` INT NOT NULL DEFAULT 0,
  `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sales_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product-level daily sales (one row per date+food_id)
CREATE TABLE IF NOT EXISTS `product_daily_sales` (
  `sales_date` DATE NOT NULL,
  `food_id` INT UNSIGNED NOT NULL,
  `food_name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `units_sold` INT NOT NULL DEFAULT 0,
  `revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `avg_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sales_date`, `food_id`),
  KEY `idx_product_date` (`sales_date`),
  KEY `idx_product_food` (`food_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders by hour for time-series analysis
CREATE TABLE IF NOT EXISTS `orders_by_hour` (
  `hour_start` DATETIME NOT NULL,
  `order_count` INT NOT NULL DEFAULT 0,
  `revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `avg_order_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`hour_start`),
  KEY `idx_hour` (`hour_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Category performance table
CREATE TABLE IF NOT EXISTS `category_performance` (
  `category` VARCHAR(100) NOT NULL,
  `total_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_units` INT NOT NULL DEFAULT 0,
  `order_count` INT NOT NULL DEFAULT 0,
  `avg_revenue` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure privileges and statistics
ANALYZE TABLE daily_sales;
ANALYZE TABLE product_daily_sales;
ANALYZE TABLE orders_by_hour;

COMMIT;

-- End of consolidated schema
