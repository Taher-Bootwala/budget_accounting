<?php
/**
 * ============================================================================
 * DOCUMENT CONTROLLER
 * ============================================================================
 * 
 * Manages Purchase Orders, Sales Orders, Vendor Bills, and Customer Invoices
 * 
 * DOCUMENT TYPES:
 * - PO           : Purchase Order (buying from vendors)
 * - SO           : Sales Order (selling to customers)
 * - VendorBill   : Bill received from vendor (payable)
 * - CustomerInvoice : Invoice sent to customer (receivable)
 * 
 * DOCUMENT WORKFLOW:
 * 1. Draft → 2. Pending Approval → 3. Approved → 4. Paid/Closed
 * 
 * KEY FEATURES:
 * - Automatic document numbering (TYPE-0001 format)
 * - Line items with product linking
 * - Cost center assignment for budget tracking
 * - Payment status tracking
 * - Integration with BudgetEngine for spending analysis
 * 
 * DATABASE TABLES:
 * - documents: Header info (type, contact, total, status)
 * - document_lines: Line items with products and amounts
 * - payments: Payment records with Razorpay integration
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

class DocumentController
{

    /**
     * Get All Documents with Optional Filtering
     * 
     * Retrieves documents with contact info and payment status.
     * Supports filtering by document type(s) and status.
     * 
     * @param string|array|null $type   Filter by doc_type - single value or array of types
     * @param string|null $status Filter by status ('draft'|'posted'|'cancelled')
     * @return array Array of document records with contact and payment info
     */
    public static function getAll($type = null, $status = null)
    {
        $sql = "
            SELECT d.*, c.name as contact_name, c.type as contact_type,
                   CONCAT(d.doc_type, '-', LPAD(d.id, 4, '0')) as document_number,
                   (SELECT SUM(paid_amount) FROM payments WHERE document_id = d.id) as paid_amount
            FROM documents d
            LEFT JOIN contacts c ON d.contact_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if ($type) {
            if (is_array($type)) {
                $placeholders = implode(',', array_fill(0, count($type), '?'));
                $sql .= " AND d.doc_type IN ($placeholders)";
                $params = array_merge($params, $type);
            } else {
                $sql .= " AND d.doc_type = ?";
                $params[] = $type;
            }
        }
        if ($status) {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY d.created_at DESC";

        $results = dbFetchAll($sql, $params);

        // Calculate payment status for each document
        foreach ($results as &$row) {
            $row['payment_status'] = self::calculatePaymentStatus($row['total_amount'], $row['paid_amount']);
        }

        return $results;
    }

    /**
     * Get Single Document by ID with Full Details
     * 
     * Retrieves complete document with contact, lines, and payment info.
     * 
     * @param int $id Document ID to retrieve
     * @return array|null Document record with lines array, or null if not found
     */
    public static function getById($id)
    {
        $doc = dbFetchOne("
            SELECT d.*, c.name as contact_name, c.type as contact_type,
                   CONCAT(d.doc_type, '-', LPAD(d.id, 4, '0')) as document_number
            FROM documents d
            LEFT JOIN contacts c ON d.contact_id = c.id
            WHERE d.id = ?
        ", [$id]);

        if ($doc) {
            // Include line items and payment data
            $doc['lines'] = self::getLines($id);
            $doc['paid_amount'] = dbFetchValue("SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE document_id = ?", [$id]);
            $doc['payment_status'] = self::calculatePaymentStatus($doc['total_amount'], $doc['paid_amount']);
        }

        return $doc;
    }

    /**
     * Get Document Line Items
     * 
     * Retrieves all line items for a document with product info.
     * 
     * @param int $documentId Document ID to get lines for
     * @return array Array of line items with product name and category
     */
    public static function getLines($documentId)
    {
        return dbFetchAll("
            SELECT dl.*, p.name as product_name, p.category as product_category
            FROM document_lines dl
            JOIN products p ON dl.product_id = p.id
            WHERE dl.document_id = ?
        ", [$documentId]);
    }

    /**
     * Create New Document with Line Items
     * 
     * Creates a new document in 'draft' status with associated line items.
     * 
     * @param array $data  Document header data (doc_type, contact_id, cost_center_id, total_amount)
     * @param array $lines Array of line items (product_id, quantity, price, line_total)
     * @return int ID of the newly created document
     */
    public static function create($data, $lines)
    {
        $sql = "INSERT INTO documents (doc_type, contact_id, cost_center_id, total_amount, status) 
                VALUES (?, ?, ?, ?, 'draft')";
        $docId = dbInsert($sql, [
            $data['doc_type'],
            $data['contact_id'],
            $data['cost_center_id'] ?? null,
            $data['total_amount']
        ]);

        // Insert each line item
        foreach ($lines as $line) {
            dbInsert("INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) 
                      VALUES (?, ?, ?, ?, ?)",
                [$docId, $line['product_id'], $line['quantity'], $line['price'], $line['line_total']]
            );
        }

        return $docId;
    }

    /**
     * Update Existing Document and Lines
     * 
     * Updates document header and replaces all line items.
     * Note: Old lines are deleted and new ones inserted (full replacement).
     * 
     * @param int   $id    Document ID to update
     * @param array $data  Updated document data
     * @param array $lines New line items (replaces existing)
     * @return bool True on success
     */
    public static function update($id, $data, $lines)
    {
        // Update document header
        dbExecute(
            "UPDATE documents SET contact_id = ?, cost_center_id = ?, total_amount = ? WHERE id = ?",
            [$data['contact_id'], $data['cost_center_id'] ?? null, $data['total_amount'], $id]
        );

        // Delete old lines and insert new ones (full replacement)
        dbExecute("DELETE FROM document_lines WHERE document_id = ?", [$id]);

        foreach ($lines as $line) {
            dbInsert("INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) 
                      VALUES (?, ?, ?, ?, ?)",
                [$id, $line['product_id'], $line['quantity'], $line['price'], $line['line_total']]
            );
        }

        return true;
    }

    /**
     * Post Document (Change Status from Draft to Posted)
     * 
     * Finalizes a document and creates transaction records for budget tracking.
     * Uses auto-analytical rules to assign cost centers to each line item.
     * 
     * WORKFLOW:
     * 1. Verify document is in 'draft' status
     * 2. Update status to 'posted'
     * 3. Create transaction records for each line (links to budget tracking)
     * 4. If Sales Order (SO), automatically generate a Customer Invoice
     * 
     * @param int $id Document ID to post
     * @return bool True on success, false if document not found or already posted
     */
    public static function post($id)
    {
        $doc = self::getById($id);
        if (!$doc || $doc['status'] !== 'draft')
            return false;

        // Update status to posted
        dbExecute("UPDATE documents SET status = 'posted' WHERE id = ?", [$id]);

        // Create transactions for each line (for budget tracking)
        require_once __DIR__ . '/RuleController.php';

        foreach ($doc['lines'] as $line) {
            // Use auto-analytical rules to find appropriate cost center
            $costCenter = RuleController::findCostCenter($line['product_id']);
            $costCenterId = $costCenter ? $costCenter['id'] : 1; // Default to first cost center

            dbInsert("INSERT INTO transactions (document_id, cost_center_id, amount, transaction_date) 
                      VALUES (?, ?, ?, CURDATE())",
                [$id, $costCenterId, $line['line_total']]
            );
        }

        // AUTO-GENERATE INVOICE: If this is a Sales Order, create a Customer Invoice
        if ($doc['doc_type'] === 'SO') {
            // Create the Customer Invoice document
            $invoiceId = dbInsert(
                "INSERT INTO documents (doc_type, contact_id, cost_center_id, total_amount, status) 
                 VALUES ('CustomerInvoice', ?, ?, ?, 'posted')",
                [
                    $doc['contact_id'],
                    $doc['cost_center_id'] ?? null,
                    $doc['total_amount']
                ]
            );

            // Copy all line items from SO to Invoice
            foreach ($doc['lines'] as $line) {
                dbInsert(
                    "INSERT INTO document_lines (document_id, product_id, quantity, price, line_total) 
                     VALUES (?, ?, ?, ?, ?)",
                    [$invoiceId, $line['product_id'], $line['quantity'], $line['price'], $line['line_total']]
                );
            }

            // Create transactions for the invoice as well
            foreach ($doc['lines'] as $line) {
                $costCenter = RuleController::findCostCenter($line['product_id']);
                $costCenterId = $costCenter ? $costCenter['id'] : 1;

                dbInsert("INSERT INTO transactions (document_id, cost_center_id, amount, transaction_date) 
                          VALUES (?, ?, ?, CURDATE())",
                    [$invoiceId, $costCenterId, $line['line_total']]
                );
            }
        }

        return true;
    }

    /**
     * Cancel Document
     * 
     * Marks document as cancelled (soft delete).
     * 
     * @param int $id Document ID to cancel
     * @return int Number of affected rows
     */
    public static function cancel($id)
    {
        return dbExecute("UPDATE documents SET status = 'cancelled' WHERE id = ?", [$id]);
    }

    /**
     * Delete Document Permanently
     * 
     * Removes document, its lines, and related transactions.
     * WARNING: This is a hard delete - data cannot be recovered!
     * 
     * @param int $id Document ID to delete
     * @return int Number of affected rows
     */
    public static function delete($id)
    {
        // Delete related records first (foreign key constraints)
        dbExecute("DELETE FROM document_lines WHERE document_id = ?", [$id]);
        dbExecute("DELETE FROM transactions WHERE document_id = ?", [$id]);
        return dbExecute("DELETE FROM documents WHERE id = ?", [$id]);
    }

    /**
     * Calculate Payment Status
     * 
     * Determines if a document is unpaid, partially paid, or fully paid.
     * 
     * @param float $total Total document amount
     * @param float $paid  Total amount paid
     * @return string 'unpaid' | 'partial' | 'paid'
     */
    private static function calculatePaymentStatus($total, $paid)
    {
        $total = floatval($total);
        $paid = floatval($paid ?? 0);

        if ($paid <= 0)
            return 'unpaid';
        if ($paid >= $total)
            return 'paid';
        return 'partial';
    }

    /**
     * Get Count of Documents Pending Approval
     * 
     * Returns number of documents in 'draft' status for dashboard display.
     * 
     * @return int Count of pending documents
     */
    public static function getPendingApprovalsCount()
    {
        return (int) dbFetchValue("SELECT COUNT(*) FROM documents WHERE status = 'draft'");
    }

    /**
     * Search Documents by Number or Contact Name
     * 
     * Used for global search functionality.
     * 
     * @param string $query Search query
     * @return array Matching documents (max 10)
     */
    public static function search($query)
    {
        return dbFetchAll("
            SELECT d.*, c.name as contact_name,
                   CONCAT(d.doc_type, '-', LPAD(d.id, 4, '0')) as document_number
            FROM documents d
            LEFT JOIN contacts c ON d.contact_id = c.id
            WHERE CONCAT(d.doc_type, '-', LPAD(d.id, 4, '0')) LIKE ? OR c.name LIKE ?
            ORDER BY d.created_at DESC
            LIMIT 10
        ", ["%$query%", "%$query%"]);
    }
}

