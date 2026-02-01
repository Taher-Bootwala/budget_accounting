-- Add archived column to products and contacts tables
-- Run this in phpMyAdmin or MySQL command line

ALTER TABLE products ADD COLUMN archived TINYINT(1) DEFAULT 0;
ALTER TABLE contacts ADD COLUMN archived TINYINT(1) DEFAULT 0;

-- Set all existing records as active (not archived)
UPDATE products SET archived = 0 WHERE archived IS NULL;
UPDATE contacts SET archived = 0 WHERE archived IS NULL;
