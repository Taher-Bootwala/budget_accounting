<?php
/**
 * ============================================================================
 * PROCESS PAYMENT API (RAZORPAY SIMULATION)
 * ============================================================================
 * 
 * Records payment transactions against documents
 * 
 * ENDPOINT: POST /api/process_payment.php
 * 
 * REQUEST BODY (JSON):
 * {
 *   "document_id": 123,
 *   "amount": 5000.00,
 *   "method": "card|upi|bank|cash",
 *   "razorpay_payment_id": "pay_xyz123" (optional)
 * }
 * 
 * RESPONSE:
 * {
 *   "success": true|false,
 *   "message": "Payment recorded successfully|Error message"
 * }
 * 
 * SIMULATED FLOW:
 * In production, Razorpay checkout would generate real payment ID.
 * For hackathon demo, payment is simulated with fake IDs.
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../controllers/PaymentController.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$documentId = $input['document_id'] ?? null;
$amount = floatval($input['amount'] ?? 0);
$method = $input['method'] ?? 'card';
$razorpayId = $input['razorpay_payment_id'] ?? null;

if (!$documentId || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit;
}

try {
    PaymentController::create([
        'document_id' => $documentId,
        'paid_amount' => $amount,
        'payment_method' => $method,
        'razorpay_payment_id' => $razorpayId
    ]);

    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()]);
}
