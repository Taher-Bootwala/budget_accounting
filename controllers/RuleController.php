<?php


require_once __DIR__ . '/../config/db.php';

class RuleController
{

    /**
     * Get All Auto-Analytical Rules
     * 
     * Retrieves all rules with their linked cost center names.
     * Sorted by rule type (category/product) then value.
     * 
     * @return array Array of rule records with cost_center_name
     */
    public static function getAll()
    {
        $sql = "
            SELECT r.*, cc.name as cost_center_name
            FROM auto_analytical_rules r
            JOIN cost_centers cc ON r.cost_center_id = cc.id
            ORDER BY r.rule_type, r.rule_value
        ";
        return dbFetchAll($sql);
    }

    /**
     * Get Single Rule by ID
     * 
     * @param int $id Rule ID
     * @return array|null Rule record or null if not found
     */
    public static function getById($id)
    {
        return dbFetchOne("SELECT * FROM auto_analytical_rules WHERE id = ?", [$id]);
    }

    /**
     * Create New Auto-Analytical Rule
     * 
     * Creates a mapping between a product/category and a cost center.
     * 
     * @param array $data Rule data (rule_type, rule_value, cost_center_id)
     *                    rule_type: 'product' or 'category'
     *                    rule_value: product ID or category name
     * @return string ID of newly created rule
     */
    public static function create($data)
    {
        $sql = "INSERT INTO auto_analytical_rules (rule_type, rule_value, cost_center_id) VALUES (?, ?, ?)";
        return dbInsert($sql, [$data['rule_type'], $data['rule_value'], $data['cost_center_id']]);
    }

    /**
     * Update Existing Rule
     * 
     * @param int   $id   Rule ID to update
     * @param array $data Updated rule data
     * @return int Number of affected rows
     */
    public static function update($id, $data)
    {
        $sql = "UPDATE auto_analytical_rules SET rule_type = ?, rule_value = ?, cost_center_id = ? WHERE id = ?";
        return dbExecute($sql, [$data['rule_type'], $data['rule_value'], $data['cost_center_id'], $id]);
    }

    /**
     * Delete Rule
     * 
     * @param int $id Rule ID to delete
     * @return int Number of affected rows
     */
    public static function delete($id)
    {
        return dbExecute("DELETE FROM auto_analytical_rules WHERE id = ?", [$id]);
    }

    /**
     * Find Matching Cost Center for a Product
     * 
     * Uses the rule priority system to find the appropriate cost center:
     * 1. First checks for product-specific rules
     * 2. If none found, checks for category-based rules
     * 3. Returns null if no matching rule exists
     * 
     * This is called when posting documents to auto-assign cost centers
     * to transaction records.
     * 
     * @param int $productId Product ID to find cost center for
     * @return array|null ['id' => cost_center_id, 'name' => cost_center_name] or null
     */
    public static function findCostCenter($productId)
    {
        // Priority 1: Check by product ID (most specific)
        $rule = dbFetchOne("
            SELECT r.*, cc.name as cost_center_name
            FROM auto_analytical_rules r
            JOIN cost_centers cc ON r.cost_center_id = cc.id
            WHERE r.rule_type = 'product' AND r.rule_value = ?
        ", [$productId]);

        if ($rule) {
            return ['id' => $rule['cost_center_id'], 'name' => $rule['cost_center_name']];
        }

        // Priority 2: Check by category (broader rule)
        $product = dbFetchOne("SELECT category FROM products WHERE id = ?", [$productId]);
        if ($product && $product['category']) {
            $rule = dbFetchOne("
                SELECT r.*, cc.name as cost_center_name
                FROM auto_analytical_rules r
                JOIN cost_centers cc ON r.cost_center_id = cc.id
                WHERE r.rule_type = 'category' AND r.rule_value = ?
            ", [$product['category']]);

            if ($rule) {
                return ['id' => $rule['cost_center_id'], 'name' => $rule['cost_center_name']];
            }
        }

        // No matching rule found
        return null;
    }
}

