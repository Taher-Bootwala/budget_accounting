<?php
/**
 * ============================================================================
 * COST CENTER PREVIEW API
 * ============================================================================
 * 
 * Returns the auto-assigned cost center for a product
 * 
 * ENDPOINT: GET /api/cost_center_preview.php?product_id={id}
 * 
 * PURPOSE:
 * When user adds a product to a document, this API is called to show
 * which cost center will be auto-assigned based on configured rules.
 * 
 * RULE PRIORITY:
 * 1. Product-specific rules checked first
 * 2. Category rules checked if no product rule
 * 
 * RESPONSE FORMAT:
 * {
 *   "cost_center": "Cost Center Name" | null,
 *   "cost_center_id": 123 | null
 * }
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../controllers/RuleController.php';

header('Content-Type: application/json');

$productId = $_GET['product_id'] ?? null;

if (!$productId) {
    echo json_encode(['cost_center' => null]);
    exit;
}

$costCenter = RuleController::findCostCenter($productId);

echo json_encode([
    'cost_center' => $costCenter ? $costCenter['name'] : null,
    'cost_center_id' => $costCenter ? $costCenter['id'] : null
]);
