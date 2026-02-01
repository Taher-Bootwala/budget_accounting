<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = getDB();
    echo "Connected to DB.\n";
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'type'");
    $col = $stmt->fetch();
    
    if (!$col) {
        echo "Column 'type' missing. Adding it...\n";
        $pdo->exec("ALTER TABLE products ADD COLUMN type ENUM('purchase', 'sales', 'both') NOT NULL DEFAULT 'both'");
        echo "Column 'type' added successfully.\n";
    } else {
        echo "Column 'type' already exists.\n";
    }

    // Update existing records
    $pdo->exec("UPDATE products SET type = 'both' WHERE type IS NULL");
    echo "Values updated.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
