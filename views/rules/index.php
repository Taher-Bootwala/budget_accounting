<?php
/**
 * Auto Analytical Rules List
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/RuleController.php';

requireAdmin();

$pageTitle = 'Auto Analytical Rules';

if (isset($_GET['delete'])) {
    RuleController::delete(intval($_GET['delete']));
    setFlash('success', 'Rule deleted.');
    redirect('/Furniture/views/rules/index.php');
}

$rules = RuleController::getAll();

include __DIR__ . '/../layouts/header.php';
?>

<div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
    <div style="background: var(--info-light); padding: 12px 16px; border-radius: 8px; color: var(--info);">
        💡 Rules automatically assign cost centers to transactions based on product or category.
    </div>
    <a href="/Furniture/views/rules/form.php" class="btn btn-primary">+ Add Rule</a>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rule Type</th>
                    <th>Rule Value</th>
                    <th>Assigns To</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td>
                            <span class="badge <?= $rule['rule_type'] === 'product' ? 'badge-info' : 'badge-warning' ?>">
                                <?= ucfirst($rule['rule_type']) ?>
                            </span>
                        </td>
                        <td><strong><?= sanitize($rule['rule_value']) ?></strong></td>
                        <td>
                            <a href="/Furniture/views/cost_centers/drilldown.php?id=<?= $rule['cost_center_id'] ?>" class="clickable">
                                🎯 <?= sanitize($rule['cost_center_name']) ?>
                            </a>
                        </td>
                        <td>
                            <a href="/Furniture/views/rules/form.php?id=<?= $rule['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <a href="?delete=<?= $rule['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rules)): ?>
                    <tr><td colspan="4" class="empty-state">No rules configured. Add one to enable auto-assignment.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
