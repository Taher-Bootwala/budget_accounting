<?php
/**
 * Cost Center Drill-Down View
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/BudgetEngine.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id) {
    redirect('/Furniture/views/cost_centers/index.php');
}

$data = BudgetEngine::getCostCenterDrilldown($id);
$pageTitle = 'Cost Center: ' . ($data['cost_center']['name'] ?? 'Unknown');

include __DIR__ . '/../layouts/header.php';
?>

<style>
.drilldown-card {
    background: rgba(255, 255, 255, 0.7);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(10px);
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 20px;
}
.stat-box {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.stat-box .value {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 4px;
}
.stat-box .label {
    font-size: 13px;
    color: var(--text-secondary);
    text-transform: uppercase;
}
.tables-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
@media (max-width: 900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .tables-grid { grid-template-columns: 1fr; }
}
</style>

<a href="/Furniture/views/cost_centers/index.php" class="btn btn-secondary btn-sm" style="margin-bottom: 20px;">
    ← Back to Cost Centers
</a>

<!-- Summary Card -->
<div class="drilldown-card">
    <h2 style="margin: 0 0 8px 0;"><?= sanitize($data['cost_center']['name']) ?></h2>
    <?php if (!empty($data['cost_center']['description'])): ?>
        <p style="color: var(--text-secondary); margin: 0;">
            <?= sanitize($data['cost_center']['description']) ?>
        </p>
    <?php endif; ?>
    
    <div class="stats-grid">
        <div class="stat-box">
            <div class="value"><?= formatCurrency($data['summary']['budget_amount']) ?></div>
            <div class="label">Total Budget</div>
        </div>
        <div class="stat-box">
            <div class="value"><?= formatCurrency($data['summary']['actual_spend']) ?></div>
            <div class="label">Actual Spend</div>
        </div>
        <div class="stat-box">
            <div class="value" style="color: <?= $data['summary']['remaining'] < 0 ? '#ef4444' : '#10b981' ?>;">
                <?= formatCurrency($data['summary']['remaining']) ?>
            </div>
            <div class="label"><?= $data['summary']['remaining'] < 0 ? 'Over Budget' : 'Remaining' ?></div>
        </div>
        <div class="stat-box">
            <div class="value">
                <span style="background: <?= $data['summary']['utilization'] > 90 ? '#fef2f2' : ($data['summary']['utilization'] > 70 ? '#fffbeb' : '#f0fdf4') ?>; 
                             color: <?= $data['summary']['utilization'] > 90 ? '#ef4444' : ($data['summary']['utilization'] > 70 ? '#f59e0b' : '#10b981') ?>; 
                             padding: 8px 16px; border-radius: 50px; font-size: 20px;">
                    <?= $data['summary']['utilization'] ?>%
                </span>
            </div>
            <div class="label">Utilization</div>
        </div>
    </div>
    
    <?php if ($data['budget']): ?>
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.05);">
            <strong>Budget Period:</strong> 
            <?= formatDate($data['budget']['start_date']) ?> — <?= formatDate($data['budget']['end_date']) ?>
            
            <?php $timeElapsed = getTimeElapsedPercentage($data['budget']['start_date'], $data['budget']['end_date']); ?>
            
            <div style="margin-top: 12px; display: flex; gap: 20px; align-items: center;">
                <div style="flex: 1;">
                    <div style="background: #e5e7eb; height: 8px; border-radius: 4px; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 0; left: 0; height: 100%; width: <?= $timeElapsed ?>%; background: #94a3b8;"></div>
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                        Time Elapsed: <?= round($timeElapsed, 1) ?>%
                    </div>
                </div>
                <div style="flex: 1;">
                    <div style="background: #e5e7eb; height: 8px; border-radius: 4px; position: relative; overflow: hidden;">
                        <div style="position: absolute; top: 0; left: 0; height: 100%; width: <?= min($data['summary']['utilization'], 100) ?>%; 
                                    background: <?= $data['summary']['utilization'] > 90 ? '#ef4444' : ($data['summary']['utilization'] > 70 ? '#f59e0b' : '#10b981') ?>;"></div>
                    </div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                        Budget Used: <?= $data['summary']['utilization'] ?>%
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div style="margin-top: 20px; padding: 16px; background: rgba(255, 248, 231, 0.8); border-radius: 8px; border-left: 4px solid var(--accent-wood);">
            <strong>⚠️ No confirmed budget found for this cost center.</strong>
            <p style="margin: 8px 0 0 0; font-size: 14px;">
                Create and confirm a budget for this cost center to track utilization.
                <a href="/Furniture/views/budgets/form.php">Create Budget</a>
            </p>
        </div>
    <?php endif; ?>
</div>

<!-- Tables Grid -->
<div class="tables-grid">
    <!-- Recent Transactions -->
    <div class="drilldown-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0;">📋 Recent Transactions</h3>
        </div>
        
        <?php if (empty($data['transactions'])): ?>
            <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                No transactions found.
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Document</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['transactions'] as $t): ?>
                        <tr>
                            <td><?= formatDate($t['transaction_date']) ?></td>
                            <td>
                                <a href="/Furniture/views/documents/view.php?id=<?= $t['document_id'] ?>" class="clickable">
                                    <?= sanitize($t['document_number']) ?>
                                </a>
                            </td>
                            <td><?= formatCurrency($t['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Related Documents -->
    <div class="drilldown-card">
        <h3 style="margin: 0 0 16px 0;">📄 Related Documents</h3>
        
        <?php if (empty($data['documents'])): ?>
            <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                No documents found.
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['documents'] as $doc): ?>
                        <tr>
                            <td>
                                <a href="/Furniture/views/documents/view.php?id=<?= $doc['id'] ?>" class="clickable">
                                    <?= sanitize($doc['document_number']) ?>
                                </a>
                            </td>
                            <td>
                                <span style="background: <?= $doc['doc_type'] === 'VendorBill' ? '#fef2f2' : '#f0fdf4' ?>; 
                                             color: <?= $doc['doc_type'] === 'VendorBill' ? '#ef4444' : '#10b981' ?>;
                                             padding: 2px 8px; border-radius: 12px; font-size: 12px;">
                                    <?= sanitize($doc['doc_type']) ?>
                                </span>
                            </td>
                            <td><?= sanitize($doc['contact_name'] ?? '-') ?></td>
                            <td><?= formatCurrency($doc['total_amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>