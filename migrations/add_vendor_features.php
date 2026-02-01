<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDB();
    echo "Connected. Starting migration...\n";

    // 1. Add vendor_id to products
    echo "Checking 'products' table for 'vendor_id'...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'vendor_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE products ADD COLUMN vendor_id INT NULL AFTER id");
        $pdo->exec("ALTER TABLE products ADD CONSTRAINT fk_products_vendor FOREIGN KEY (vendor_id) REFERENCES contacts(id) ON DELETE SET NULL");
        echo "Added 'vendor_id' column to products.\n";
    } else {
        echo "'vendor_id' column already exists.\n";
    }

    // 2. Update documents status ENUM
    echo "Updating 'documents' status ENUM...\n";
    // Current statuses: 'draft', 'posted', 'cancelled', 'partial', 'paid'
    // New status needed: 'pending_vendor'
    // Note: It's safer to redefine the whole list
    $pdo->exec("ALTER TABLE documents MODIFY COLUMN status ENUM('draft', 'posted', 'cancelled', 'partial', 'paid', 'pending_vendor') NOT NULL DEFAULT 'draft'");
    echo "Updated 'status' enum.\n";

    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
