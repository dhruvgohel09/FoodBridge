-- FoodBridge Database Setup SQL
-- Database: foodbridge

CREATE DATABASE IF NOT EXISTS `foodbridge` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `foodbridge`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `status` ENUM('Active', 'Inactive', 'Blocked') DEFAULT 'Active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(100) DEFAULT 'Super Admin / Ops Head',
  `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Password Resets / OTP Table
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(150) NOT NULL,
  `otp` VARCHAR(10) NOT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pr_ident_otp` (`identifier`, `otp`, `is_verified`),
  INDEX `idx_pr_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Cities Table
CREATE TABLE IF NOT EXISTS `cities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `status_label` VARCHAR(100) DEFAULT 'Active Hub',
  `centers_count` INT DEFAULT 4,
  `description` VARCHAR(255) DEFAULT '',
  `eta_info` VARCHAR(100) DEFAULT '< 45 mins',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Organizations Table
CREATE TABLE IF NOT EXISTS `organizations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `city_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `short_code` VARCHAR(10) DEFAULT 'HF',
  `category` VARCHAR(100) DEFAULT 'Verified NGO distribution',
  `rating_info` VARCHAR(100) DEFAULT 'Admin Vetted Partner',
  `address` VARCHAR(255) NOT NULL,
  `distance_info` VARCHAR(100) DEFAULT '1.8 km from Pickup Hub',
  `phone` VARCHAR(50) DEFAULT '+91 828145-FOOD',
  `accepted_items` VARCHAR(255) DEFAULT 'Cooked Food, Packaged Food, Fresh Fruits',
  `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_org_active` (`is_active`),
  FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Donations Table
CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `donation_code` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `food_name` VARCHAR(255) NOT NULL,
  `food_type` VARCHAR(100) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit` VARCHAR(50) NOT NULL DEFAULT 'Kg',
  `serves` INT NOT NULL DEFAULT 10,
  `available_date` DATE NOT NULL,
  `available_time` VARCHAR(50) NOT NULL,
  `food_photo` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `city_id` INT NOT NULL,
  `organization_id` INT NOT NULL,
  `pickup_address` TEXT DEFAULT NULL,
  `contact_phone` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('Pending', 'Accepted', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_don_status` (`status`),
  INDEX `idx_don_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`city_id`) REFERENCES `cities`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`organization_id`) REFERENCES `organizations`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Food Requests Table
CREATE TABLE IF NOT EXISTS `food_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `request_code` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `food_type` VARCHAR(100) NOT NULL,
  `dietary_category` VARCHAR(100) DEFAULT 'Vegetarian',
  `people_count` INT NOT NULL DEFAULT 2,
  `city` VARCHAR(100) NOT NULL,
  `required_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `assigned_org_id` INT DEFAULT NULL,
  `contact_window` VARCHAR(100) DEFAULT 'Today, 1:30 PM',
  `status` ENUM('Pending', 'Accepted', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_fr_status` (`status`),
  INDEX `idx_fr_org` (`assigned_org_id`),
  INDEX `idx_fr_created` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Seed Data

-- Valid bcrypt hashes:
-- 'admin123'    -> $2y$10$xoASZdgSM4agLgQGcxT8tekLsv.qbAFTlfE0gJKr.rpvnkSp8FJWG
-- 'password123' -> $2y$10$FnhdLO9lDbffC3cNUC6fRunz.Tj4x40JoFSCNVfTxVh4z.VkieefO

INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'System Admin', 'ops.head@foodbridge.org', '$2y$10$xoASZdgSM4agLgQGcxT8tekLsv.qbAFTlfE0gJKr.rpvnkSp8FJWG', 'Super Admin / Ops Head', 'Active'),
(2, 'Operations Admin', 'admin@foodbridge.org', '$2y$10$xoASZdgSM4agLgQGcxT8tekLsv.qbAFTlfE0gJKr.rpvnkSp8FJWG', 'System Administrator', 'Active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `city`, `role`, `status`) VALUES
(1, 'Dhruv Gohel', 'dhruv.gohel@example.com', '+91 98765 43210', '$2y$10$FnhdLO9lDbffC3cNUC6fRunz.Tj4x40JoFSCNVfTxVh4z.VkieefO', 'Rajkot', 'user', 'Active'),
(2, 'Rahul Sharma', 'rahul.sharma@example.com', '+91 98250 12345', '$2y$10$FnhdLO9lDbffC3cNUC6fRunz.Tj4x40JoFSCNVfTxVh4z.VkieefO', 'Rajkot', 'user', 'Active'),
(3, 'Priya Mehta', 'priya.mehta@example.com', '+91 98795 67890', '$2y$10$FnhdLO9lDbffC3cNUC6fRunz.Tj4x40JoFSCNVfTxVh4z.VkieefO', 'Ahmedabad', 'user', 'Active'),
(4, 'Amit Patel', 'amit.patel@example.com', '+91 94260 54321', '$2y$10$FnhdLO9lDbffC3cNUC6fRunz.Tj4x40JoFSCNVfTxVh4z.VkieefO', 'Surat', 'user', 'Active'),
(5, 'Neha Joshi', 'neha.j@example.com', '+91 99099 11223', '$2y$10$FnhdLO9lDbffC3cNUC6fRunz.Tj4x40JoFSCNVfTxVh4z.VkieefO', 'Vadodara', 'user', 'Active'),
(6, 'Vikram Singh', 'vikram.s@example.com', '+91 98241 99887', '$2y$10$FnhdLO9lDbffC3cNUC6fRunz.Tj4x40JoFSCNVfTxVh4z.VkieefO', 'Bhavnagar', 'user', 'Active'),
(7, 'Anjali Dave', 'anjali.dave@example.com', '+91 97230 44556', '$2y$10$FnhdLO9lDbffC3cNUC6fRunz.Tj4x40JoFSCNVfTxVh4z.VkieefO', 'Junagadh', 'user', 'Active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

INSERT INTO `cities` (`id`, `name`, `status_label`, `centers_count`, `description`, `eta_info`, `is_active`) VALUES
(1, 'Rajkot', 'Active Hub', 4, 'Saurashtra regional redistribution center', '< 45 mins', 1),
(2, 'Ahmedabad', 'Active Hub', 6, 'East & West zones covered', '< 30 mins', 1),
(3, 'Surat', 'Active Hub', 5, 'Textile & Ring Road clusters', '< 40 mins', 1),
(4, 'Vadodara', 'Active Hub', 4, 'Central Sanskar Nagari division', '< 35 mins', 1),
(5, 'Junagadh', 'Active Hub', 2, 'Girnar foothill relief network', '< 50 mins', 1),
(6, 'Bhavnagar', 'Active Hub', 2, 'Coastal & inner city hubs', '< 45 mins', 1),
(7, 'Gandhinagar', 'Active Hub', 3, 'Capital green corridor & sectors', '< 35 mins', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `organizations` (`id`, `city_id`, `name`, `short_code`, `category`, `rating_info`, `address`, `distance_info`, `phone`, `accepted_items`, `description`, `is_active`) VALUES
(1, 1, 'Food Hope Foundation', 'HF', 'Verified NGO distribution', 'Admin Vetted Partner', 'Rajkot Central Ward', '1.8 km from Pickup Hub', '+91 828145-FOOD', 'Cooked Food, Packaged Food, Fresh Fruits', 'Community organization working to connect surplus food with people in need across Rajkot neighborhoods.', 1),
(2, 1, 'Saurashtra Annakshetra Trust', 'SA', 'Community Relief Kitchen', 'Admin Vetted Partner', 'Bhaktinagar Circle, Rajkot', '3.2 km from Pickup Hub', '+91 94271-FOOD', 'Cooked Food, Dry Ration', 'Distributes cooked hot meals to municipal shelter centers and low-income community areas.', 1),
(3, 1, 'Rajkot Roti Bank', 'RB', 'Dedicated Meal Redistribution', 'Admin Vetted Partner', 'Kalawad Road, Rajkot', '2.5 km from Pickup Hub', '+91 98980-ROTI', 'Packed Thalis, Cooked Food', 'Daily evening drives providing packed thalis directly to migrant laborers and street families.', 1),
(4, 2, 'Ahmedabad Food Rescue Hub', 'AF', 'City Distribution Hub', 'Admin Vetted Partner', 'Ashram Road, Ahmedabad', '2.0 km from Hub', '+91 97123-AHD01', 'All Food Categories', 'Wide network of refrigerated vans connecting banquets, caterers, and distribution centers.', 1),
(5, 3, 'Surat Annapurna Seva', 'AS', 'Community Kitchen', 'Admin Vetted Partner', 'Ring Road, Surat', '2.8 km from Hub', '+91 98251-SUR01', 'Cooked Food, Dry Ration', 'Serves industrial worker clusters and slum colonies across Surat.', 1),
(6, 4, 'Vadodara Relief Collective', 'VR', 'Regional Hub', 'Admin Vetted Partner', 'Alkapuri, Vadodara', '3.1 km from Hub', '+91 99042-BRD01', 'Cooked Food, Fresh Produce', 'Partners with local orphanages, day centers, and elderly care homes across Vadodara.', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `donations` (`id`, `donation_code`, `user_id`, `food_name`, `food_type`, `quantity`, `unit`, `serves`, `available_date`, `available_time`, `food_photo`, `notes`, `city_id`, `organization_id`, `pickup_address`, `contact_phone`, `status`, `created_at`) VALUES
(1, 'FB-2026-001', 1, 'Rice and Vegetable Curry', 'Cooked Food', 10.00, 'Kg', 25, '2026-09-24', '11:30', 'images/wholesome-meals.jpg', 'Freshly prepared food. Suitable for children and adults. Packed in food-grade foil containers.', 1, 1, '12 Patel Colony, Kalawad Road, Rajkot', '+91 98765 43210', 'Pending', '2026-09-24 11:30:00'),
(2, 'FB-2026-002', 1, 'Packed Gujarati Thalis & Khichdi', 'Prepared Meals', 50.00, 'Meals', 50, '2026-09-20', '13:00', 'images/packed-thali.jpg', 'Packed lunch boxes left from a family event. Kept clean and warm.', 1, 2, '5 Shivam Apartment, Yagnik Road, Rajkot', '+91 98765 43210', 'Accepted', '2026-09-20 13:00:00'),
(3, 'FB-2026-003', 1, 'Fresh Seasonal Fruits (Bananas & Apples)', 'Fresh Produce', 30.00, 'Kg', 60, '2026-09-18', '10:00', NULL, 'Clean wholesale surplus fruit boxes.', 1, 3, 'Market Yard Gate 2, Rajkot', '+91 98765 43210', 'Completed', '2026-09-18 10:00:00'),
(4, 'FB-2026-004', 1, 'Dry Ration Kits (Wheat & Rice)', 'Dry Ration', 40.00, 'Kg', 80, '2026-09-12', '16:30', NULL, 'Standard 10kg sealed grain bags.', 1, 1, 'Near University Road, Rajkot', '+91 98765 43210', 'Completed', '2026-09-12 16:30:00')
ON DUPLICATE KEY UPDATE `donation_code` = VALUES(`donation_code`);

INSERT INTO `food_requests` (`id`, `request_code`, `user_id`, `food_type`, `dietary_category`, `people_count`, `city`, `required_date`, `notes`, `assigned_org_id`, `contact_window`, `status`, `created_at`) VALUES
(1, 'FB-REQ-001', 1, 'Cooked Food', 'Vegetarian', 4, 'Rajkot', '2026-09-24', 'Warm dinner meals for a family of 4. Vegetarian preferred if available.', 1, 'Today, 1:30 PM', 'Pending', '2026-09-24 09:30:00'),
(2, 'FB-REQ-002', 1, 'Dry Ration', 'Vegetarian', 6, 'Rajkot', '2026-09-21', 'Dry rations & grain kit for senior citizens.', 2, 'Today, 3:00 PM', 'Accepted', '2026-09-21 11:00:00'),
(3, 'FB-REQ-003', 1, 'Cooked Food', 'Vegetarian', 4, 'Rajkot', '2026-09-15', 'Lunch packet support for relief shelter workers.', 3, 'Completed', 'Completed', '2026-09-15 12:00:00')
ON DUPLICATE KEY UPDATE `request_code` = VALUES(`request_code`);
