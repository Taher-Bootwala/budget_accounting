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
            <button class="btn btn-primary" onclick="alert('Payment Gateway Integration would launch here.')">
                <i class="ri-secure-payment-line"></i> Pay Now <?= formatCurrency($balance) ?>
            </button>
        </div>
        <?php endif; ?>

    </div>

    <!-- Bottom Actions -->
    <div class="no-print" style="width: 100%; max-width: 800px; margin: 20px auto 40px; display: flex; justify-content: flex-end; align-items: center; gap: 16px;">
        <a href="/Furniture/portal/index.php" class="btn btn-secondary"><i class="ri-arrow-left-line"></i> Back</a>
        <button onclick="window.print()" class="btn btn-secondary"><i class="ri-printer-line"></i> Print / Download</button>
    </div>

</body>
</html>
