ALTER TABLE products ADD COLUMN type ENUM('purchase', 'sales', 'both') NOT NULL DEFAULT 'both';
-- Update existing categories if we can guess, otherwise default to both
-- For now, default all to 'both' which is safe
UPDATE products SET type = 'both';
