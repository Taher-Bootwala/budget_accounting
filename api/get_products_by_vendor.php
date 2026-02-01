<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$vendorId = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;

if ($vendorId <= 0) {
    echo json_encode([]);
    exit;
}

try {
    // Fetch products belonging to this vendor
    // We also include products with NULL vendor_id (global products) if needed? 
    // User request: "only dispaly that specific vendor added product"
    // So STRICT filtering: vendor_id = ?
    
    $products = dbFetchAll("SELECT * FROM products WHERE vendor_id = ? ORDER BY name", [$vendorId]);
    
    echo json_encode($products);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
