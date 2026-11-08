-- ==============================================================================
-- Orbit Database Migration: Milestone 6 (Smart Reminders & Alarm Engine)
-- Database: orbit_db
-- ==============================================================================

USE `orbit_db`;

-- 1. Add timezone to users table (defaults to UTC, updated via browser on login/register)
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `timezone` VARCHAR(50) DEFAULT 'UTC' AFTER `dark_mode`;

-- 2. Add due_time, reminder_time, reminder_sent, and snoozed_until to tasks table
ALTER TABLE `tasks`
ADD COLUMN IF NOT EXISTS `due_time` TIME NULL AFTER `due_date`,
ADD COLUMN IF NOT EXISTS `reminder_time` DATETIME NULL AFTER `due_time`,
ADD COLUMN IF NOT EXISTS `reminder_sent` TINYINT(1) DEFAULT 0 AFTER `reminder_time`,
ADD COLUMN IF NOT EXISTS `snoozed_until` DATETIME NULL AFTER `reminder_sent`;

-- 3. Add compound index to optimize the 30-second reminder polling queries
CREATE INDEX IF NOT EXISTS `idx_tasks_reminder` ON `tasks` (`user_id`, `is_completed`, `reminder_sent`, `reminder_time`);

