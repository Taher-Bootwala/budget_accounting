<?php
/**
 * Budget Revisions History View
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../controllers/BudgetController.php';

requireAdmin();

$budgetId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$budgetId) {
    setFlash('error', 'Budget ID required.');
    redirect('/Furniture/views/budgets/index.php');
}

$budget = BudgetController::getById($budgetId);
if (!$budget) {
    setFlash('error', 'Budget not found.');
    redirect('/Furniture/views/budgets/index.php');
}

$revisions = BudgetController::getRevisions($budgetId);
$costCenter = dbFetchOne("SELECT * FROM cost_centers WHERE id = ?", [$budget['cost_center_id']]);

$pageTitle = 'Budget Revisions';
include __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📝 Budget Revisions</h1>
    <a href="/Furniture/views/budgets/index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <h3><?= sanitize($costCenter['name'] ?? 'Unknown') ?></h3>
        <p>Current Amount: <strong><?= formatCurrency($budget['amount']) ?></strong></p>
        <p>Period: <?= formatDate($budget['start_date']) ?> - <?= formatDate($budget['end_date']) ?></p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Revision History</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($revisions)): ?>
            <div class="empty-state">No revisions found for this budget.</div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Old Amount</th>
                        <th>New Amount</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revisions as $rev): ?>
                        <?php
                        $change = $rev['new_amount'] - $rev['old_amount'];
                        $changeClass = $change >= 0 ? 'success' : 'danger';
                        $changeSign = $change >= 0 ? '+' : '';
                        ?>
                        <tr>
                            <td><?= formatDate($rev['revised_at'], 'd M Y H:i') ?></td>
                            <td><?= formatCurrency($rev['old_amount']) ?></td>
                            <td><strong><?= formatCurrency($rev['new_amount']) ?></strong></td>
                            <td>
                                <span style="color: var(--<?= $changeClass ?>);">
                                    <?= $changeSign ?>        <?= formatCurrency($change) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>