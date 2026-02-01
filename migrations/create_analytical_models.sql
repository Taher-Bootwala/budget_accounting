-- Migration: Create Auto Analytical Model Schema
-- Date: 2026-02-01

-- 1. Create contact_tags table for partner categorization
CREATE TABLE IF NOT EXISTS `contact_tags` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Add tag_id column to contacts table
ALTER TABLE `contacts` ADD COLUMN IF NOT EXISTS `tag_id` INT(11) NULL AFTER `type`;
ALTER TABLE `contacts` ADD CONSTRAINT `fk_contacts_tag` FOREIGN KEY (`tag_id`) REFERENCES `contact_tags`(`id`) ON DELETE SET NULL;

-- 3. Create auto_analytical_models table with priority-based matching
CREATE TABLE IF NOT EXISTS `auto_analytical_models` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `status` ENUM('draft','confirmed','cancelled') NOT NULL DEFAULT 'draft',
  
  -- Matching Criteria (all nullable for flexible matching)
  `partner_tag_id` INT(11) NULL COMMENT 'FK to contact_tags - matches contacts by tag',
  `product_category` VARCHAR(100) NULL COMMENT 'Matches products by category',
  `partner_id` INT(11) NULL COMMENT 'FK to contacts - matches specific partner',
  `product_id` INT(11) NULL COMMENT 'FK to products - matches specific product',
  
  -- Allocation Target
  `cost_center_id` INT(11) NOT NULL COMMENT 'Cost center to allocate to',
  
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_partner_tag` (`partner_tag_id`),
  KEY `idx_product_category` (`product_category`),
  KEY `idx_partner` (`partner_id`),
  KEY `idx_product` (`product_id`),
  KEY `idx_cost_center` (`cost_center_id`),
  
  CONSTRAINT `fk_aam_partner_tag` FOREIGN KEY (`partner_tag_id`) REFERENCES `contact_tags`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_aam_partner` FOREIGN KEY (`partner_id`) REFERENCES `contacts`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_aam_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_aam_cost_center` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Insert sample contact tags
INSERT INTO `contact_tags` (`name`) VALUES 
('Premium Partner'),
('Regular Partner'),
('Wholesale'),
('Retail')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
