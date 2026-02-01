<?php
// DEBUGGING SETUP
$logFile = __DIR__ . '/../debug_payment_log.txt';
function logStep($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

logStep("Request Started");

// Handle Fatal Errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR)) {
        global $logFile;
        file_put_contents($logFile, "FATAL ERROR: " . print_r($error, true) . "\n", FILE_APPEND);
        // Clean buffer and output JSON error
        @ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server Fatal Error: ' . $error['message']]);
        exit;
    }
});

error_reporting(E_ALL); // Enable error reporting for log, but we suppress display via ob
ini_set('display_errors', 0); // Do NOT display errors to output
ob_start(); // Buffer output

try {
    logStep("Loading dependencies...");
    require_once __DIR__ . '/../config/auth.php';
    require_once __DIR__ . '/../controllers/PaymentController.php';

    logStep("Dependencies loaded.");

    // Clean any previous output (like includes)
    ob_end_clean(); 
    header('Content-Type: application/json');

    $userId = getCurrentUserId(); 
    logStep("User ID: " . ($userId ? $userId : 'NULL'));

    if (!$userId) {
        throw new Exception("User not logged in");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    logStep("Input: " . print_r($input, true));

    if (!$input) {
        throw new Exception("Invalid JSON input");
    }

    $documentId = $input['document_id'] ?? null;
    $amount = $input['amount'] ?? null;
    $method = $input['method'] ?? 'card';

    if (!$documentId || !$amount) {
        throw new Exception("Missing document_id or amount");
    }

    logStep("Verifying access for Doc ID: $documentId");

    // Verify document belongs to user's contact (Security)
    $portalAccess = dbFetchOne("SELECT contact_id FROM portal_access WHERE user_id = ?", [$userId]);
    
    if (!$portalAccess) {
        throw new Exception("No portal access configured for user");
    }
    
    $doc = dbFetchOne("SELECT id FROM documents WHERE id = ? AND contact_id = ?", [$documentId, $portalAccess['contact_id']]);

    if (!$doc) {
        throw new Exception("Unauthorized payment attempt or document not found.");
    }

    logStep("Creating payment record...");

    // Capture payment (Mock ID generation)
    $fakeTxnId = 'pay_' . substr(md5(uniqid()), 0, 14);

    $paymentId = PaymentController::create([
        'document_id' => $documentId,
        'paid_amount' => $amount,
        'payment_method' => $method,
        'razorpay_payment_id' => $fakeTxnId,
        'payment_date' => date('Y-m-d')
    ]);

    logStep("Payment Success. ID: $paymentId");

    echo json_encode([
        'success' => true,
        'payment_id' => $paymentId,
        'txn_id' => $fakeTxnId,
        'message' => 'Payment successful!'
    ]);

} catch (Exception $e) {
    logStep("Exception: " . $e->getMessage());
    // Ensure we send JSON even on error
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;

