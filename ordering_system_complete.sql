-- phpMyAdmin SQL Dump
-- Complete OLTP & OLAP Database Schema
-- Includes transactional tables and data warehouse

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ========================================
-- CREATE DATABASE
-- ========================================
CREATE DATABASE IF NOT EXISTS `ordering_system`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `ordering_system`;

-- ========================================
-- OLTP TABLES (Transactional)
-- ========================================

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Food items table
CREATE TABLE IF NOT EXISTS `foods` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_is_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table (OLTP)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED,
  `customer_name` varchar(100) NOT NULL,
  `email` varchar(255),
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `notes` text,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT 'cash',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` datetime,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status_created` (`status`, `created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table (OLTP)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` int(10) UNSIGNED NOT NULL,
  `food_id` int(10) UNSIGNED NOT NULL,
  `food_name` varchar(150) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `line_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `idx_food_id` (`food_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log for audit trail
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50),
  `entity_id` int(10) UNSIGNED,
  `description` text,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- OLAP TABLES (Data Warehouse)
-- ========================================

-- Daily sales summary
CREATE TABLE IF NOT EXISTS `daily_sales` (
  `sales_date` date NOT NULL,
  `total_orders` int(10) UNSIGNED DEFAULT 0,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `total_items` int(10) UNSIGNED DEFAULT 0,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `completed_orders` int(10) UNSIGNED DEFAULT 0,
  `pending_orders` int(10) UNSIGNED DEFAULT 0,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sales_date`),
  KEY `idx_date` (`sales_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product daily sales
CREATE TABLE IF NOT EXISTS `product_daily_sales` (
  `sales_date` date NOT NULL,
  `food_id` int(10) UNSIGNED NOT NULL,
  `food_name` varchar(150) NOT NULL,
  `category` varchar(50),
  `units_sold` int(10) UNSIGNED DEFAULT 0,
  `revenue` decimal(12,2) DEFAULT 0.00,
  `avg_price` decimal(10,2) DEFAULT 0.00,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`sales_date`, `food_id`),
  KEY `idx_date` (`sales_date`),
  KEY `idx_food_id` (`food_id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders by hour
CREATE TABLE IF NOT EXISTS `orders_by_hour` (
  `hour_start` datetime NOT NULL,
  `order_count` int(10) UNSIGNED DEFAULT 0,
  `revenue` decimal(12,2) DEFAULT 0.00,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`hour_start`),
  KEY `idx_hour` (`hour_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monthly performance summary
CREATE TABLE IF NOT EXISTS `monthly_summary` (
  `year_month` varchar(7) NOT NULL,
  `total_orders` int(10) UNSIGNED DEFAULT 0,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `total_customers` int(10) UNSIGNED DEFAULT 0,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `top_product` varchar(150),
  `top_product_revenue` decimal(12,2),
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`year_month`),
  KEY `idx_year_month` (`year_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Category performance
CREATE TABLE IF NOT EXISTS `category_performance` (
  `category` varchar(50) NOT NULL,
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `total_units` int(10) UNSIGNED DEFAULT 0,
  `order_count` int(10) UNSIGNED DEFAULT 0,
  `avg_revenue` decimal(10,2) DEFAULT 0.00,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customer analytics
CREATE TABLE IF NOT EXISTS `customer_analytics` (
  `user_id` int(10) UNSIGNED,
  `total_orders` int(10) UNSIGNED DEFAULT 0,
  `total_spent` decimal(12,2) DEFAULT 0.00,
  `avg_order_value` decimal(10,2) DEFAULT 0.00,
  `first_order_date` date,
  `last_order_date` date,
  `preferred_category` varchar(50),
  `lifetime_value` decimal(12,2) DEFAULT 0.00,
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
