<?php
/**
 * ============================================================================
 * GLOBAL SEARCH API
 * ============================================================================
 * 
 * Provides unified search across all entities for the search bar
 * 
 * ENDPOINT: GET /api/search.php?q={query}
 * 
 * SEARCHABLE ENTITIES:
 * - Contacts    : Search by name (customers and vendors)
 * - Documents   : Search by document number (PO-0001) or contact name
 * - Cost Centers: Search by name
 * 
 * RESPONSE FORMAT:
 * {
 *   "results": [
 *     {
 *       "type": "contact|document|cost_center",
 *       "icon": "emoji",
 *       "title": "Display title",
 *       "subtitle": "Additional info",
 *       "url": "/Furniture/views/..."
 *     }
 *   ]
 * }
 * 
 * REQUIREMENTS:
 * - Minimum 2 characters in query
 * - User must be authenticated
 * - Returns max 5 results per entity type
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$results = [];
$searchTerm = "%$query%";

// Search Contacts (only name field now)
$contacts = dbFetchAll("
    SELECT id, name, type 
    FROM contacts 
    WHERE name LIKE ? 
    LIMIT 5
", [$searchTerm]);

foreach ($contacts as $contact) {
    $results[] = [
        'type' => 'contact',
        'icon' => $contact['type'] === 'customer' ? '👤' : '🏢',
        'title' => $contact['name'],
        'subtitle' => ucfirst($contact['type']),
        'url' => "/Furniture/views/contacts/form.php?id=" . $contact['id']
    ];
}

// Search Documents (generate document_number from doc_type + id)
$documents = dbFetchAll("
    SELECT d.id, d.doc_type, d.total_amount, d.status, c.name as contact_name,
           CONCAT(d.doc_type, '-', LPAD(d.id, 4, '0')) as document_number
    FROM documents d
    LEFT JOIN contacts c ON d.contact_id = c.id
    WHERE CONCAT(d.doc_type, '-', LPAD(d.id, 4, '0')) LIKE ? OR c.name LIKE ?
    LIMIT 5
", [$searchTerm, $searchTerm]);

foreach ($documents as $doc) {
    $typeIcons = [
        'PO' => '📦',
        'SO' => '🛒',
        'VendorBill' => '📥',
        'CustomerInvoice' => '📤'
    ];
    $results[] = [
        'type' => 'document',
        'icon' => $typeIcons[$doc['doc_type']] ?? '📄',
        'title' => $doc['document_number'],
        'subtitle' => ($doc['contact_name'] ?? 'Unknown') . ' • ' . formatCurrency($doc['total_amount']),
        'url' => "/Furniture/views/documents/view.php?id=" . $doc['id']
    ];
}

// Search Cost Centers
$costCenters = dbFetchAll("
    SELECT id, name 
    FROM cost_centers 
    WHERE name LIKE ? 
    LIMIT 5
", [$searchTerm]);

foreach ($costCenters as $cc) {
    $results[] = [
        'type' => 'cost_center',
        'icon' => '🎯',
        'title' => $cc['name'],
        'subtitle' => 'Cost Center',
        'url' => "/Furniture/views/cost_centers/drilldown.php?id=" . $cc['id']
    ];
}

echo json_encode(['results' => $results]);
