<?php
/**
 * Portal Invoice View
 * Uses portal_access for security check, not email
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

// Get user's contact via portal_access
$portalAccess = dbFetchOne("SELECT contact_id FROM portal_access WHERE user_id = ?", [$userId]);
if (!$portalAccess) {
    setFlash('error', 'No portal access.');
    redirect('/Furniture/portal/index.php');
}

// Get invoice with security check
$invoice = dbFetchOne("
    SELECT d.*, c.name as contact_name,
           CONCAT(d.doc_type, '-', LPAD(d.id, 4, '0')) as document_number,
           (SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE document_id = d.id) as paid_amount
    FROM documents d
    JOIN contacts c ON d.contact_id = c.id
    WHERE d.id = ? AND d.doc_type = 'CustomerInvoice' AND d.contact_id = ?
", [$id, $portalAccess['contact_id']]);

if (!$invoice) {
    setFlash('error', 'Invoice not found or access denied.');
    redirect('/Furniture/portal/index.php');
}

$lines = dbFetchAll("
    SELECT dl.*, p.name as product_name
    FROM document_lines dl
    JOIN products p ON dl.product_id = p.id
    WHERE dl.document_id = ?
", [$id]);

$payments = dbFetchAll("SELECT * FROM payments WHERE document_id = ? ORDER BY payment_date DESC", [$id]);
$paymentBadge = getPaymentBadge(getPaymentStatus($invoice['total_amount'], $invoice['paid_amount']));

$pageTitle = 'Invoice ' . $invoice['document_number'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Furniture/assets/css/style.css">
    <style>
        .invoice-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }

        .invoice-box {
            background: var(--white);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .invoice-header {
            background: var(--gradient-primary);
            color: white;
            padding: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .invoice-body {
            padding: 32px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .invoice-box {
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="demo-banner no-print">🎭 Demo Mode – Sample Data</div>

    <div class="invoice-container">
        <div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between;">
            <a href="/Furniture/portal/index.php" class="btn btn-secondary">← Back</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print</button>
        </div>

        <div class="invoice-box">
            <div class="invoice-header">
                <div>
                    <h1 style="margin: 0;"><?= sanitize($invoice['document_number']) ?></h1>
                    <p style="margin: 8px 0 0; opacity: 0.9;"><?= formatDate($invoice['created_at'], 'd M Y') ?></p>
                </div>
                <div>
                    <span class="badge badge-<?= $paymentBadge['color'] ?>" style="font-size: 16px;">
                        <?= $paymentBadge['icon'] ?> <?= $paymentBadge['label'] ?>
                    </span>
                </div>
            </div>

            <div class="invoice-body">
                <div style="margin-bottom: 32px;">
                    <strong>Bill To:</strong><br>
                    <span style="font-size: 18px;"><?= sanitize($invoice['contact_name']) ?></span>
                </div>

                <!-- Line Items -->
                <table class="data-table" style="margin-bottom: 24px;">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lines as $line): ?>
                            <tr>
                                <td><?= sanitize($line['product_name']) ?></td>
                                <td><?= $line['quantity'] ?></td>
                                <td><?= formatCurrency($line['price']) ?></td>
                                <td style="text-align: right;"><?= formatCurrency($line['line_total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 600;">Total:</td>
                            <td style="text-align: right; font-size: 20px; font-weight: 700; color: var(--primary);">
                                <?= formatCurrency($invoice['total_amount']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;">Paid:</td>
                            <td style="text-align: right; color: var(--success);">
                                <?= formatCurrency($invoice['paid_amount']) ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: 600;">Balance Due:</td>
                            <td style="text-align: right; font-weight: 700; color: var(--danger);">
                                <?= formatCurrency($invoice['total_amount'] - $invoice['paid_amount']) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <?php if ($invoice['total_amount'] - $invoice['paid_amount'] > 0): ?>
                    <div class="no-print" style="text-align: center;">
                        <button class="btn btn-primary btn-lg"
                            onclick="initiatePayment(<?= $id ?>, <?= $invoice['total_amount'] - $invoice['paid_amount'] ?>)">
                            💳 Pay Now (<?= formatCurrency($invoice['total_amount'] - $invoice['paid_amount']) ?>)
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Payment History -->
                <?php if (!empty($payments)): ?>
                    <h4 style="margin-top: 40px;">Payment History</h4>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= formatDate($p['payment_date']) ?></td>
                                    <td style="color: var(--success);"><?= formatCurrency($p['paid_amount']) ?></td>
                                    <td><?= ucfirst($p['payment_method'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../views/layouts/footer.php'; ?>
</body>

</html>