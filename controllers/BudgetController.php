<?php
/**
 * Budget Controller - Redesigned
 * Handles budgets with budget lines linked to analytical models
 */

require_once __DIR__ . '/../config/db.php';

class BudgetController
{
    /**
     * Get all budgets with stats
     */
    public static function getAll($status = null, $month = null)
    {
        $params = [];
        $sql = "
            SELECT b.*, 
                   (SELECT COUNT(*) FROM budget_lines WHERE budget_id = b.id) AS line_count
            FROM budgets b
            WHERE 1=1
        ";
        
        if ($status) {
            $sql .= " AND b.status = :status";
            $params['status'] = $status;
        }
        
        if ($month) {
            // Filter by month (format: YYYY-MM)
            $sql .= " AND DATE_FORMAT(b.start_date, '%Y-%m') = :month";
            $params['month'] = $month;
        }
        
        $sql .= " ORDER BY b.start_date DESC, b.created_at DESC";
        return dbFetchAll($sql, $params);
    }

    /**
     * Get distinct months from budgets for filter
     */
    public static function getAvailableMonths()
    {
        return dbFetchAll("
            SELECT DISTINCT DATE_FORMAT(start_date, '%Y-%m') as month_value,
                   DATE_FORMAT(start_date, '%M %Y') as month_label
            FROM budgets
            ORDER BY start_date DESC
        ");
    }

    /**
     * Get budget by ID with all lines
     */
    public static function getById($id)
    {
        $budget = dbFetchOne("
            SELECT b.*, rb.name as revised_from_name
            FROM budgets b
            LEFT JOIN budgets rb ON b.revised_from_id = rb.id
            WHERE b.id = :id
        ", ['id' => $id]);
        
        if ($budget) {
            $budget['lines'] = self::getLines($id);
        }
        
        return $budget;
    }

    /**
     * Get budget lines with analytical model details
     */
    public static function getLines($budgetId)
    {
        try {
            return dbFetchAll("
                SELECT bl.*, 
                       aam.name AS analytical_name,
                       cc.name AS cost_center_name
                FROM budget_lines bl
                JOIN auto_analytical_models aam ON bl.analytical_model_id = aam.id
                LEFT JOIN cost_centers cc ON aam.cost_center_id = cc.id
                WHERE bl.budget_id = :budget_id
                ORDER BY bl.type, aam.name
            ", ['budget_id' => $budgetId]);
        } catch (Exception $e) {
            // Table doesn't exist yet - migration not run
            return [];
        }
    }


    /**
     * Create new budget
     */
    public static function create($data)
    {
        $sql = "
            INSERT INTO budgets (name, cost_center_id, amount, start_date, end_date, status)
            VALUES (:name, :cost_center_id, :amount, :start_date, :end_date, :status)
        ";
        return dbInsert($sql, [
            'name' => $data['name'],
            'cost_center_id' => $data['cost_center_id'] ?? 1, // Default for backward compatibility
            'amount' => $data['amount'] ?? 0,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'] ?? 'draft'
        ]);
    }

    /**
     * Update budget
     */
    public static function update($id, $data)
    {
        $sql = "
            UPDATE budgets SET
                name = :name,
                start_date = :start_date,
                end_date = :end_date,
                status = :status
            WHERE id = :id
        ";
        return dbExecute($sql, [
            'id' => $id,
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'] ?? 'draft'
        ]);
    }

    /**
     * Update status only
     */
    public static function updateStatus($id, $status)
    {
        $sql = "UPDATE budgets SET status = :status WHERE id = :id";
        return dbExecute($sql, ['id' => $id, 'status' => $status]);
    }

    /**
     * Delete budget
     */
    public static function delete($id)
    {
        $sql = "DELETE FROM budgets WHERE id = :id";
        return dbExecute($sql, ['id' => $id]);
    }

    /**
     * Add budget line
     */
    public static function addLine($budgetId, $data)
    {
        try {
            $sql = "
                INSERT INTO budget_lines (budget_id, analytical_model_id, type, budgeted_amount)
                VALUES (:budget_id, :analytical_model_id, :type, :budgeted_amount)
            ";
            return dbExecute($sql, [
                'budget_id' => $budgetId,
                'analytical_model_id' => $data['analytical_model_id'],
                'type' => $data['type'],
                'budgeted_amount' => $data['budgeted_amount']
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Update budget line
     */
    public static function updateLine($lineId, $data)
    {
        $sql = "
            UPDATE budget_lines SET
                analytical_model_id = :analytical_model_id,
                type = :type,
                budgeted_amount = :budgeted_amount
            WHERE id = :id
        ";
        return dbExecute($sql, [
            'id' => $lineId,
            'analytical_model_id' => $data['analytical_model_id'],
            'type' => $data['type'],
            'budgeted_amount' => $data['budgeted_amount']
        ]);
    }

    /**
     * Delete budget line
     */
    public static function deleteLine($lineId)
    {
        return dbExecute("DELETE FROM budget_lines WHERE id = :id", ['id' => $lineId]);
    }

    /**
     * Get achieved amount for a budget line
     * For Income: Sum of Sales Invoice lines matching the analytical model's cost center in the budget period
     * For Expense: Sum of Vendor Bill lines matching the analytical model's cost center in the budget period
     */
    public static function getAchievedAmount($budgetLine, $startDate, $endDate)
    {
        // Get the analytical model's cost center
        $model = dbFetchOne("SELECT cost_center_id FROM auto_analytical_models WHERE id = :id", 
            ['id' => $budgetLine['analytical_model_id']]);
        
        if (!$model) return 0;
        
        $costCenterId = $model['cost_center_id'];
        
        if ($budgetLine['type'] === 'income') {
            // Search in Sales Invoices (CustomerInvoice)
            $sql = "
                SELECT COALESCE(SUM(dl.line_total), 0) as total
                FROM documents d
                JOIN document_lines dl ON d.id = dl.document_id
                WHERE d.doc_type = 'CustomerInvoice'
                AND d.status = 'posted'
                AND d.cost_center_id = :cost_center_id
                AND DATE(d.created_at) BETWEEN :start_date AND :end_date
            ";
        } else {
            // Search in Vendor Bills
            $sql = "
                SELECT COALESCE(SUM(dl.line_total), 0) as total
                FROM documents d
                JOIN document_lines dl ON d.id = dl.document_id
                WHERE d.doc_type = 'VendorBill'
                AND d.status = 'posted'
                AND d.cost_center_id = :cost_center_id
                AND DATE(d.created_at) BETWEEN :start_date AND :end_date
            ";
        }
        
        $result = dbFetchOne($sql, [
            'cost_center_id' => $costCenterId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $result ? floatval($result['total']) : 0;
    }

    /**
     * Get all lines with computed fields for a budget
     */
    public static function getLinesWithComputed($budgetId)
    {
        $budget = dbFetchOne("SELECT * FROM budgets WHERE id = :id", ['id' => $budgetId]);
        if (!$budget) return [];
        
        $lines = self::getLines($budgetId);
        $isConfirmed = in_array($budget['status'], ['confirmed', 'revised']);
        
        foreach ($lines as &$line) {
            $line['achieved_amount'] = 0;
            $line['achieved_percent'] = 0;
            $line['amount_to_achieve'] = null;
            
            if ($isConfirmed) {
                $achieved = self::getAchievedAmount($line, $budget['start_date'], $budget['end_date']);
                $line['achieved_amount'] = $achieved;
                
                $budgeted = floatval($line['budgeted_amount']);
                if ($budgeted > 0) {
                    $line['achieved_percent'] = round(($achieved / $budgeted) * 100, 2);
                }
                
                // Amount to achieve only for Income type
                if ($line['type'] === 'income') {
                    $line['amount_to_achieve'] = max(0, $budgeted - $achieved);
                }
            }
        }
        
        return $lines;
    }

    /**
     * Create a revised version of a budget
     */
    public static function revise($originalBudgetId)
    {
        $original = self::getById($originalBudgetId);
        if (!$original) return false;
        
        // Mark original as revised
        self::updateStatus($originalBudgetId, 'revised');
        
        // Create new budget with reference - keep original name (user can edit it)
        $sql = "
            INSERT INTO budgets (name, cost_center_id, amount, start_date, end_date, status, revised_from_id)
            VALUES (:name, :cost_center_id, :amount, :start_date, :end_date, 'draft', :revised_from_id)
        ";
        $newBudgetId = dbInsert($sql, [
            'name' => $original['name'],
            'cost_center_id' => $original['cost_center_id'],
            'amount' => $original['amount'],
            'start_date' => $original['start_date'],
            'end_date' => $original['end_date'],
            'revised_from_id' => $originalBudgetId
        ]);
        
        // Copy all lines to new budget
        if (!empty($original['lines'])) {
            foreach ($original['lines'] as $line) {
                self::addLine($newBudgetId, [
                    'analytical_model_id' => $line['analytical_model_id'],
                    'type' => $line['type'],
                    'budgeted_amount' => $line['budgeted_amount']
                ]);
            }
        }
        
        return $newBudgetId;
    }

    /**
     * Get documents (invoices/bills) for a specific analytical line
     */
    public static function getDocumentsForLine($budgetLine, $startDate, $endDate)
    {
        $model = dbFetchOne("SELECT cost_center_id FROM auto_analytical_models WHERE id = :id", 
            ['id' => $budgetLine['analytical_model_id']]);
        
        if (!$model) return [];
        
        $docType = $budgetLine['type'] === 'income' ? 'CustomerInvoice' : 'VendorBill';
        
        return dbFetchAll("
            SELECT d.id, d.doc_type, d.total_amount, d.created_at, c.name as contact_name
            FROM documents d
            JOIN contacts c ON d.contact_id = c.id
            WHERE d.doc_type = :doc_type
            AND d.status = 'posted'
            AND d.cost_center_id = :cost_center_id
            AND DATE(d.created_at) BETWEEN :start_date AND :end_date
            ORDER BY d.created_at DESC
        ", [
            'doc_type' => $docType,
            'cost_center_id' => $model['cost_center_id'],
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    /**
     * Get total budget amount
     */
    public static function getTotalBudget()
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) AS total FROM budgets WHERE status IN ('active', 'confirmed')";
        return dbFetchValue($sql);
    }

    /**
     * Get budget revisions history
     */
    public static function getRevisions($budgetId)
    {
        return dbFetchAll("
            SELECT * FROM budget_revisions 
            WHERE budget_id = :budget_id 
            ORDER BY revised_at DESC
        ", ['budget_id' => $budgetId]);
    }
}
