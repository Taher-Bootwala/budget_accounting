<?php
/**
 * ============================================================================
 * REPORT EXPORT API
 * ============================================================================
 * 
 * Generates CSV file downloads for reports
 * 
 * ENDPOINT: GET /api/export.php?type={report_type}&cost_center={id}
 * 
 * EXPORT TYPES:
 * - budget_vs_actual : Budget comparison report with utilization
 * - transactions     : Transaction history (optionally filtered by cost center)
 * 
 * RESPONSE:
 * - Content-Type: text/csv
 * - Forces file download with timestamped filename
 * 
 * USAGE:
 * Called from "Export Report" buttons throughout the application
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../controllers/BudgetEngine.php';

$type = $_GET['type'] ?? 'budget_vs_actual';
$costCenterId = $_GET['cost_center'] ?? null;

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

switch ($type) {
    case 'budget_vs_actual':
        fputcsv($output, ['Cost Center', 'Budget', 'Actual', 'Remaining', 'Utilization %', 'Status']);
        $data = BudgetEngine::getBudgetVsActual();
        foreach ($data as $row) {
            fputcsv($output, [
                $row['cost_center_name'],
                $row['budget_amount'],
                $row['actual_spend'],
                $row['remaining'],
                $row['utilization'],
                $row['health_status']
            ]);
        }
        break;

    case 'transactions':
        fputcsv($output, ['Date', 'Document', 'Cost Center', 'Amount']);
        $sql = "SELECT t.*, cc.name as cost_center_name, d.document_number 
                FROM transactions t 
                JOIN cost_centers cc ON t.cost_center_id = cc.id
                LEFT JOIN documents d ON t.document_id = d.id";
        $params = [];

        if ($costCenterId) {
            $sql .= " WHERE t.cost_center_id = ?";
            $params[] = $costCenterId;
        }
        $sql .= " ORDER BY t.transaction_date DESC";

        $transactions = dbFetchAll($sql, $params);
        foreach ($transactions as $t) {
            fputcsv($output, [
                $t['transaction_date'],
                $t['document_number'] ?? '-',
                $t['cost_center_name'],
                $t['amount']
            ]);
        }
        break;
}

fclose($output);
