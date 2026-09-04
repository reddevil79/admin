-- =====================================================
-- INVENTORY MANAGEMENT SYSTEM - DATABASE SCHEMA v2.0
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- 1. CATEGORIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `category_list` (
  `category_id` int(30) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  KEY `idx_status_delete` (`status`, `delete_flag`),
  FULLTEXT KEY `ft_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 2. USERS TABLE (with roles)
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_list` (
  `user_id` int(30) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL UNIQUE,
  `username` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'staff',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  KEY `idx_email` (`email`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 3. PRODUCTS TABLE (Enhanced)
-- =====================================================
CREATE TABLE IF NOT EXISTS `product_list` (
  `product_id` int(30) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(100) NOT NULL UNIQUE,
  `barcode` varchar(255),
  `category_id` int(30) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `cost_price` decimal(12,2) DEFAULT 0,
  `price` decimal(12,2) NOT NULL DEFAULT 0,
  `discount_percent` decimal(5,2) DEFAULT 0,
  `stock` decimal(12,2) NOT NULL DEFAULT 0,
  `alert_restock` decimal(12,2) NOT NULL DEFAULT 0,
  `image` varchar(255),
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `delete_flag` tinyint(1) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `idx_product_code` (`product_code`),
  KEY `idx_barcode` (`barcode`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status_delete` (`status`, `delete_flag`),
  KEY `idx_stock` (`stock`),
  FULLTEXT KEY `ft_name` (`name`),
  FULLTEXT KEY `ft_description` (`description`),
  CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `category_list` (`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 4. STOCK MOVEMENTS TABLE (History)
-- =====================================================
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `movement_id` int(30) NOT NULL AUTO_INCREMENT,
  `product_id` int(30) NOT NULL,
  `movement_type` enum('OPENING','PURCHASE','SALE','RETURN','ADJUSTMENT','DAMAGE') NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `reference_type` varchar(50),
  `reference_id` int(30),
  `previous_stock` decimal(12,2),
  `new_stock` decimal(12,2),
  `user_id` int(30),
  `notes` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_movement_product` FOREIGN KEY (`product_id`) REFERENCES `product_list` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 5. TRANSACTIONS (Sales) TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `transaction_list` (
  `transaction_id` int(30) NOT NULL AUTO_INCREMENT,
  `receipt_no` varchar(50) NOT NULL UNIQUE,
  `user_id` int(30),
  `sub_total` decimal(12,2) NOT NULL DEFAULT 0,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `discount_percent` decimal(5,2) NOT NULL DEFAULT 0,
  `total` decimal(12,2) NOT NULL DEFAULT 0,
  `tendered_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `change` decimal(12,2) NOT NULL DEFAULT 0,
  `payment_method` varchar(50) DEFAULT 'cash',
  `status` enum('completed','cancelled','refunded') DEFAULT 'completed',
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  UNIQUE KEY `idx_receipt_no` (`receipt_no`),
  KEY `idx_user` (`user_id`),
  KEY `idx_date` (`date_added`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 6. TRANSACTION ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `transaction_items` (
  `item_id` int(30) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(30) NOT NULL,
  `product_id` int(30) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0,
  `price` decimal(12,2) NOT NULL DEFAULT 0,
  `discount` decimal(12,2) DEFAULT 0,
  `subtotal` decimal(12,2),
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_product` (`product_id`),
  CONSTRAINT `fk_transaction_items_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transaction_list` (`transaction_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transaction_items_product` FOREIGN KEY (`product_id`) REFERENCES `product_list` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 7. NOTIFICATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(30) NOT NULL AUTO_INCREMENT,
  `user_id` int(30),
  `type` enum('LOW_STOCK','OUT_OF_STOCK','SALE','PAYMENT_DUE','SYSTEM','ALERT') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `reference_type` varchar(50),
  `reference_id` int(30),
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_type` (`type`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- 8. ACTIVITY LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` int(30) NOT NULL AUTO_INCREMENT,
  `user_id` int(30),
  `action` varchar(100) NOT NULL,
  `module` varchar(100) NOT NULL,
  `reference_id` int(30),
  `description` text,
  `ip_address` varchar(50),
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
