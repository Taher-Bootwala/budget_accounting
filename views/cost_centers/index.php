<?php
/**
 * Cost Centers List View with Health Indicators
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/CostCenterController.php';

requireAdmin();

$pageTitle = 'Cost Centers';

if (isset($_GET['delete'])) {
    CostCenterController::delete(intval($_GET['delete']));
    setFlash('success', 'Cost center deleted.');
    redirect('/Furniture/views/cost_centers/index.php');
}

$costCenters = CostCenterController::getAllWithBudgetInfo();

include __DIR__ . '/../layouts/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0;">Cost Centers</h2>
    <a href="/Furniture/views/cost_centers/form.php" class="btn btn-primary">+ Add Cost Center</a>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Budget</th>
                    <th>Actual</th>
                    <th>Utilization</th>
                    <th>Timeline vs Spend</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($costCenters as $cc): ?>
                    <tr>
                        <td>
                            <a href="/Furniture/views/cost_centers/drilldown.php?id=<?= $cc['id'] ?>" class="clickable">
                                <strong><?= sanitize($cc['name']) ?></strong>
                            </a>
                            <?php if (!empty($cc['description'])): ?>
                                <br><small style="color: var(--gray);"><?= sanitize($cc['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $cc['budget_amount'] ? formatCurrency($cc['budget_amount']) : '-' ?></td>
                        <td><?= formatCurrency($cc['actual_spend']) ?></td>
                        <td>
                            <?php if ($cc['budget_amount']): ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="progress-bar-container" style="width: 80px;">
                                        <div class="progress-bar <?= $cc['health']['color'] ?>"
                                            style="width: <?= min($cc['utilization'], 100) ?>%"></div>
                                    </div>
                                    <span><?= $cc['utilization'] ?>%</span>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($cc['budget_amount'] && $cc['time_elapsed'] > 0): ?>
                                <div class="timeline-spend-bar" style="width: 120px; height: 16px;">
                                    <div class="timeline-bar" style="width: <?= $cc['time_elapsed'] ?>%;"></div>
                                    <div class="spend-bar <?= $cc['health']['color'] ?>"
                                        style="width: <?= min($cc['utilization'], 100) ?>%; top: 2px; height: 12px;"></div>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $cc['health']['color'] ?>">
                                <?= $cc['health']['icon'] ?>     <?= $cc['health']['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="/Furniture/views/cost_centers/drilldown.php?id=<?= $cc['id'] ?>"
                                class="btn btn-secondary btn-sm">View</a>
                            <a href="/Furniture/views/cost_centers/form.php?id=<?= $cc['id'] ?>"
                                class="btn btn-secondary btn-sm">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($costCenters)): ?>
                    <tr>
                        <td colspan="7" class="empty-state">No cost centers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>