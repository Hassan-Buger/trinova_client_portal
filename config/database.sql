-- TriNova Accounting Client Portal Database Schema
-- CREATE DATABASE IF NOT EXISTS `trinova_portal`
--   DEFAULT CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
-- 
-- USE `trinova_portal`;

-- Core Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('client', 'staff') NOT NULL DEFAULT 'client',
  `status` ENUM('active', 'suspended', 'pending_activation') NOT NULL DEFAULT 'active',
  `reset_token` VARCHAR(255) NULL,
  `reset_token_expires_at` DATETIME NULL,
  `verification_code` VARCHAR(6) NULL,
  `verification_code_expires_at` DATETIME NULL,
  `activation_token` VARCHAR(255) NULL,
  `failed_login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` DATETIME NULL,
  INDEX `idx_users_role_status` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clients Core Profile
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `aml_status` ENUM('Complete', 'Action Required') NOT NULL DEFAULT 'Action Required',
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multi-business linkage per client
CREATE TABLE IF NOT EXISTS `client_entities` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `company_name` VARCHAR(150) NOT NULL,
  `company_number` VARCHAR(30) NULL,
  `tax_reference` VARCHAR(50) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_entities_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  INDEX `idx_entities_client` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documents Management
CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `uploaded_by_user_id` INT UNSIGNED NOT NULL,
  `direction` ENUM('client_upload', 'from_trinova') NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `stored_path` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'Ready',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_docs_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_docs_user` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`),
  INDEX `idx_docs_client_dir` (`client_id`, `direction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Document Requests Workflow
CREATE TABLE IF NOT EXISTS `document_requests` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `created_by_user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `due_date` DATE NOT NULL,
  `status` ENUM('Awaiting Client', 'Uploaded', 'Under Review', 'Completed') NOT NULL DEFAULT 'Awaiting Client',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_reqs_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reqs_user` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`),
  INDEX `idx_reqs_client_status` (`client_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Threaded Messaging
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `thread_id` INT UNSIGNED NULL,
  `body` TEXT NOT NULL,
  `read_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_msg_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  INDEX `idx_msg_client_read` (`client_id`, `read_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compliance Deadlines
CREATE TABLE IF NOT EXISTS `deadlines` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `type` ENUM('VAT', 'Payroll', 'Accounts', 'Corporation Tax', 'Self Assessment', 'Confirmation Statement') NOT NULL,
  `due_date` DATE NOT NULL,
  `status` ENUM('Pending', 'Overdue', 'Completed') NOT NULL DEFAULT 'Pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_deadlines_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  INDEX `idx_deadlines_client_date` (`client_id`, `due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meetings Integration Audit
CREATE TABLE IF NOT EXISTS `meetings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `client_id` INT UNSIGNED NOT NULL,
  `type` ENUM('existing_client_meeting', 'telephone_call') NOT NULL,
  `external_booking_reference` VARCHAR(100) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_meetings_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compliance & Security Audit Log
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `target_type` VARCHAR(50) NOT NULL,
  `target_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_audit_user_action` (`user_id`, `action_type`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Notifications Queue
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `related_entity` VARCHAR(50) NULL,
  `sent_at` DATETIME NULL,
  `read_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Data: Password for all default accounts is "password123"
-- Hashed using password_hash('password123', PASSWORD_BCRYPT)
-- $2y$12$uUgV4QcXGo9b9eOEO3/rmuHsKgbwXBm06PfvEgIchptJEsOTFv4ee

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'Kirsty', 'kirsty@trinova.co.uk', '$2y$12$uUgV4QcXGo9b9eOEO3/rmuHsKgbwXBm06PfvEgIchptJEsOTFv4ee', 'staff', 'active'),
(2, 'Jane', 'jane@trinova.co.uk', '$2y$12$uUgV4QcXGo9b9eOEO3/rmuHsKgbwXBm06PfvEgIchptJEsOTFv4ee', 'staff', 'active'),
(3, 'Emma', 'emma@trinova.co.uk', '$2y$12$uUgV4QcXGo9b9eOEO3/rmuHsKgbwXBm06PfvEgIchptJEsOTFv4ee', 'staff', 'active'),
(4, 'Jess', 'jess@trinova.co.uk', '$2y$12$uUgV4QcXGo9b9eOEO3/rmuHsKgbwXBm06PfvEgIchptJEsOTFv4ee', 'staff', 'active'),
(5, 'Nick Powell', 'nick@powellelectrical.co.uk', '$2y$12$uUgV4QcXGo9b9eOEO3/rmuHsKgbwXBm06PfvEgIchptJEsOTFv4ee', 'client', 'active');

INSERT INTO `clients` (`id`, `user_id`, `phone`, `address`, `aml_status`, `notes`) VALUES
(1, 5, '07700 900432', '14 Kiln Lane, Leeds LS1 4AB', 'Complete', 'Client prefers email for confirmations. Keep payroll reminders short.');

INSERT INTO `client_entities` (`id`, `client_id`, `company_name`, `company_number`, `tax_reference`) VALUES
(1, 1, 'Powell Electrical Ltd', '08942104', '9482104821'),
(2, 1, 'GGDC Ltd', '09214820', '8421048201'),
(3, 1, 'Personal Tax', NULL, '4821049281');

INSERT INTO `deadlines` (`id`, `client_id`, `type`, `due_date`, `status`) VALUES
(1, 1, 'Payroll', '2026-07-26', 'Pending'),
(2, 1, 'VAT', '2026-08-07', 'Pending'),
(3, 1, 'Confirmation Statement', '2026-09-03', 'Pending'),
(4, 1, 'Corporation Tax', '2026-10-01', 'Pending'),
(5, 1, 'Self Assessment', '2027-01-31', 'Pending');

INSERT INTO `document_requests` (`id`, `client_id`, `created_by_user_id`, `title`, `description`, `due_date`, `status`) VALUES
(1, 1, 3, 'June bank statements', 'Upload PDF statements for June accounts', '2026-07-28', 'Awaiting Client'),
(2, 1, 3, 'CIS certificates', 'Two subcontractor documents needed', '2026-07-30', 'Awaiting Client');

INSERT INTO `documents` (`id`, `client_id`, `uploaded_by_user_id`, `direction`, `filename`, `stored_path`, `description`, `status`) VALUES
(1, 1, 3, 'from_trinova', 'Draft accounts 2025.pdf', 'draft_accounts_2025_hash.pdf', 'Uploaded by Emma', 'New'),
(2, 1, 3, 'from_trinova', 'VAT return Q2.pdf', 'vat_return_q2_hash.pdf', 'Submitted copy', 'Ready');

INSERT INTO `messages` (`id`, `client_id`, `sender_id`, `thread_id`, `body`, `read_at`) VALUES
(1, 1, 3, 1, 'Hi Nick, could you send over the June payroll figures when you have a moment?', NOW()),
(2, 1, 5, 1, 'Of course — I have just uploaded them to the portal.', NOW()),
(3, 1, 3, 1, 'Perfect, got them. Thanks Nick, I will process these today.', NULL);
