<?php
/**
 * Budget Line Documents View
 * Shows invoices/bills contributing to achieved amount
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/BudgetController.php';

requireAdmin();

$lineId = isset($_GET['line_id']) ? intval($_GET['line_id']) : null;
$budgetId = isset($_GET['budget_id']) ? intval($_GET['budget_id']) : null;

if (!$lineId || !$budgetId) {
    setFlash('error', 'Invalid request.');
    redirect('/Furniture/views/budgets/index.php');
}

// Get budget and line info
$budget = BudgetController::getById($budgetId);
if (!$budget) {
    setFlash('error', 'Budget not found.');
    redirect('/Furniture/views/budgets/index.php');
}

$line = null;
foreach ($budget['lines'] as $l) {
    if ($l['id'] == $lineId) {
        $line = $l;
        break;
    }
}

if (!$line) {
    setFlash('error', 'Budget line not found.');
    redirect('/Furniture/views/budgets/form.php?id=' . $budgetId);
}

// Get documents
$documents = BudgetController::getDocumentsForLine($line, $budget['start_date'], $budget['end_date']);

$pageTitle = 'Achieved Amount Details';
include __DIR__ . '/../layouts/header.php';
?>

<style>
.detail-card {
    background: rgba(255, 255, 255, 0.7);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid rgba(255, 255, 255, 0.5);
}
.summary-box {
    display: flex;
    gap: 24px;
    padding: 16px;
    background: rgba(139, 92, 246, 0.05);
    border-radius: 12px;
    margin-bottom: 24px;
}
.summary-item {
    text-align: center;
}
.summary-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
}
.summary-label {
    font-size: 12px;
    color: var(--text-secondary);
    text-transform: uppercase;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="/Furniture/views/budgets/form.php?id=<?= $budgetId ?>" class="btn btn-secondary">← Back to Budget</a>
        <h2 style="margin: 0;">Achieved Amount Details</h2>
    </div>
</div>

<div class="detail-card">
    <h3 style="margin-top: 0;">
        <?= sanitize($line['analytical_name']) ?>
        <span style="font-weight: normal; color: var(--text-secondary);">
            (<?= ucfirst($line['type']) ?>)
        </span>
    </h3>
    
    <div class="summary-box">
        <div class="summary-item">
            <div class="summary-value"><?= formatCurrency($line['budgeted_amount']) ?></div>
            <div class="summary-label">Budgeted</div>
        </div>
        <div class="summary-item">
            <div class="summary-value" style="color: #8b5cf6;">
                <?php 
                $achieved = 0;
                foreach ($documents as $doc) {
                    $achieved += floatval($doc['total_amount']);
                }
                echo formatCurrency($achieved);
                ?>
            </div>
            <div class="summary-label">Achieved</div>
        </div>
        <div class="summary-item">
            <div class="summary-value"><?= count($documents) ?></div>
            <div class="summary-label"><?= $line['type'] === 'income' ? 'Invoices' : 'Bills' ?></div>
        </div>
    </div>
    
    <p style="color: var(--text-secondary);">
        Budget Period: <?= formatDate($budget['start_date']) ?> — <?= formatDate($budget['end_date']) ?>
    </p>
</div>

<div class="detail-card">
    <h3 style="margin-top: 0;">
        <?= $line['type'] === 'income' ? 'Customer Invoices' : 'Vendor Bills' ?>
    </h3>
    
    <?php if (empty($documents)): ?>
        <p style="color: var(--text-secondary); text-align: center; padding: 20px;">
            No <?= $line['type'] === 'income' ? 'invoices' : 'bills' ?> found for this period and cost center.
        </p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Document #</th>
                    <th><?= $line['type'] === 'income' ? 'Customer' : 'Vendor' ?></th>
                    <th>Date</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td>
                            <a href="/Furniture/views/documents/view.php?id=<?= $doc['id'] ?>">
                                <?= $line['type'] === 'income' ? 'INV' : 'BILL' ?>-<?= str_pad($doc['id'], 5, '0', STR_PAD_LEFT) ?>
                            </a>
                        </td>
                        <td><?= sanitize($doc['contact_name']) ?></td>
                        <td><?= formatDate($doc['created_at'], 'd M Y') ?></td>
                        <td><strong><?= formatCurrency($doc['total_amount']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
