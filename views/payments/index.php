<?php
/**
 * Payments List View
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';

requireAdmin();

$pageTitle = 'Payments';
$payments = PaymentController::getAll();

include __DIR__ . '/../layouts/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">💳 All Payments</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($payments)): ?>
            <div class="empty-state">No payments recorded yet.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Document</th>
                        <th>Contact</th>
                        <th>Amount Paid</th>
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
                            <td>
                                <a href="/Furniture/views/documents/view.php?id=<?= $p['document_id'] ?>" class="clickable">
                                    <?= sanitize($p['document_number']) ?>
                                </a>
                            </td>
                            <td>
                                <?= sanitize($p['contact_name'] ?? '-') ?>
                            </td>
                            <td style="color: var(--success); font-weight: 600;">
                                <?= formatCurrency($p['paid_amount']) ?>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?= sanitize(ucfirst($p['payment_method'] ?? 'N/A')) ?>
                                </span>
                            </td>
                            <td><code style="font-size: 12px;"><?= sanitize($p['razorpay_payment_id'] ?? '-') ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>