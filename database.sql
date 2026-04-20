-- ============================================================
-- E&N School Supplies — Full Database Schema
-- Database: azeu_en_school_supplies
-- ============================================================

CREATE DATABASE IF NOT EXISTS `azeu_en_school_supplies`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `azeu_en_school_supplies`;

-- ------------------------------------------------------------
-- Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `full_name`        VARCHAR(150)    NOT NULL,
  `email`            VARCHAR(150)    NOT NULL UNIQUE,
  `phone`            VARCHAR(20)     NOT NULL DEFAULT '',
  `password`         TEXT            NOT NULL,
  `role`             ENUM('admin','staff','customer') NOT NULL,
  `status`           ENUM('active','pending','flagged') NOT NULL DEFAULT 'active',
  `flag_reason`      TEXT            NULL,
  `profile_image`    VARCHAR(255)    NULL,
  `theme_preference` ENUM('light','dark','auto') NOT NULL DEFAULT 'auto',
  `created_by`       INT             NULL,
  `created_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Item Categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `item_categories` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Default Item Names (for inventory add dropdown)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `default_item_names` (
  `id`        INT AUTO_INCREMENT PRIMARY KEY,
  `item_name` VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Inventory
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `item_name`     VARCHAR(150)   NOT NULL,
  `category_id`   INT            NULL,
  `price`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `stock_count`   INT            NOT NULL DEFAULT 0,
  `max_order_qty` INT            NOT NULL DEFAULT 1,
  `item_image`    VARCHAR(255)   NULL,
  `created_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_inventory_category` FOREIGN KEY (`category_id`) REFERENCES `item_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Orders
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `order_code`   VARCHAR(20)    NOT NULL UNIQUE,
  `user_id`      INT            NULL,
  `guest_name`   VARCHAR(150)   NULL,
  `guest_phone`  VARCHAR(20)    NULL,
  `guest_note`   TEXT           NULL,
  `status`       ENUM('pending','ready','claimed','cancelled') NOT NULL DEFAULT 'pending',
  `total_price`  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `processed_by` INT            NULL,
  `created_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user`      FOREIGN KEY (`user_id`)      REFERENCES `users`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_orders_processed` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Order Items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`                 INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`           INT            NOT NULL,
  `item_id`            INT            NOT NULL,
  `item_name_snapshot` VARCHAR(150)   NOT NULL,
  `quantity`           INT            NOT NULL DEFAULT 1,
  `unit_price`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_item`  FOREIGN KEY (`item_id`)  REFERENCES `inventory`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Staff Sessions (login tracking)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_sessions` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`          INT        NOT NULL,
  `login_time`       TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time`      TIMESTAMP  NULL,
  `logout_type`      ENUM('manual','auto_system') NULL,
  `duration_minutes` INT        NULL,
  `is_suspicious`    TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_ss_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- System Settings (key-value store)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key`   VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT         NOT NULL,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- System Logs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_logs` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `level`      ENUM('info','warning','error') NOT NULL DEFAULT 'info',
  `message`    TEXT        NOT NULL,
  `context`    JSON        NULL,
  `user_id`    INT         NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;


-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin account — password will be AES-encrypted by the app on first use.
-- Credentials: admin@en.com / admin123
INSERT INTO `users` (`full_name`, `email`, `phone`, `password`, `role`, `status`)
VALUES ('Administrator', 'admin@en.com', '', 'admin123', 'admin', 'active');

-- Default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
  ('store_name',             'E&N School Supplies'),
  ('store_phone',            ''),
  ('store_email',            ''),
  ('logo_path',              'assets/images/logo.png'),
  ('timezone',               'Asia/Manila'),
  ('force_dark_mode',        '0'),
  ('disable_nologin_orders', '0'),
  ('online_payment',         '0'),
  ('system_status',          'online'),
  ('auto_logout_hours',      '8');

-- Default item categories
INSERT INTO `item_categories` (`category_name`) VALUES
  ('Notebooks'),
  ('Writing Instruments'),
  ('Paper Products'),
  ('Art Supplies'),
  ('Filing & Organization'),
  ('Bags & Cases'),
  ('Measuring Tools'),
  ('Adhesives & Tapes'),
  ('Scissors & Cutters'),
  ('General Supplies');

-- Default item names
INSERT INTO `default_item_names` (`item_name`) VALUES
  ('Notebook (80 leaves)'),
  ('Notebook (100 leaves)'),
  ('Ballpen (Blue)'),
  ('Ballpen (Black)'),
  ('Ballpen (Red)'),
  ('Pencil #2'),
  ('Eraser'),
  ('Ruler (12 inch)'),
  ('Ruler (18 inch)'),
  ('Scissors'),
  ('Glue Stick'),
  ('Liquid Glue'),
  ('Yellow Pad'),
  ('Bond Paper (Short)'),
  ('Bond Paper (Long)'),
  ('Folder (Long)'),
  ('Folder (Short)'),
  ('Clear Book (20 pockets)'),
  ('Crayons (24 colors)'),
  ('Colored Pencils (12 colors)'),
  ('Marker (Black)'),
  ('Highlighter (Yellow)'),
  ('Correction Tape'),
  ('Stapler'),
  ('Staple Wire (#35)'),
  ('Masking Tape'),
  ('Transparent Tape'),
  ('Pencil Case'),
  ('Backpack'),
  ('Protractor');
