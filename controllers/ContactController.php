<?php
/**
 * Contact Controller
 * Handles CRUD operations for contacts (customers and vendors)
 */

require_once __DIR__ . '/../config/db.php';

class ContactController
{
    /**
     * Get all contacts
     */
    public static function getAll($archived = 0)
    {
        $sql = "SELECT * FROM contacts WHERE archived = :archived ORDER BY name";
        return dbFetchAll($sql, ['archived' => $archived]);
    }

    /**
     * Get contacts by type
     */
    public static function getByType($type, $archived = 0)
    {
        $sql = "SELECT * FROM contacts WHERE type = :type AND archived = :archived ORDER BY name";
        return dbFetchAll($sql, ['type' => $type, 'archived' => $archived]);
    }

    /**
     * Get contact by ID
     */
    public static function getById($id)
    {
        $sql = "SELECT * FROM contacts WHERE id = :id";
        return dbFetchOne($sql, ['id' => $id]);
    }

    /**
     * Create new contact
     */
    public static function create($data)
    {
        $sql = "INSERT INTO contacts (name, type, tag_id) VALUES (:name, :type, :tag_id)";
        return dbExecute($sql, [
            'name' => $data['name'],
            'type' => $data['type'],
            'tag_id' => $data['tag_id'] ?? null
        ]);
    }

    /**
     * Update contact
     */
    public static function update($id, $data)
    {
        $sql = "UPDATE contacts SET name = :name, type = :type, tag_id = :tag_id WHERE id = :id";
        return dbExecute($sql, [
            'id' => $id,
            'name' => $data['name'],
            'type' => $data['type'],
            'tag_id' => $data['tag_id'] ?? null
        ]);
    }

    /**
     * Archive contact
     */
    public static function archive($id)
    {
        $sql = "UPDATE contacts SET archived = 1 WHERE id = :id";
        return dbExecute($sql, ['id' => $id]);
    }

    /**
     * Restore contact
     */
    public static function restore($id)
    {
        $sql = "UPDATE contacts SET archived = 0 WHERE id = :id";
        return dbExecute($sql, ['id' => $id]);
    }

    /**
     * Delete contact
     */
    public static function delete($id)
    {
        $sql = "DELETE FROM contacts WHERE id = :id";
        return dbExecute($sql, ['id' => $id]);
    }

    /**
     * Get customers
     */
    public static function getCustomers($archived = 0)
    {
        return self::getByType('customer', $archived);
    }

    /**
     * Get vendors
     */
    public static function getVendors($archived = 0)
    {
        return self::getByType('vendor', $archived);
    }
}
