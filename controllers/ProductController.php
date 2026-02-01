<?php
/**
 * ============================================================================
 * PRODUCTS CONTROLLER
 * ============================================================================
 * 
 * Manages Product/Service Catalog for the furniture business
 * 
 * PRODUCT STRUCTURE:
 * - name     : Product name (e.g., "Teak Wood Dining Table")
 * - category : Product category (e.g., "Tables", "Chairs", "Sofas")
 * - price    : Standard selling price
 * 
 * USAGE:
 * - Selected when adding line items to documents
 * - Categories used for auto cost center assignment
 * - Price auto-populates on document lines
 * 
 * AUTO-ANALYTICAL RULES:
 * - Products/categories can be linked to cost centers via rules
 * - When a product is added to a PO, the cost center is auto-assigned
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';

class ProductController
{

    /**
     * Get All Products
     * 
     * Retrieves complete product catalog sorted by name.
     * 
     * @return array Array of product records
     */
    public static function getAll()
    {
        return dbFetchAll("SELECT * FROM products ORDER BY name");
    }

    /**
     * Get Single Product by ID
     * 
     * @param int $id Product ID
     * @return array|null Product record or null if not found
     */
    public static function getById($id)
    {
        return dbFetchOne("SELECT * FROM products WHERE id = ?", [$id]);
    }

    /**
     * Create New Product
     * 
     * Adds a new product to the catalog.
     * 
     * @param array $data Product data (name, category, price)
     * @return string ID of newly created product
     */
    public static function create($data)
    {
        $sql = "INSERT INTO products (name, category, price) VALUES (?, ?, ?)";
        return dbInsert($sql, [$data['name'], $data['category'], $data['price']]);
    }

    /**
     * Update Existing Product
     * 
     * @param int   $id   Product ID to update
     * @param array $data Updated product data (name, category, price)
     * @return int Number of affected rows
     */
    public static function update($id, $data)
    {
        $sql = "UPDATE products SET name = ?, category = ?, price = ? WHERE id = ?";
        return dbExecute($sql, [$data['name'], $data['category'], $data['price'], $id]);
    }

    /**
     * Delete Product
     * 
     * @param int $id Product ID to delete
     * @return int Number of affected rows
     */
    public static function delete($id)
    {
        return dbExecute("DELETE FROM products WHERE id = ?", [$id]);
    }

    /**
     * Get Distinct Product Categories
     * 
     * Returns unique category names for dropdown menus
     * and auto-analytical rule configuration.
     * 
     * @return array Array of category records
     */
    public static function getCategories()
    {
        return dbFetchAll("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category");
    }
}

