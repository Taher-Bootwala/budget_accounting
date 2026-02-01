<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDB();
    echo "Connected. Updating status ENUM...\n";
    
    // Note: We must include ALL existing values + new ones
    $pdo->exec("ALTER TABLE documents MODIFY COLUMN status ENUM('draft', 'posted', 'cancelled', 'partial', 'paid') NOT NULL DEFAULT 'draft'");
    
    echo "Status ENUM updated successfully.\n";
    
    // Verify
    $stmt = $pdo->query("SHOW COLUMNS FROM documents LIKE 'status'");
    print_r($stmt->fetch());

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
