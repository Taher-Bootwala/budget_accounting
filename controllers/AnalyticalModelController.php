<?php
/**
 * Analytical Model Controller
 * Handles priority-based automatic cost center allocation rules
 */

require_once __DIR__ . '/../config/db.php';

class AnalyticalModelController
{
    /**
     * Get all models with related data
     */
    public static function getAll($status = null)
    {
        $sql = "
            SELECT 
                aam.*,
                ct.name AS partner_tag_name,
                c.name AS partner_name,
                p.name AS product_name,
                cc.name AS cost_center_name
            FROM auto_analytical_models aam
            LEFT JOIN contact_tags ct ON aam.partner_tag_id = ct.id
            LEFT JOIN contacts c ON aam.partner_id = c.id
            LEFT JOIN products p ON aam.product_id = p.id
            LEFT JOIN cost_centers cc ON aam.cost_center_id = cc.id
        ";
        
        if ($status) {
            $sql .= " WHERE aam.status = :status";
            $sql .= " ORDER BY aam.created_at DESC";
            return dbFetchAll($sql, ['status' => $status]);
        }
        
        $sql .= " ORDER BY aam.created_at DESC";
        return dbFetchAll($sql);
    }

    /**
     * Get model by ID
     */
    public static function getById($id)
    {
        $sql = "
            SELECT 
                aam.*,
                ct.name AS partner_tag_name,
                c.name AS partner_name,
                p.name AS product_name,
                cc.name AS cost_center_name
            FROM auto_analytical_models aam
            LEFT JOIN contact_tags ct ON aam.partner_tag_id = ct.id
            LEFT JOIN contacts c ON aam.partner_id = c.id
            LEFT JOIN products p ON aam.product_id = p.id
            LEFT JOIN cost_centers cc ON aam.cost_center_id = cc.id
            WHERE aam.id = :id
        ";
        return dbFetchOne($sql, ['id' => $id]);
    }

    /**
     * Create new model
     */
    public static function create($data)
    {
        $sql = "
            INSERT INTO auto_analytical_models 
            (name, status, partner_tag_id, product_category, partner_id, product_id, cost_center_id)
            VALUES 
            (:name, :status, :partner_tag_id, :product_category, :partner_id, :product_id, :cost_center_id)
        ";
        return dbExecute($sql, [
            'name' => $data['name'],
            'status' => $data['status'] ?? 'draft',
            'partner_tag_id' => $data['partner_tag_id'] ?: null,
            'product_category' => $data['product_category'] ?: null,
            'partner_id' => $data['partner_id'] ?: null,
            'product_id' => $data['product_id'] ?: null,
            'cost_center_id' => $data['cost_center_id']
        ]);
    }

    /**
     * Update model
     */
    public static function update($id, $data)
    {
        $sql = "
            UPDATE auto_analytical_models SET
                name = :name,
                status = :status,
                partner_tag_id = :partner_tag_id,
                product_category = :product_category,
                partner_id = :partner_id,
                product_id = :product_id,
                cost_center_id = :cost_center_id
            WHERE id = :id
        ";
        return dbExecute($sql, [
            'id' => $id,
            'name' => $data['name'],
            'status' => $data['status'] ?? 'draft',
            'partner_tag_id' => $data['partner_tag_id'] ?: null,
            'product_category' => $data['product_category'] ?: null,
            'partner_id' => $data['partner_id'] ?: null,
            'product_id' => $data['product_id'] ?: null,
            'cost_center_id' => $data['cost_center_id']
        ]);
    }

    /**
     * Update status only
     */
    public static function updateStatus($id, $status)
    {
        $sql = "UPDATE auto_analytical_models SET status = :status WHERE id = :id";
        return dbExecute($sql, ['id' => $id, 'status' => $status]);
    }

    /**
     * Delete model
     */
    public static function delete($id)
    {
        $sql = "DELETE FROM auto_analytical_models WHERE id = :id";
        return dbExecute($sql, ['id' => $id]);
    }

    /**
     * Find best matching model for a transaction line
     * Priority: More fields matched = higher specificity = higher priority
     * 
     * @param int $productId - The product being transacted
     * @param int $contactId - The partner (customer/vendor)
     * @return array|null - Best matching model or null
     */
    public static function findBestMatch($productId, $contactId)
    {
        // Get product details
        $product = dbFetchOne("SELECT * FROM products WHERE id = :id", ['id' => $productId]);
        if (!$product) return null;

        // Get contact details with tag
        $contact = dbFetchOne("SELECT * FROM contacts WHERE id = :id", ['id' => $contactId]);
        if (!$contact) return null;

        // Get all confirmed models
        $models = self::getAll('confirmed');
        
        $bestMatch = null;
        $bestScore = 0;

        foreach ($models as $model) {
            $score = 0;
            $matches = true;

            // Check product match (most specific)
            if ($model['product_id']) {
                if ($model['product_id'] == $productId) {
                    $score += 4;
                } else {
                    $matches = false;
                }
            }

            // Check partner match
            if ($model['partner_id'] && $matches) {
                if ($model['partner_id'] == $contactId) {
                    $score += 3;
                } else {
                    $matches = false;
                }
            }

            // Check product category match
            if ($model['product_category'] && $matches) {
                if ($model['product_category'] === $product['category']) {
                    $score += 2;
                } else {
                    $matches = false;
                }
            }

            // Check partner tag match (most generic)
            if ($model['partner_tag_id'] && $matches) {
                if ($model['partner_tag_id'] == $contact['tag_id']) {
                    $score += 1;
                } else {
                    $matches = false;
                }
            }

            // If all specified criteria matched and score is better
            if ($matches && $score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $model;
            }
        }

        return $bestMatch;
    }

    /**
     * Get all contact tags
     */
    public static function getContactTags()
    {
        return dbFetchAll("SELECT * FROM contact_tags ORDER BY name");
    }

    /**
     * Get all product categories
     */
    public static function getProductCategories()
    {
        return dbFetchAll("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
    }
}
