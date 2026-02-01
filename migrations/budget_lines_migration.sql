-- Migration: Budget Redesign with Budget Lines
-- Date: 2026-02-01
-- Adds name, revised status, and budget_lines linking to analytical models

-- 1. Modify budgets table to add name and update status enum
ALTER TABLE `budgets` 
ADD COLUMN IF NOT EXISTS `name` VARCHAR(150) NOT NULL DEFAULT '' AFTER `id`,
MODIFY COLUMN `status` ENUM('draft','confirmed','revised','cancelled') NOT NULL DEFAULT 'draft',
ADD COLUMN IF NOT EXISTS `revised_from_id` INT(11) NULL AFTER `status`;

-- Add foreign key for revised_from_id
ALTER TABLE `budgets` 
ADD CONSTRAINT `fk_budgets_revised_from` FOREIGN KEY (`revised_from_id`) REFERENCES `budgets`(`id`) ON DELETE SET NULL;

-- 2. Create budget_lines table
CREATE TABLE IF NOT EXISTS `budget_lines` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `budget_id` INT(11) NOT NULL,
  `analytical_model_id` INT(11) NOT NULL COMMENT 'FK to auto_analytical_models',
  `type` ENUM('income','expense') NOT NULL COMMENT 'Income=Sales Invoice, Expense=Vendor Bills',
  `budgeted_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_budget` (`budget_id`),
  KEY `idx_analytical_model` (`analytical_model_id`),
  KEY `idx_type` (`type`),
  
  CONSTRAINT `fk_budget_lines_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_budget_lines_model` FOREIGN KEY (`analytical_model_id`) REFERENCES `auto_analytical_models`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Drop cost_center_id foreign key from budgets (no longer needed, using analytical models instead)
-- Note: This is optional, keeping for backward compatibility
