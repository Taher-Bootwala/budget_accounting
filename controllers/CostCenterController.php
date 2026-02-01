<?php
/**
 * ============================================================================
 * COST CENTERS CONTROLLER
 * ============================================================================
 * 
 * Manages Cost Centers (Departments/Projects for budget allocation)
 * 
 * WHAT IS A COST CENTER?
 * A cost center is a department, project, or category that has its own budget.
 * All transactions are tagged with a cost center for spending analysis.
 * 
 * EXAMPLES:
 * - R&D Department     : Budget for research & development
 * - IT Infrastructure  : Budget for technology expenses
 * - Marketing          : Budget for marketing campaigns
 * - Operations         : Day-to-day operational expenses
 * 
 * INTEGRATION:
 * - Linked to budgets (each cost center can have multiple budget periods)
 * - Linked to transactions (tracks where money is being spent)
 * - Auto-assignment via Rules (AutoAnalyticalRules)
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

class CostCenterController
{

    /**
     * Get All Cost Centers
     * 
     * Retrieves all cost centers sorted alphabetically.
     * 
     * @return array Array of cost center records
     */
    public static function getAll()
    {
        return dbFetchAll("SELECT * FROM cost_centers ORDER BY name");
    }

    /**
     * Get Single Cost Center by ID
     * 
     * @param int $id Cost center ID
     * @return array|null Cost center record or null if not found
     */
    public static function getById($id)
    {
        return dbFetchOne("SELECT * FROM cost_centers WHERE id = ?", [$id]);
    }

    /**
     * Create New Cost Center
     * 
     * @param array $data Cost center data (name)
     * @return string ID of newly created cost center
     */
    public static function create($data)
    {
        $sql = "INSERT INTO cost_centers (name) VALUES (?)";
        return dbInsert($sql, [$data['name']]);
    }

    /**
     * Update Existing Cost Center
     * 
     * @param int   $id   Cost center ID to update
     * @param array $data Updated data (name)
     * @return int Number of affected rows
     */
    public static function update($id, $data)
    {
        $sql = "UPDATE cost_centers SET name = ? WHERE id = ?";
        return dbExecute($sql, [$data['name'], $id]);
    }

    /**
     * Delete Cost Center
     * 
     * @param int $id Cost center ID to delete
     * @return int Number of affected rows
     */
    public static function delete($id)
    {
        return dbExecute("DELETE FROM cost_centers WHERE id = ?", [$id]);
    }

    /**
     * Get All Cost Centers with Budget and Spending Info
     * 
     * Enhanced version that includes:
     * - Current active budget amount
     * - Actual spending from transactions
     * - Utilization percentage and health status
     * - Time elapsed in budget period
     * 
     * Used for the Cost Centers list page with budget metrics.
     * 
     * @return array Array of cost centers with calculated budget metrics
     */
    public static function getAllWithBudgetInfo()
    {
        // Get cost centers with their confirmed budgets for current period
        $sql = "
            SELECT 
                cc.*,
                b.id as budget_id,
                b.amount as budget_amount,
                b.start_date,
                b.end_date
            FROM cost_centers cc
            LEFT JOIN budgets b ON cc.id = b.cost_center_id 
                AND b.status = 'confirmed'
                AND b.start_date <= CURDATE() AND b.end_date >= CURDATE()
            ORDER BY cc.name
        ";

        $results = dbFetchAll($sql);

        // Calculate derived metrics for each cost center
        foreach ($results as &$row) {
            $budget = floatval($row['budget_amount'] ?? 0);
            
            // Calculate actual spend from posted documents (VendorBill = expense)
            $actualSpend = 0;
            if ($row['start_date'] && $row['end_date']) {
                $spendResult = dbFetchOne("
                    SELECT COALESCE(SUM(total_amount), 0) as total
                    FROM documents 
                    WHERE cost_center_id = :cc_id
                    AND doc_type = 'VendorBill'
                    AND status = 'posted'
                    AND DATE(created_at) BETWEEN :start_date AND :end_date
                ", [
                    'cc_id' => $row['id'],
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date']
                ]);
                $actualSpend = floatval($spendResult['total'] ?? 0);
            }
            
            $row['actual_spend'] = $actualSpend;
            $utilization = $budget > 0 ? ($actualSpend / $budget) * 100 : 0;
            $row['utilization'] = round($utilization, 2);
            $row['health'] = getBudgetHealth($utilization);
            $row['remaining'] = $budget - $actualSpend;

            // Calculate time elapsed if budget period is defined
            if ($row['start_date'] && $row['end_date']) {
                $row['time_elapsed'] = getTimeElapsedPercentage($row['start_date'], $row['end_date']);
            } else {
                $row['time_elapsed'] = 0;
            }
        }

        return $results;
    }
}

