<?php
/**
 * Budget Engine
 * Core analytics and reporting engine for budget management
 * Provides dashboard KPIs, trends, and financial analysis
 */

require_once __DIR__ . '/../config/db.php';

class BudgetEngine
{
    /**
     * Get dashboard KPIs for a given timeframe
     */
    public static function getDashboardKPIs($timeframe = 'month')
    {
        // Total budget from confirmed budgets
        $totalBudget = dbFetchValue("
            SELECT COALESCE(SUM(amount), 0) 
            FROM budgets 
            WHERE status = 'confirmed' 
            AND CURDATE() BETWEEN start_date AND end_date
        ") ?: 0;

        // Get date range based on timeframe
        $dateStart = match($timeframe) {
            'week' => date('Y-m-d', strtotime('-7 days')),
            'year' => date('Y-m-d', strtotime('-1 year')),
            default => date('Y-m-d', strtotime('-1 month'))
        };

        // Total actual spending from posted documents
        $totalActual = dbFetchValue("
            SELECT COALESCE(SUM(total_amount), 0) 
            FROM documents 
            WHERE status = 'posted' 
            AND doc_type IN ('PO', 'VendorBill')
            AND created_at >= :date_start
        ", ['date_start' => $dateStart]) ?: 0;

        // Calculate utilization
        $utilization = $totalBudget > 0 ? round(($totalActual / $totalBudget) * 100, 1) : 0;

        // Determine health status
        $health = self::getHealthStatus($utilization);
        
        $remaining = $totalBudget - $totalActual;

        return [
            'total_budget' => $totalBudget,
            'total_actual' => $totalActual,
            'remaining' => max(0, $remaining),
            'total_remaining' => $remaining,
            'utilization' => $utilization,
            'health' => $health,
            'timeframe' => $timeframe
        ];
    }

    /**
     * Get spending trend data for charts
     */
    public static function getSpendingTrend($timeframe = 'month')
    {
        $labels = [];
        $data = [];

        switch ($timeframe) {
            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-{$i} days"));
                    $dayName = date('D', strtotime($date));
                    $labels[] = $dayName;
                    
                    $amount = dbFetchValue("
                        SELECT COALESCE(SUM(total_amount), 0) 
                        FROM documents 
                        WHERE DATE(created_at) = :date 
                        AND status = 'posted'
                        AND doc_type IN ('PO', 'VendorBill')
                    ", ['date' => $date]) ?: 0;
                    $data[] = $amount;
                }
                break;

            case 'year':
                for ($i = 11; $i >= 0; $i--) {
                    $month = date('Y-m', strtotime("-{$i} months"));
                    $monthName = date('M', strtotime($month . '-01'));
                    $labels[] = $monthName;
                    
                    $amount = dbFetchValue("
                        SELECT COALESCE(SUM(total_amount), 0) 
                        FROM documents 
                        WHERE DATE_FORMAT(created_at, '%Y-%m') = :month
                        AND status = 'posted'
                        AND doc_type IN ('PO', 'VendorBill')
                    ", ['month' => $month]) ?: 0;
                    $data[] = $amount;
                }
                break;

            default: // month
                for ($i = 29; $i >= 0; $i -= 5) {
                    $date = date('Y-m-d', strtotime("-{$i} days"));
                    $labels[] = date('M d', strtotime($date));
                    
                    $amount = dbFetchValue("
                        SELECT COALESCE(SUM(total_amount), 0) 
                        FROM documents 
                        WHERE DATE(created_at) BETWEEN :start AND :end
                        AND status = 'posted'
                        AND doc_type IN ('PO', 'VendorBill')
                    ", [
                        'start' => date('Y-m-d', strtotime("-{$i} days")),
                        'end' => date('Y-m-d', strtotime("-" . max(0, $i - 4) . " days"))
                    ]) ?: 0;
                    $data[] = $amount;
                }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Get transaction volume trend
     */
    public static function getTransactionVolumeTrend($timeframe = 'month')
    {
        $labels = [];
        $data = [];

        switch ($timeframe) {
            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-{$i} days"));
                    $labels[] = date('D', strtotime($date));
                    
                    $count = dbFetchValue("
                        SELECT COUNT(*) 
                        FROM documents 
                        WHERE DATE(created_at) = :date
                    ", ['date' => $date]) ?: 0;
                    $data[] = $count;
                }
                break;

            case 'year':
                for ($i = 11; $i >= 0; $i--) {
                    $month = date('Y-m', strtotime("-{$i} months"));
                    $labels[] = date('M', strtotime($month . '-01'));
                    
                    $count = dbFetchValue("
                        SELECT COUNT(*) 
                        FROM documents 
                        WHERE DATE_FORMAT(created_at, '%Y-%m') = :month
                    ", ['month' => $month]) ?: 0;
                    $data[] = $count;
                }
                break;

            default: // month
                for ($i = 29; $i >= 0; $i -= 5) {
                    $date = date('Y-m-d', strtotime("-{$i} days"));
                    $labels[] = date('M d', strtotime($date));
                    
                    $count = dbFetchValue("
                        SELECT COUNT(*) 
                        FROM documents 
                        WHERE DATE(created_at) BETWEEN :start AND :end
                    ", [
                        'start' => date('Y-m-d', strtotime("-{$i} days")),
                        'end' => date('Y-m-d', strtotime("-" . max(0, $i - 4) . " days"))
                    ]) ?: 0;
                    $data[] = $count;
                }
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Get cost center breakdown
     */
    public static function getCostCenterBreakdown($timeframe = 'month')
    {
        $sql = "
            SELECT 
                cc.id,
                cc.name,
                COALESCE(b.amount, 0) AS budget,
                COALESCE(SUM(dl.total), 0) AS actual
            FROM cost_centers cc
            LEFT JOIN budgets b ON cc.id = b.cost_center_id AND b.status = 'active'
            LEFT JOIN document_lines dl ON cc.id = dl.cost_center_id
            LEFT JOIN documents d ON dl.document_id = d.id AND d.status = 'posted'
            GROUP BY cc.id, cc.name, b.amount
            ORDER BY actual DESC
        ";
        
        return dbFetchAll($sql);
    }

    /**
     * Find matching cost center using auto analytical models
     */
    public static function findMatchingCostCenter($productId, $contactId)
    {
        require_once __DIR__ . '/AnalyticalModelController.php';
        
        $match = AnalyticalModelController::findBestMatch($productId, $contactId);
        
        if ($match) {
            return $match['cost_center_id'];
        }
        
        return null;
    }

    /**
     * Get health status based on utilization percentage
     */
    private static function getHealthStatus($utilization)
    {
        if ($utilization <= 70) {
            return ['status' => 'Healthy', 'color' => 'success'];
        } elseif ($utilization <= 90) {
            return ['status' => 'Caution', 'color' => 'warning'];
        } else {
            return ['status' => 'Critical', 'color' => 'danger'];
        }
    }

    /**
     * Get SQL date condition for timeframe
     */
    private static function getDateCondition($timeframe)
    {
        switch ($timeframe) {
            case 'week':
                return "d.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case 'year':
                return "d.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            default: // month
                return "d.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        }
    }

    /**
     * Get budget vs actual summary
     */
    public static function getBudgetVsActualSummary()
    {
        $sql = "
            SELECT 
                cc.id AS cost_center_id,
                cc.name AS cost_center_name,
                COALESCE(b.amount, 0) AS budget_amount,
                COALESCE((
                    SELECT SUM(dl.total)
                    FROM document_lines dl
                    JOIN documents d ON dl.document_id = d.id
                    WHERE dl.cost_center_id = cc.id
                    AND d.status = 'posted'
                    AND d.doc_type IN ('PO', 'VendorBill')
                ), 0) AS actual_amount
            FROM cost_centers cc
            LEFT JOIN budgets b ON cc.id = b.cost_center_id AND b.status = 'active'
            ORDER BY cc.name
        ";
        
        $results = dbFetchAll($sql);
        
        foreach ($results as &$row) {
            $row['variance'] = $row['budget_amount'] - $row['actual_amount'];
            $row['utilization'] = $row['budget_amount'] > 0 
                ? round(($row['actual_amount'] / $row['budget_amount']) * 100, 1) 
                : 0;
        }
        
        return $results;
    }

    /**
     * Get recent alerts/notifications
     */
    public static function getAlerts()
    {
        $alerts = [];
        
        // Check for over-budget cost centers
        $summary = self::getBudgetVsActualSummary();
        foreach ($summary as $item) {
            if ($item['utilization'] > 100) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => $item['cost_center_name'] . ' is over budget by ' . formatCurrency($item['actual_amount'] - $item['budget_amount']),
                    'icon' => 'ri-error-warning-line'
                ];
            } elseif ($item['utilization'] > 90) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => $item['cost_center_name'] . ' is at ' . $item['utilization'] . '% utilization',
                    'icon' => 'ri-alert-line'
                ];
            }
        }
        
        // Check for pending approvals
        $pendingCount = dbFetchValue("SELECT COUNT(*) FROM documents WHERE status = 'draft'") ?: 0;
        if ($pendingCount > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => $pendingCount . ' document(s) pending approval',
                'icon' => 'ri-file-list-line'
            ];
        }
        
        return $alerts;
    }

    /**
     * Get cost center drilldown data for detailed view
     */
    public static function getCostCenterDrilldown($costCenterId)
    {
        require_once __DIR__ . '/CostCenterController.php';
        
        // Get cost center info
        $costCenter = CostCenterController::getById($costCenterId);
        if (!$costCenter) {
            return [
                'cost_center' => ['name' => 'Unknown', 'description' => ''],
                'budget' => null,
                'summary' => [
                    'budget_amount' => 0,
                    'actual_spend' => 0,
                    'remaining' => 0,
                    'utilization' => 0,
                    'health' => getBudgetHealth(0)
                ],
                'transactions' => [],
                'documents' => []
            ];
        }
        
        // Get confirmed budget for this cost center (current period)
        $budget = dbFetchOne("
            SELECT * FROM budgets 
            WHERE cost_center_id = :cc_id 
            AND status = 'confirmed'
            AND start_date <= CURDATE() AND end_date >= CURDATE()
            ORDER BY created_at DESC
            LIMIT 1
        ", ['cc_id' => $costCenterId]);
        
        // Calculate actual spend from posted documents
        $actualSpend = 0;
        $startDate = $budget ? $budget['start_date'] : date('Y-01-01');
        $endDate = $budget ? $budget['end_date'] : date('Y-12-31');
        
        $spendResult = dbFetchOne("
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM documents 
            WHERE cost_center_id = :cc_id
            AND doc_type = 'VendorBill'
            AND status = 'posted'
            AND DATE(created_at) BETWEEN :start_date AND :end_date
        ", [
            'cc_id' => $costCenterId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        $actualSpend = floatval($spendResult['total'] ?? 0);
        
        $budgetAmount = floatval($budget['amount'] ?? 0);
        $utilization = $budgetAmount > 0 ? round(($actualSpend / $budgetAmount) * 100, 2) : 0;
        
        // Get recent transactions (documents)
        $transactions = dbFetchAll("
            SELECT 
                d.id as document_id,
                CONCAT(d.doc_type, '-', d.id) as document_number,
                d.created_at as transaction_date,
                d.total_amount as amount
            FROM documents d
            WHERE d.cost_center_id = :cc_id
            AND d.status = 'posted'
            ORDER BY d.created_at DESC
            LIMIT 20
        ", ['cc_id' => $costCenterId]);
        
        // Get related documents
        $documents = dbFetchAll("
            SELECT 
                d.id,
                d.doc_type,
                d.status,
                d.total_amount,
                CONCAT(d.doc_type, '-', d.id) as document_number,
                c.name as contact_name
            FROM documents d
            LEFT JOIN contacts c ON d.contact_id = c.id
            WHERE d.cost_center_id = :cc_id
            ORDER BY d.created_at DESC
            LIMIT 20
        ", ['cc_id' => $costCenterId]);
        
        return [
            'cost_center' => $costCenter,
            'budget' => $budget,
            'summary' => [
                'budget_amount' => $budgetAmount,
                'actual_spend' => $actualSpend,
                'remaining' => $budgetAmount - $actualSpend,
                'utilization' => $utilization,
                'health' => getBudgetHealth($utilization)
            ],
            'transactions' => $transactions,
            'documents' => $documents
        ];
    }

    /**
     * Get Budget vs Actual data for reports
     */
    public static function getBudgetVsActual()
    {
        require_once __DIR__ . '/CostCenterController.php';
        
        $costCenters = CostCenterController::getAll();
        $results = [];
        
        foreach ($costCenters as $cc) {
            // Get confirmed budget for this cost center
            $budget = dbFetchOne("
                SELECT * FROM budgets 
                WHERE cost_center_id = :cc_id 
                AND status = 'confirmed'
                AND start_date <= CURDATE() AND end_date >= CURDATE()
                ORDER BY created_at DESC
                LIMIT 1
            ", ['cc_id' => $cc['id']]);
            
            $budgetAmount = floatval($budget['amount'] ?? 0);
            $startDate = $budget ? $budget['start_date'] : date('Y-01-01');
            $endDate = $budget ? $budget['end_date'] : date('Y-12-31');
            
            // Calculate actual spend
            $spendResult = dbFetchOne("
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM documents 
                WHERE cost_center_id = :cc_id
                AND doc_type = 'VendorBill'
                AND status = 'posted'
                AND DATE(created_at) BETWEEN :start_date AND :end_date
            ", [
                'cc_id' => $cc['id'],
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            $actualSpend = floatval($spendResult['total'] ?? 0);
            
            $utilization = $budgetAmount > 0 ? round(($actualSpend / $budgetAmount) * 100, 1) : 0;
            $health = getBudgetHealth($utilization);
            $timeElapsed = $budget ? getTimeElapsedPercentage($startDate, $endDate) : 0;
            
            $results[] = [
                'cost_center_id' => $cc['id'],
                'cost_center_name' => $cc['name'],
                'budget_amount' => $budgetAmount,
                'actual_spend' => $actualSpend,
                'remaining' => $budgetAmount - $actualSpend,
                'utilization' => $utilization,
                'time_elapsed' => round($timeElapsed, 1),
                'health_status' => $health['status'],
                'health_color' => $health['color']
            ];
        }
        
        return $results;
    }

    /**
     * Get chart data for Budget vs Actual report
     */
    public static function getChartData()
    {
        $data = self::getBudgetVsActual();
        
        $labels = [];
        $budget = [];
        $actual = [];
        
        foreach ($data as $row) {
            $labels[] = $row['cost_center_name'];
            $budget[] = $row['budget_amount'];
            $actual[] = $row['actual_spend'];
        }
        
        return [
            'labels' => $labels,
            'budget' => $budget,
            'actual' => $actual
        ];
    }
}
