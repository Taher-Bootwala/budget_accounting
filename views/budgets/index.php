<?php
/**
 * Budgets List View - Redesigned
 * With status tabs: Draft, Confirm, Revised, Cancelled
 * With month filter
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/BudgetController.php';

requireAdmin();

$pageTitle = 'Budgets';

// Handle status change actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $tab = $_GET['tab'] ?? 'draft';
    $month = $_GET['month'] ?? '';
    
    if ($action === 'confirm') {
        BudgetController::updateStatus($id, 'confirmed');
        setFlash('success', 'Budget confirmed.');
    } elseif ($action === 'cancel') {
        BudgetController::updateStatus($id, 'cancelled');
        setFlash('success', 'Budget cancelled.');
    } elseif ($action === 'revise') {
        $newId = BudgetController::revise($id);
        if ($newId) {
            setFlash('success', 'Revision created. You can now edit the new draft.');
            redirect('/Furniture/views/budgets/form.php?id=' . $newId);
        }
    } elseif ($action === 'delete') {
        BudgetController::delete($id);
        setFlash('success', 'Budget deleted.');
    }
    redirect('/Furniture/views/budgets/index.php?tab=' . $tab . ($month ? '&month=' . $month : ''));
}

// Get current tab and month filter
$currentTab = $_GET['tab'] ?? 'draft';
$currentMonth = $_GET['month'] ?? '';
$availableMonths = BudgetController::getAvailableMonths();
$budgets = BudgetController::getAll($currentTab, $currentMonth ?: null);

include __DIR__ . '/../layouts/header.php';
?>

<style>
.status-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
}
.status-tab {
    padding: 10px 24px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
    background: rgba(255, 255, 255, 0.5);
    color: var(--text-secondary);
    border: 1px solid rgba(255, 255, 255, 0.6);
}
.status-tab:hover {
    background: rgba(255, 255, 255, 0.8);
}
.status-tab.active {
    background: var(--text-primary);
    color: white;
}
.status-tab.draft.active { background: #f97316; }
.status-tab.confirmed.active { background: #10b981; }
.status-tab.revised.active { background: #8b5cf6; }
.status-tab.cancelled.active { background: #ef4444; }

.budget-card {
    background: rgba(255, 255, 255, 0.7);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(10px);
}
.budget-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}
.budget-name {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
}
.budget-period {
    background: rgba(0,0,0,0.05);
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    color: var(--text-secondary);
}
.budget-stats {
    display: flex;
    gap: 24px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(0,0,0,0.05);
}
.stat-item {
    text-align: center;
}
.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-primary);
}
.stat-label {
    font-size: 12px;
    color: var(--text-secondary);
    text-transform: uppercase;
}
.revised-link {
    margin-top: 8px;
    font-size: 13px;
}
.revised-link a {
    color: var(--accent-wood);
    text-decoration: none;
}
.revised-link a:hover {
    text-decoration: underline;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0;">Budgets</h2>
    <div style="display: flex; align-items: center; gap: 12px;">
        <!-- Month Filter -->
        <select id="monthFilter" class="form-control" style="min-width: 180px;"
                onchange="window.location='?tab=<?= $currentTab ?>&month=' + this.value">
            <option value="">All Months</option>
            <?php foreach ($availableMonths as $m): ?>
                <option value="<?= $m['month_value'] ?>" <?= $currentMonth === $m['month_value'] ? 'selected' : '' ?>>
                    <?= $m['month_label'] ?>
                </option>
            <?php endforeach; ?>
        </select>
        <a href="/Furniture/views/budgets/form.php" class="btn btn-primary">+ New Budget</a>
    </div>
</div>

<!-- Status Tabs -->
<div class="status-tabs">
    <a href="?tab=draft<?= $currentMonth ? '&month=' . $currentMonth : '' ?>" 
       class="status-tab draft <?= $currentTab === 'draft' ? 'active' : '' ?>">
        Draft
    </a>
    <a href="?tab=confirmed<?= $currentMonth ? '&month=' . $currentMonth : '' ?>" 
       class="status-tab confirmed <?= $currentTab === 'confirmed' ? 'active' : '' ?>">
        Confirmed
    </a>
    <a href="?tab=revised<?= $currentMonth ? '&month=' . $currentMonth : '' ?>" 
       class="status-tab revised <?= $currentTab === 'revised' ? 'active' : '' ?>">
        Revised
    </a>
    <a href="?tab=cancelled<?= $currentMonth ? '&month=' . $currentMonth : '' ?>" 
       class="status-tab cancelled <?= $currentTab === 'cancelled' ? 'active' : '' ?>">
        Cancelled
    </a>
</div>

<!-- Budgets List -->
<?php if (empty($budgets)): ?>
    <div class="budget-card" style="text-align: center; padding: 40px;">
        <p style="color: var(--text-secondary); margin-bottom: 16px;">No <?= $currentTab ?> budgets found.</p>
        <?php if ($currentTab === 'draft'): ?>
            <a href="/Furniture/views/budgets/form.php" class="btn btn-primary">Create your first budget</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($budgets as $budget): ?>
        <div class="budget-card">
            <div class="budget-header">
                <div>
                    <div class="budget-name">
                        <a href="/Furniture/views/budgets/form.php?id=<?= $budget['id'] ?>" style="color: inherit; text-decoration: none;">
                            <?= sanitize($budget['name'] ?: 'Budget #' . $budget['id']) ?>
                        </a>
                    </div>
                    <div class="budget-period">
                        📅 <?= formatDate($budget['start_date']) ?> — <?= formatDate($budget['end_date']) ?>
                    </div>
                    <?php if ($budget['revised_from_id']): ?>
                        <div class="revised-link">
                            Revised from: <a href="/Furniture/views/budgets/form.php?id=<?= $budget['revised_from_id'] ?>">View original</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 8px;">
                    <?php $monthParam = $currentMonth ? '&month=' . $currentMonth : ''; ?>
                    <?php if ($currentTab === 'draft'): ?>
                        <a href="?action=confirm&id=<?= $budget['id'] ?>&tab=draft<?= $monthParam ?>" 
                           class="btn btn-primary btn-sm"
                           onclick="return confirm('Confirm this budget? Once confirmed, you cannot edit it directly.')">Confirm</a>
                        <a href="/Furniture/views/budgets/form.php?id=<?= $budget['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <?php endif; ?>
                    <?php if ($currentTab === 'confirmed'): ?>
                        <a href="?action=revise&id=<?= $budget['id'] ?>&tab=confirmed<?= $monthParam ?>" 
                           class="btn btn-primary btn-sm"
                           onclick="return confirm('Create a revision of this budget? The current budget will be marked as revised.')">Revise</a>
                        <a href="/Furniture/views/budgets/form.php?id=<?= $budget['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                    <?php endif; ?>
                    <?php if ($currentTab !== 'cancelled'): ?>
                        <a href="?action=cancel&id=<?= $budget['id'] ?>&tab=<?= $currentTab ?><?= $monthParam ?>" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('Cancel this budget?')">Cancel</a>
                    <?php endif; ?>
                    <?php if ($currentTab === 'draft' || $currentTab === 'cancelled'): ?>
                        <a href="?action=delete&id=<?= $budget['id'] ?>&tab=<?= $currentTab ?><?= $monthParam ?>" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('Delete permanently?')">Delete</a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Analytical Lines Table -->
            <?php 
            $isConfirmed = in_array($currentTab, ['confirmed', 'revised']);
            $lines = BudgetController::getLinesWithComputed($budget['id']); 
            ?>
            <?php if (!empty($lines)): ?>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.05);">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th>Analytic Name</th>
                                <th>Type</th>
                                <th>Budgeted Amount</th>
                                <th>Achieved Amount</th>
                                <th>Achieved %</th>
                                <th>Amount to Achieve</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lines as $line): ?>
                                <tr>
                                    <td>
                                        <strong><?= sanitize($line['analytical_name']) ?></strong>
                                        <br><small style="color: var(--text-secondary);">→ <?= sanitize($line['cost_center_name']) ?></small>
                                    </td>
                                    <td>
                                        <span style="color: <?= $line['type'] === 'income' ? '#10b981' : '#ef4444' ?>; font-weight: 600;">
                                            <?= ucfirst($line['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatCurrency($line['budgeted_amount']) ?></td>
                                    <td>
                                        <?php if ($isConfirmed): ?>
                                            <span style="color: #8b5cf6; font-weight: 600;"><?= formatCurrency($line['achieved_amount']) ?></span>
                                            <a href="/Furniture/views/budgets/line_documents.php?line_id=<?= $line['id'] ?>&budget_id=<?= $budget['id'] ?>" 
                                               style="margin-left: 8px; padding: 2px 8px; background: #e5e7eb; border-radius: 4px; font-size: 11px; text-decoration: none; color: var(--text-primary);">View</a>
                                        <?php else: ?>
                                            <span style="background: #f3f4f6; padding: 2px 8px; border-radius: 12px; font-size: 12px; color: #6b7280;">Compute</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isConfirmed): ?>
                                            <span style="background: rgba(139, 92, 246, 0.1); padding: 4px 10px; border-radius: 20px; font-size: 13px; color: #8b5cf6; font-weight: 600;">
                                                <?= $line['achieved_percent'] ?>%
                                            </span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($line['type'] === 'income' && $isConfirmed): ?>
                                            <span style="color: #f97316; font-weight: 600;"><?= formatCurrency($line['amount_to_achieve']) ?></span>
                                        <?php elseif ($line['type'] === 'income'): ?>
                                            <span style="background: #f3f4f6; padding: 2px 8px; border-radius: 12px; font-size: 12px; color: #6b7280;">Compute</span>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
