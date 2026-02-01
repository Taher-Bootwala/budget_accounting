<?php
/**
 * Documents List View
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/DocumentController.php';

requireAdmin();

$pageTitle = 'Documents';

// Handle actions
if (isset($_GET['post'])) {
    if (DocumentController::post(intval($_GET['post']))) {
        setFlash('success', 'Document posted. Transactions created.');
    } else {
        setFlash('error', 'Cannot post document.');
    }
    redirect('/Furniture/views/documents/index.php');
}

if (isset($_GET['cancel'])) {
    DocumentController::cancel(intval($_GET['cancel']));
    setFlash('success', 'Document cancelled.');
    redirect('/Furniture/views/documents/index.php');
}

if (isset($_GET['delete'])) {
    DocumentController::delete(intval($_GET['delete']));
    setFlash('success', 'Document deleted.');
    redirect('/Furniture/views/documents/index.php');
}

$type = $_GET['type'] ?? null;
$status = $_GET['status'] ?? null;

// Map merged types to arrays
$typeFilter = null;
if ($type === 'purchases') {
    $typeFilter = ['PO', 'VendorBill'];
} elseif ($type === 'sales') {
    $typeFilter = ['SO', 'CustomerInvoice'];
} elseif ($type) {
    $typeFilter = $type;
}

$documents = DocumentController::getAll($typeFilter, $status);

include __DIR__ . '/../layouts/header.php';
?>

<div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="?type=" class="btn <?= !$type ? 'btn-primary' : 'btn-secondary' ?> btn-sm">All</a>
        <a href="?type=purchases" class="btn <?= $type === 'purchases' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Purchases</a>
        <a href="?type=sales" class="btn <?= $type === 'sales' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Sales</a>
    </div>
    <div style="display: flex; gap: 8px;">
        <?php if ($type === 'purchases'): ?>
            <a href="/Furniture/views/documents/create.php?type=PO" class="btn btn-secondary">+ Purchase Order</a>
            <a href="/Furniture/views/documents/create.php?type=VendorBill" class="btn btn-primary">+ Vendor Bill</a>
        <?php elseif ($type === 'sales'): ?>
            <a href="/Furniture/views/documents/create.php?type=SO" class="btn btn-secondary">+ Sales Order</a>
            <a href="/Furniture/views/documents/create.php?type=CustomerInvoice" class="btn btn-primary">+ Invoice</a>
        <?php else: ?>
            <a href="/Furniture/views/documents/create.php?type=PO" class="btn btn-secondary">+ Purchase Order</a>
            <a href="/Furniture/views/documents/create.php?type=SO" class="btn btn-primary">+ Sales Order</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Document #</th>
                    <th>Type</th>
                    <th>Contact</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <?php 
                    $statusBadge = getDocumentStatusBadge($doc['status']);
                    $paymentBadge = getPaymentBadge($doc['payment_status']);
                    ?>
                    <tr>
                        <td>
                            <a href="/Furniture/views/documents/view.php?id=<?= $doc['id'] ?>" class="clickable">
                                <strong><?= sanitize($doc['document_number']) ?></strong>
                            </a>
                            <br><small style="color: var(--gray);"><?= formatDate($doc['created_at']) ?></small>
                        </td>
                        <td><?= sanitize($doc['doc_type']) ?></td>
                        <td><?= sanitize($doc['contact_name'] ?? '-') ?></td>
                        <td><?= formatCurrency($doc['total_amount']) ?></td>
                        <td>
                            <span class="badge badge-<?= $statusBadge['color'] ?>">
                                <?= $statusBadge['label'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if (in_array($doc['doc_type'], ['VendorBill', 'CustomerInvoice'])): ?>
                                <span class="badge badge-<?= $paymentBadge['color'] ?>">
                                    <?= $paymentBadge['icon'] ?> <?= $paymentBadge['label'] ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/Furniture/views/documents/view.php?id=<?= $doc['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                            
                            <?php if ($doc['status'] === 'draft'): ?>
                                <a href="?post=<?= $doc['id'] ?>" class="btn btn-primary btn-sm" 
                                   onclick="return confirm('Post this document?')">Post</a>
                            <?php endif; ?>
                            
                            <?php if ($doc['status'] === 'posted' && $doc['doc_type'] === 'VendorBill' && $doc['payment_status'] !== 'paid'): ?>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="initiatePayment(<?= $doc['id'] ?>, <?= $doc['total_amount'] - ($doc['paid_amount'] ?? 0) ?>)">
                                    Pay Vendor
                                </button>
                            <?php endif; ?>

                            <?php if ($doc['status'] === 'pending_vendor'): ?>
                                <span class="badge badge-warning">Pending Vendor Approval</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($documents)): ?>
                    <tr><td colspan="7" class="empty-state">No documents found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
