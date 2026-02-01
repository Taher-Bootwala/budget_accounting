<?php
require_once __DIR__ . '/../config/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/add_product_type_column.sql');
    dbExecute($sql);
    echo "Migration successful\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
