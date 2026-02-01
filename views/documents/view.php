<?php
/**
 * Document Detail View
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/DocumentController.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id)
    redirect('/Furniture/views/documents/index.php');

$document = DocumentController::getById($id);
if (!$document) {
    setFlash('error', 'Document not found.');
    redirect('/Furniture/views/documents/index.php');
}

$payments = PaymentController::getByDocumentId($id);
$statusBadge = getDocumentStatusBadge($document['status']);
$paymentBadge = getPaymentBadge($document['payment_status']);
$pageTitle = $document['document_number'];
$hideNavbar = true;

include __DIR__ . '/../layouts/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <a href="/Furniture/views/documents/index.php" class="btn btn-secondary btn-sm">← Back to Documents</a>

    <div style="display: flex; gap: 8px;">
        <?php if ($document['status'] === 'draft'): ?>
            <a href="/Furniture/views/documents/index.php?post=<?= $id ?>" class="btn btn-success"
                onclick="return confirm('Post this document?')">
                Post Document
            </a>
        <?php endif; ?>

        <?php if ($document['status'] === 'posted' && in_array($document['doc_type'], ['VendorBill', 'CustomerInvoice']) && $document['payment_status'] !== 'paid'): ?>
            <button class="btn btn-primary"
                onclick="initiatePayment(<?= $id ?>, <?= $document['total_amount'] - $document['paid_amount'] ?>)">
                💳 Pay Now (
                <?= formatCurrency($document['total_amount'] - $document['paid_amount']) ?>)
            </button>
        <?php endif; ?>

        <button class="btn btn-secondary" onclick="window.print()">🖨️ Print</button>
    </div>
</div>

<!-- Document Header -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-body" style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
        <div>
            <h2 style="margin: 0 0 8px;">
                <?= sanitize($document['document_number']) ?>
            </h2>
            <p style="color: var(--gray); margin: 0;">
                <?= $document['doc_type'] ?> • Created
                <?= formatDate($document['created_at'], 'd M Y H:i') ?>
            </p>

            <div style="margin-top: 24px;">
                <strong>
                    <?= $document['contact_type'] === 'vendor' ? 'Vendor' : 'Customer' ?>:
                </strong><br>
                <span style="font-size: 18px;">
                    <?= sanitize($document['contact_name']) ?>
                </span><br>
                <span style="color: var(--gray);">
                    <?= sanitize($document['contact_email'] ?? '') ?>
                </span>
            </div>
        </div>

        <div style="text-align: right;">
            <div style="margin-bottom: 16px;">
                <span class="badge badge-<?= $statusBadge['color'] ?>" style="font-size: 14px;">
                    <?= $statusBadge['label'] ?>
                </span>
            </div>

            <?php if (in_array($document['doc_type'], ['VendorBill', 'CustomerInvoice'])): ?>
                <div style="margin-bottom: 16px;">
                    <span class="badge badge-<?= $paymentBadge['color'] ?>" style="font-size: 14px;">
                        <?= $paymentBadge['icon'] ?>
                        <?= $paymentBadge['label'] ?>
                    </span>
                </div>
            <?php endif; ?>

            <div>
                <span style="color: var(--gray);">Total Amount</span><br>
                <span style="font-size: 32px; font-weight: 700; color: var(--primary);">
                    <?= formatCurrency($document['total_amount']) ?>
                </span>
            </div>

            <?php if ($document['paid_amount'] > 0): ?>
                <div style="margin-top: 8px;">
                    <span style="color: var(--success);">Paid:
                        <?= formatCurrency($document['paid_amount']) ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Line Items -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title">Line Items</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th style="text-align: right;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($document['lines'] as $line): ?>
                    <tr>
                        <td><strong>
                                <?= sanitize($line['product_name']) ?>
                            </strong></td>
                        <td><span class="badge badge-secondary">
                                <?= sanitize($line['product_category'] ?? '-') ?>
                            </span></td>
                        <td>
                            <?= $line['quantity'] ?>
                        </td>
                        <td>
                            <?= formatCurrency($line['price']) ?>
                        </td>
                        <td style="text-align: right; font-weight: 600;">
                            <?= formatCurrency($line['line_total']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: 600; padding-right: 16px;">Grand Total:</td>
                    <td style="text-align: right; font-size: 16px; font-weight: 700; color: var(--primary); white-space: nowrap; padding-right: 16px;">
                        <?= formatCurrency($document['total_amount']) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Payments -->
<?php if (!empty($payments) || in_array($document['doc_type'], ['VendorBill', 'CustomerInvoice'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">💳 Payment History</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($payments)): ?>
                <div class="empty-state">No payments recorded.</div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Razorpay ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td>
                                    <?= formatDate($p['payment_date']) ?>
                                </td>
                                <td style="color: var(--success); font-weight: 600;">
                                    <?= formatCurrency($p['paid_amount']) ?>
                                </td>
                                <td>
                                    <?= sanitize(ucfirst($p['payment_method'] ?? 'N/A')) ?>
                                </td>
                                <td><code><?= sanitize($p['razorpay_payment_id'] ?? '-') ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>