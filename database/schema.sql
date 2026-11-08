-- Orbit Database Schema
-- Database: orbit_db

CREATE DATABASE IF NOT EXISTS `orbit_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `orbit_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `dark_mode` TINYINT(1) DEFAULT 0,
    `timezone` VARCHAR(50) DEFAULT 'UTC',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table
-- Categories can be system defaults or user-created; here we associate with user_id (NULL = default/global, or tied to a user)
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `name` VARCHAR(50) NOT NULL,
    `color` VARCHAR(20) DEFAULT 'secondary',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tasks Table
CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `is_completed` TINYINT(1) DEFAULT 0,
    `completed_at` DATETIME NULL,
    `due_date` DATE NULL,
    `due_time` TIME NULL,
    `reminder_time` DATETIME NULL,
    `reminder_sent` TINYINT(1) DEFAULT 0,
    `snoozed_until` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tasks_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tasks_category` FOREIGN KEY (`category_id`) 
        REFERENCES `categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_status` (`user_id`, `is_completed`),
    INDEX `idx_user_title` (`user_id`, `title`),
    INDEX `idx_tasks_reminder` (`user_id`, `is_completed`, `reminder_sent`, `reminder_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default global categories
INSERT INTO `categories` (`user_id`, `name`, `color`) VALUES
(NULL, 'Work', 'primary'),
(NULL, 'Personal', 'success'),
(NULL, 'Study', 'warning'),
(NULL, 'Health', 'danger')
ON DUPLICATE KEY UPDATE `name`=`name`;

