<?php
/**
 * Generic Portal Document View
 * Handles: CustomerInvoice, VendorBill, SalesOrder, PurchaseOrder
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

requirePortal();

$userId = getCurrentUserId();
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$id) {
    redirect('/Furniture/portal/index.php');
}

// Handle Vendor Approval Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Security: We already check portal access below, but we need to fetch doc first to verify ownership
    // However, $doc is fetched LATER. We need to move the fetch logic up or put this handler after fetch.
    // Let's just put it after $doc is fetched.
}


// Security: Check portal access
$portalAccess = dbFetchOne("SELECT contact_id FROM portal_access WHERE user_id = ?", [$userId]);
if (!$portalAccess) {
    echo "Access Denied."; exit;
}
$contactId = $portalAccess['contact_id'];

// Fetch Document
// Security: Ensure document belongs to this contact
$doc = dbFetchOne("
    SELECT d.*, c.name as contact_name
    FROM documents d
    JOIN contacts c ON d.contact_id = c.id
    WHERE d.id = ? AND d.contact_id = ?
", [$id, $contactId]);

if (!$doc) {
    echo "Document not found or access denied."; exit;
}

// Handle Vendor Approval Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    // Logic: Vendors can Approve/Reject POs
    if (($doc['doc_type'] === 'PO' || $doc['doc_type'] === 'PurchaseOrder') && $doc['status'] === 'pending_vendor') {
        if ($action === 'approve') {
            dbExecute("UPDATE documents SET status = 'posted' WHERE id = ?", [$id]);
            setFlash('success', 'Order Approved.'); 
            // Refresh
            header("Location: ?id=$id"); exit;
        } elseif ($action === 'reject') {
            dbExecute("UPDATE documents SET status = 'cancelled' WHERE id = ?", [$id]);
            setFlash('success', 'Order Rejected.');
            header("Location: ?id=$id"); exit;
        }
    }
}

// Fetch Lines
$lines = dbFetchAll("
    SELECT dl.*, p.name as product_name
    FROM document_lines dl
    JOIN products p ON dl.product_id = p.id
    WHERE dl.document_id = ?
", [$id]);

// Calculate Paid Amount (only for Bills/Invoices)
$paidAmount = 0;
if (in_array($doc['doc_type'], ['CustomerInvoice', 'VendorBill'])) {
    $paid = dbFetchOne("SELECT SUM(paid_amount) as total FROM payments WHERE document_id = ?", [$id]);
    $paidAmount = $paid['total'] ?? 0;
}

$balance = $doc['total_amount'] - $paidAmount;
$status = $doc['status'];

// Badge Logic
$badgeColor = 'secondary';
if ($status === 'draft') $badgeColor = 'secondary';
elseif ($status === 'confirmed' || $status === 'approved') $badgeColor = 'info';
elseif ($status === 'paid' || $status === 'completed') $badgeColor = 'success';
elseif ($status === 'cancelled') $badgeColor = 'danger';

// Type Label
$typeLabel = preg_replace('/(?<!^)[A-Z]/', ' $0', $doc['doc_type']); // "CustomerInvoice" -> "Customer Invoice"

$pageTitle = "$typeLabel #" . str_pad($doc['id'], 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/Furniture/assets/css/style.css">
    <style>
        body { 
            background: #f8fafc; 
            padding: 20px; 
            display: flex; 
            flex-direction: column; /* Force vertical layout */
            align-items: center;    /* Center content horizontally */
        }
        .doc-paper {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border-radius: 16px;
        }
        @media (max-width: 768px) {
            .doc-paper { padding: 20px; }
            body { padding: 10px; }
        }
        @media print {
            .no-print { display: none !important; }
            body, .doc-paper { background: white;  box-shadow: none; margin: 0; padding: 0; display: block; }
        }
    </style>
</head>
<body>

    <!-- Ambient BG -->
    <div class="fluid-background"></div>

    <!-- Top Navigation Bar -->
    <div class="no-print" style="width: 100%; max-width: 800px; display: flex; justify-content: space-between; align-items: center; padding: 10px 0; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="/Furniture/portal/index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: var(--text-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i class="ri-user-star-line" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px; font-weight: 700; white-space: nowrap; color: var(--text-primary);">Customer Portal</h2>
                    <div style="font-size: 13px; opacity: 0.7; color: var(--text-secondary);">Furniture ERP</div>
                </div>
            </a>
        </div>
        <!-- Logout button removed as requested -->
    </div>


    <div class="doc-paper">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1 style="font-size: 24px; margin-bottom: 4px;"><?= $typeLabel ?></h1>
                <div style="color: var(--text-secondary);">#<?= str_pad($doc['id'], 4, '0', STR_PAD_LEFT) ?></div>
                <div style="margin-top: 8px; font-size: 14px; opacity: 0.8;"><?= $doc['created_at'] ?></div>
            </div>
            <div style="text-align: right;">
                <span class="badge badge-<?= $badgeColor ?>"><?= strtoupper($status) ?></span>
                <div style="margin-top: 10px; font-size: 24px; font-weight: 700; color: var(--accent-wood);">
                    <?= formatCurrency($doc['total_amount']) ?>
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <div>
                <h5 class="form-label">From</h5>
                <strong>Furniture ERP Ltd.</strong><br>
                123 Design Street<br>
                Creative City, 10001
            </div>
            <div>
                <h5 class="form-label">To</h5>
                <strong><?= sanitize($doc['contact_name']) ?></strong><br>
                (Address on file)
            </div>
        </div>

        <!-- Lines -->
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="text-align: left;">Product</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $line): ?>
                    <tr>
                        <td><?= sanitize($line['product_name']) ?></td>
                        <td style="text-align: center;"><?= $line['quantity'] ?></td>
                        <td style="text-align: right;"><?= formatCurrency($line['price']) ?></td>
                        <td style="text-align: right;"><?= formatCurrency($line['line_total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 600; padding-top: 20px;">Total</td>
                        <td style="text-align: right; font-weight: 700; padding-top: 20px; padding-right: 12px;"><?= formatCurrency($doc['total_amount']) ?></td>
                    </tr>
                    <?php if ($doc['doc_type'] === 'CustomerInvoice'): ?>
                    <tr>
                        <td colspan="3" style="text-align: right;">Paid</td>
                        <td style="text-align: right; color: var(--success); padding-right: 12px;"><?= formatCurrency($paidAmount) ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: 600;">Balance Due</td>
                        <td style="text-align: right; font-weight: 700; color: var(--danger); padding-right: 12px;"><?= formatCurrency($balance) ?></td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>



        <!-- Actions -->
        <?php if ($doc['doc_type'] === 'CustomerInvoice' && $balance > 0 && $status !== 'cancelled'): ?>
            <div class="no-print" style="margin-top: 40px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                <p style="margin-bottom: 16px;">Secure Payment via Razorpay/Stripe</p>
                <button class="btn btn-primary" onclick="openPaymentModal()">
                    <i class="ri-secure-payment-line"></i> Pay Now <?= formatCurrency($balance) ?>
                </button>
            </div>
        <?php endif; ?>

        <!-- Vendor Approval Actions -->
        <?php if (($doc['doc_type'] === 'PO' || $doc['doc_type'] === 'PurchaseOrder') && $status === 'pending_vendor'): ?>
            <div class="no-print" style="margin-top: 40px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                <p style="margin-bottom: 16px;">Please review the order details.</p>
                <div style="display: flex; gap: 16px; justify-content: center;">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn btn-secondary" style="color: var(--danger); border-color: var(--danger);" onclick="return confirm('Reject this order?')">Reject Order</button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-primary" style="background-color: var(--success); border-color: var(--success);">Approve Order</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Vendor Bill Payment Status -->
        <?php if (($doc['doc_type'] === 'VendorBill') && $status === 'posted'): ?>
            <div class="no-print" style="margin-top: 40px; text-align: center; border-top: 1px solid #eee; padding-top: 20px;">
                <?php if ($balance <= 0): ?>
                    <div style="color: var(--success); font-size: 18px; font-weight: 600;">
                        <i class="ri-check-double-line"></i> Payment Received
                    </div>
                <?php else: ?>
                    <div style="color: var(--warning); font-size: 18px; font-weight: 600;">
                        <i class="ri-time-line"></i> Payment Pending (Due: <?= formatCurrency($balance) ?>)
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Bottom Actions -->
    <div class="no-print" style="width: 100%; max-width: 800px; margin: 20px auto 40px; display: flex; justify-content: flex-end; align-items: center; gap: 16px;">
        <a href="/Furniture/portal/index.php" class="btn btn-secondary"><i class="ri-arrow-left-line"></i> Back</a>
        <button onclick="window.print()" class="btn btn-secondary"><i class="ri-printer-line"></i> Print / Download</button>
    </div>


    <!-- MOCK PAYMENT MODAL -->
    <div class="modal-overlay" id="paymentModal">
        <div class="modal-content" style="max-width: 550px; text-align: center;">
            <div class="modal-header" style="justify-content: center; position: relative; margin-bottom: 20px;">
                <h3 style="margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="ri-secure-payment-line" style="color: var(--success);"></i> Secure Payment
                </h3>
                <button class="modal-close" onclick="closePaymentModal()" style="position: absolute; right: 0; top: -5px;">&times;</button>
            </div>

            <div id="paymentStep1">
                <p style="color: var(--text-secondary); margin-bottom: 20px;">Processing payment for <strong><?= $typeLabel ?> #<?= str_pad($doc['id'], 4, '0', STR_PAD_LEFT) ?></strong></p>

                <div style="margin-bottom: 20px; text-align: left;">
                    <label style="display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 4px;">Amount to Pay (Partial Allowed)</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 12px; color: var(--text-secondary);">₹</span>
                        <input type="number" id="payAmount" class="form-control" style="width: 100%; padding-left: 30px; font-weight: 700;" value="<?= $balance ?>" max="<?= $balance ?>">
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">Balance Due: <?= formatCurrency($balance) ?></div>
                </div>

                <div style="margin-bottom: 24px; text-align: left;">
                    <label style="display: block; font-size: 13px; color: var(--text-secondary); margin-bottom: 8px;">Payment Method</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label class="pay-method selected" onclick="selectMethod(this, 'card')">
                            <i class="ri-bank-card-line"></i> Card
                        </label>
                        <label class="pay-method" onclick="selectMethod(this, 'upi')">
                            <i class="ri-apps-line"></i> UPI / QR
                        </label>
                    </div>
                </div>

                <button onclick="processMockPayment()" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    Pay Now
                </button>
            </div>

            <div id="paymentStep2" style="display: none; padding: 20px 0;">
                
                <!-- Card Animation -->
                <div id="cardAnimation" style="display: none;">
                    <div class="spinner"></div>
                    <p style="margin-top: 16px; font-weight: 500;">Contacting Bank...</p>
                    <p style="font-size: 12px; color: var(--text-secondary);">Please do not refresh the page.</p>
                </div>

                <!-- UPI Animation -->
                <div id="upiAnimation" style="display: none;">
                    <div class="upi-pulse">
                        <i class="ri-smartphone-line" style="font-size: 32px; color: var(--primary);"></i>
                    </div>
                    <p style="margin-top: 16px; font-weight: 500;">Request sent to UPI App</p>
                    <p style="font-size: 12px; color: var(--text-secondary);">Open your payment app to approve.</p>
                    <div style="margin-top: 12px; height: 4px; background: #eee; border-radius: 2px; overflow: hidden;">
                        <div class="progress-bar"></div>
                    </div>
                </div>

            </div>

            <div id="paymentError" style="display: none; padding: 10px 0;">
                <div style="color: var(--danger); font-size: 48px; margin-bottom: 8px;"><i class="ri-error-warning-fill"></i></div>
                <h3 style="margin: 0 0 8px;">Payment Failed</h3>
                <p id="errorMsg" style="color: var(--text-secondary); font-size: 14px; margin-bottom: 20px;">Something went wrong.</p>
                <button onclick="resetPaymentModal()" class="btn btn-secondary" style="width: 100%;">Try Again</button>
            </div>

            <div id="paymentSuccess" style="display: none; padding: 10px 0;">
                <div style="width: 60px; height: 60px; background: #dcfce7; color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px;">
                    <i class="ri-check-line"></i>
                </div>
                <h3 style="margin: 0 0 8px;">Payment Successful!</h3>
                <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 20px;">Transaction ID: <span id="txnIdDisplay" style="font-family: monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"></span></p>
                <button onclick="window.location.reload()" class="btn btn-primary" style="width: 100%;">Done</button>
            </div>
        </div>
    </div>

    <!-- Payment Styles -->
    <style>
        .pay-method {
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            color: var(--text-secondary);
        }
        .pay-method:hover { background: #f8fafc; }
        .pay-method.selected {
            border-color: var(--primary);
            background: #fdf4ff;
            color: var(--accent-wood); 
            border-color: var(--accent-wood);
            font-weight: 600;
        }
        .spinner {
            width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid var(--accent-wood);
            border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
        }
        .upi-pulse {
            width: 60px; height: 60px; background: #fdf4ff; border-radius: 50%; margin: 0 auto;
            display: flex; align-items: center; justify-content: center;
            animation: pulse 2s infinite;
        }
        .progress-bar {
            height: 100%; background: var(--accent-wood); width: 0%; animation: progress 3s ease-in-out infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes pulse { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); } }
        @keyframes progress { 0% { width: 0%; } 50% { width: 70%; } 100% { width: 100%; } }
    </style>

    <script>
    let selectedMethod = 'card';

    function openPaymentModal() {
        document.getElementById('paymentModal').classList.add('show');
        resetPaymentModal();
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.remove('show');
    }

    function resetPaymentModal() {
        document.getElementById('paymentStep1').style.display = 'block';
        document.getElementById('paymentStep2').style.display = 'none';
        document.getElementById('paymentError').style.display = 'none';
        document.getElementById('paymentSuccess').style.display = 'none';
    }

    function selectMethod(el, method) {
        document.querySelectorAll('.pay-method').forEach(e => e.classList.remove('selected'));
        el.classList.add('selected');
        selectedMethod = method;
    }

    async function processMockPayment() {
        const amount = document.getElementById('payAmount').value;
        
        if(amount <= 0 || amount > <?= $balance ?>) {
            // Show inline error instead of alert if possible, or simple alert for validation
            alert("Please enter a valid amount (Max: <?= $balance ?>)");
            return;
        }

        // Show Loading UI based on method
        document.getElementById('paymentStep1').style.display = 'none';
        document.getElementById('paymentStep2').style.display = 'block';
        
        if(selectedMethod === 'upi') {
            document.getElementById('cardAnimation').style.display = 'none';
            document.getElementById('upiAnimation').style.display = 'block';
        } else {
            document.getElementById('upiAnimation').style.display = 'none';
            document.getElementById('cardAnimation').style.display = 'block';
        }

        // Simulate Delay
        await new Promise(r => setTimeout(r, 2000)); 

        try {
            const response = await fetch('/Furniture/api/process_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    document_id: <?= $doc['id'] ?>,
                    amount: amount,
                    method: selectedMethod
                })
            });
            
            // Check if response is JSON
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                const text = await response.text();
                console.error("Server Error:", text);
                throw new Error("Server returned an error (Check console for details)");
            }

            const result = await response.json();

            if(result.success) {
                document.getElementById('paymentStep2').style.display = 'none';
                document.getElementById('paymentSuccess').style.display = 'block';
                document.getElementById('txnIdDisplay').innerText = result.txn_id;
            } else {
                throw new Error(result.message);
            }
        } catch (e) {
            document.getElementById('paymentStep2').style.display = 'none';
            document.getElementById('paymentError').style.display = 'block';
            document.getElementById('errorMsg').innerText = e.message;
        }
    }
    </script>
</body>
</html>
