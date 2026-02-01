<?php
/**
 * Budget Form View
 * Create/Edit budget with analytical lines
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/BudgetController.php';
require_once __DIR__ . '/../../controllers/AnalyticalModelController.php';
require_once __DIR__ . '/../../controllers/CostCenterController.php';

requireAdmin();

$budgetId = isset($_GET['id']) ? intval($_GET['id']) : null;
$budget = $budgetId ? BudgetController::getById($budgetId) : null;
$isEdit = $budget !== null;
$isConfirmed = $isEdit && in_array($budget['status'], ['confirmed', 'revised']);

$pageTitle = $isEdit ? ($isConfirmed ? 'View Budget' : 'Edit Budget') : 'New Budget';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_budget') {
        $data = [
            'name' => $_POST['name'],
            'cost_center_id' => intval($_POST['cost_center_id']),
            'amount' => floatval($_POST['amount']),
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'status' => $_POST['status'] ?? 'draft'
        ];
        
        if ($isEdit) {
            BudgetController::update($budgetId, $data);
            setFlash('success', 'Budget updated.');
        } else {
            $budgetId = BudgetController::create($data);
            setFlash('success', 'Budget created. Now add analytical lines.');
        }
        redirect('/Furniture/views/budgets/form.php?id=' . $budgetId);
    }
    
    if ($action === 'add_line' && $budgetId) {
        $result = BudgetController::addLine($budgetId, [
            'analytical_model_id' => $_POST['analytical_model_id'],
            'type' => $_POST['type'],
            'budgeted_amount' => floatval($_POST['budgeted_amount'])
        ]);
        if ($result) {
            setFlash('success', 'Line added.');
        } else {
            setFlash('error', 'Failed to add line. Please run the database migration first: /Furniture/migrations/run_migration.php');
        }
        redirect('/Furniture/views/budgets/form.php?id=' . $budgetId);
    }
    
    if ($action === 'update_line') {
        BudgetController::updateLine($_POST['line_id'], [
            'analytical_model_id' => $_POST['analytical_model_id'],
            'type' => $_POST['type'],
            'budgeted_amount' => floatval($_POST['budgeted_amount'])
        ]);
        setFlash('success', 'Line updated.');
        redirect('/Furniture/views/budgets/form.php?id=' . $budgetId);
    }
    
    if ($action === 'delete_line') {
        BudgetController::deleteLine($_POST['line_id']);
        setFlash('success', 'Line removed.');
        redirect('/Furniture/views/budgets/form.php?id=' . $budgetId);
    }
}

// Get data for form
$costCenters = CostCenterController::getAll();
$analyticalModels = AnalyticalModelController::getAll('confirmed');
$lines = $budgetId ? BudgetController::getLinesWithComputed($budgetId) : [];

include __DIR__ . '/../layouts/header.php';

// Check if budget_lines table exists
$migrationNeeded = false;
try {
    dbFetchAll("SELECT 1 FROM budget_lines LIMIT 1");
} catch (Exception $e) {
    $migrationNeeded = true;
}
?>

<?php if ($migrationNeeded): ?>
<div class="form-card" style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; border-left: 4px solid #ef4444;">
    <strong>⚠️ Database Migration Required</strong>
    <p style="margin: 8px 0 0 0;">
        The new budget features require a database update. Please run the migration first:
        <a href="/Furniture/migrations/run_migration.php" target="_blank" style="color: #ef4444; font-weight: 600;">Run Migration</a>
    </p>
</div>
<?php endif; ?>

<style>
.form-card {
    background: rgba(255, 255, 255, 0.7);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(10px);
}
.form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
}
.form-row .form-group {
    flex: 1;
}
.lines-table {
    width: 100%;
    border-collapse: collapse;
}
.lines-table th, .lines-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.lines-table th {
    background: rgba(0,0,0,0.03);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    color: var(--text-secondary);
}
.type-income { color: #10b981; font-weight: 600; }
.type-expense { color: #ef4444; font-weight: 600; }
.achieved-value { color: #8b5cf6; font-weight: 600; }
.percent-value { 
    background: rgba(139, 92, 246, 0.1);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 13px;
    color: #8b5cf6;
    font-weight: 600;
}
.amount-to-achieve {
    color: #f97316;
    font-weight: 600;
}
.compute-badge {
    background: #f3f4f6;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    color: #6b7280;
}
.view-btn {
    padding: 4px 12px;
    background: #e5e7eb;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none;
    color: var(--text-primary);
}
.view-btn:hover {
    background: #d1d5db;
}
.status-banner {
    padding: 12px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.status-banner.confirmed {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #059669;
}
.status-banner.revised {
    background: rgba(139, 92, 246, 0.1);
    border: 1px solid rgba(139, 92, 246, 0.3);
    color: #7c3aed;
}
.add-line-form {
    background: rgba(0,0,0,0.02);
    padding: 16px;
    border-radius: 12px;
    margin-top: 16px;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <a href="/Furniture/views/budgets/index.php" class="btn btn-secondary">← Back</a>
        <h2 style="margin: 0;"><?= $pageTitle ?></h2>
    </div>
</div>

<?php if ($isConfirmed): ?>
    <div class="status-banner <?= $budget['status'] ?>">
        <span style="font-size: 20px;"><?= $budget['status'] === 'confirmed' ? '✅' : '📝' ?></span>
        <div>
            <strong>This budget is <?= ucfirst($budget['status']) ?></strong>
            <?php if ($budget['revised_from_name']): ?>
                <br><small>Revised from: <?= sanitize($budget['revised_from_name']) ?></small>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Budget Details Form -->
<div class="form-card">
    <h3 style="margin-top: 0;">Budget Details</h3>
    <form method="POST">
        <input type="hidden" name="action" value="save_budget">
        
        <div class="form-row">
            <div class="form-group">
                <label for="name">Budget Name *</label>
                <input type="text" name="name" id="name" class="form-control" required
                       value="<?= $budget ? sanitize($budget['name']) : '' ?>"
                       placeholder="e.g., IT Budget Q1 2026"
                       <?= $isConfirmed ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
                <label for="cost_center_id">Cost Center *</label>
                <select name="cost_center_id" id="cost_center_id" class="form-control" required
                        <?= $isConfirmed ? 'disabled' : '' ?>>
                    <option value="">-- Select Cost Center --</option>
                    <?php foreach ($costCenters as $cc): ?>
                        <option value="<?= $cc['id'] ?>" <?= ($budget && $budget['cost_center_id'] == $cc['id']) ? 'selected' : '' ?>>
                            <?= sanitize($cc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($isConfirmed && $budget): ?>
                    <input type="hidden" name="cost_center_id" value="<?= $budget['cost_center_id'] ?>">
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="amount">Budget Amount *</label>
                <input type="number" name="amount" id="amount" class="form-control" required
                       step="0.01" min="0"
                       value="<?= $budget ? $budget['amount'] : '' ?>"
                       placeholder="e.g., 500000"
                       <?= $isConfirmed ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date *</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required
                       value="<?= $budget ? $budget['start_date'] : date('Y-m-01') ?>"
                       <?= $isConfirmed ? 'readonly' : '' ?>>
            </div>
            <div class="form-group">
                <label for="end_date">End Date *</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required
                       value="<?= $budget ? $budget['end_date'] : date('Y-m-t') ?>"
                       <?= $isConfirmed ? 'readonly' : '' ?>>
            </div>
        </div>
        
        <?php if (!$isConfirmed): ?>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Budget' : 'Create Budget' ?></button>
        <?php endif; ?>
    </form>
</div>

<?php if ($budgetId): ?>
<!-- Analytical Lines Table (View Only for Confirmed/Revised Budgets) -->
<?php if ($isConfirmed && !empty($lines)): ?>
<div class="form-card">
    <h3 style="margin-top: 0;">Analytical Lines</h3>
    
    <table class="lines-table">
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
                        <span class="type-<?= $line['type'] ?>"><?= ucfirst($line['type']) ?></span>
                    </td>
                    <td><?= formatCurrency($line['budgeted_amount']) ?></td>
                    <td>
                        <span class="achieved-value"><?= formatCurrency($line['achieved_amount']) ?></span>
                        <a href="/Furniture/views/budgets/line_documents.php?line_id=<?= $line['id'] ?>&budget_id=<?= $budgetId ?>" 
                           class="view-btn">View</a>
                    </td>
                    <td>
                        <span class="percent-value"><?= $line['achieved_percent'] ?>%</span>
                    </td>
                    <td>
                        <?php if ($line['type'] === 'income'): ?>
                            <span class="amount-to-achieve"><?= formatCurrency($line['amount_to_achieve']) ?></span>
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
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

