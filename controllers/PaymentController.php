<?php
/**
 * ============================================================================
 * PAYMENT CONTROLLER
 * ============================================================================
 * 
 * Manages Payment Processing with Razorpay Integration (Simulated)
 * 
 * PAYMENT FLOW:
 * 1. User clicks "Pay" on a document (Invoice/Bill)
 * 2. Payment modal opens with amount and method selection
 * 3. Razorpay checkout simulated (generates fake razorpay_payment_id)
 * 4. Payment record created and linked to document
 * 5. Document status updated to "paid" if fully paid
 * 
 * PAYMENT METHODS:
 * - card   : Credit/Debit Card via Razorpay
 * - upi    : UPI Payment
 * - bank   : Net Banking
 * - cash   : Cash payment (manually recorded)
 * 
 * DATABASE:
 * - payments table stores all payment records
 * - Linked to documents via document_id
 * - Tracks paid_amount, payment_method, razorpay_payment_id
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

class PaymentController
{

    /**
     * Get All Payments with Document and Contact Info
     * 
     * Retrieves complete payment history with linked document
     * and contact details. Sorted by most recent first.
     * 
     * @return array Array of payment records with document_number, contact_name
     */
    public static function getAll()
    {
        return dbFetchAll("
            SELECT p.*, 
                   CONCAT(d.doc_type, '-', LPAD(d.id, 4, '0')) as document_number, 
                   d.doc_type,
                   d.total_amount as document_total,
                   c.name as contact_name
            FROM payments p
            JOIN documents d ON p.document_id = d.id
            LEFT JOIN contacts c ON d.contact_id = c.id
            ORDER BY p.payment_date DESC
        ");
    }

    /**
     * Get All Payments for a Specific Document
     * 
     * Used to show payment history on document detail pages.
     * 
     * @param int $documentId Document ID
     * @return array Array of payment records for this document
     */
    public static function getByDocumentId($documentId)
    {
        return dbFetchAll("SELECT * FROM payments WHERE document_id = ? ORDER BY payment_date DESC", [$documentId]);
    }

    /**
     * Create New Payment Record
     * 
     * Records a payment against a document.
     * Called after Razorpay checkout completion.
     * 
     * @param array $data Payment data:
     *                    - document_id: Document being paid
     *                    - paid_amount: Amount paid
     *                    - payment_method: 'card'|'upi'|'bank'|'cash'
     *                    - razorpay_payment_id: Payment gateway reference
     *                    - payment_date: Date of payment (defaults to today)
     * @return string ID of newly created payment
     */
    public static function create($data)
    {
        $sql = "INSERT INTO payments (document_id, paid_amount, payment_method, razorpay_payment_id, payment_date) 
                VALUES (?, ?, ?, ?, ?)";
        return dbInsert($sql, [
            $data['document_id'],
            $data['paid_amount'],
            $data['payment_method'] ?? 'other',
            $data['razorpay_payment_id'] ?? null,
            $data['payment_date'] ?? date('Y-m-d')
        ]);
    }

    /**
     * Get Total Amount Paid for a Document
     * 
     * Calculates sum of all payments made against a document.
     * Used to determine payment status (unpaid/partial/paid).
     * 
     * @param int $documentId Document ID
     * @return float Total amount paid (0 if no payments)
     */
    public static function getTotalPaidForDocument($documentId)
    {
        return dbFetchValue("SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE document_id = ?", [$documentId]);
    }
}

