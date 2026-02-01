<?php
require_once __DIR__ . '/config/db.php';
try {
    $stmt = getDB()->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";
    
    // Also show full details
    $stmt = getDB()->query("DESCRIBE products");
    print_r($stmt->fetchAll());
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
