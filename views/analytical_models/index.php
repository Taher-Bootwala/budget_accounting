<?php
/**
 * Auto Analytical Models List
 * With status tabs: Draft, Confirmed, Cancelled
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/AnalyticalModelController.php';

requireAdmin();

$pageTitle = 'Auto Analytical Models';

// Handle status change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'confirm') {
        AnalyticalModelController::updateStatus($id, 'confirmed');
        setFlash('success', 'Model confirmed.');
    } elseif ($action === 'cancel') {
        AnalyticalModelController::updateStatus($id, 'cancelled');
        setFlash('success', 'Model cancelled.');
    } elseif ($action === 'delete') {
        AnalyticalModelController::delete($id);
        setFlash('success', 'Model deleted.');
    }
    redirect('/Furniture/views/analytical_models/index.php');
}

// Get current tab
$currentTab = $_GET['tab'] ?? 'draft';
$models = AnalyticalModelController::getAll($currentTab);

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
.model-card {
    background: rgba(255, 255, 255, 0.7);
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 16px;
    border: 1px solid rgba(255, 255, 255, 0.5);
}
.model-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}
.model-name {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}
.criteria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.criteria-item {
    background: rgba(255, 255, 255, 0.5);
    padding: 12px;
    border-radius: 12px;
}
.criteria-label {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-secondary);
    margin-bottom: 4px;
}
.criteria-value {
    font-weight: 600;
    color: var(--text-primary);
}
.criteria-empty {
    color: var(--text-light);
    font-style: italic;
}
.allocation-box {
    background: linear-gradient(135deg, var(--accent-wood) 0%, var(--accent-walnut) 100%);
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.priority-badge {
    background: rgba(0,0,0,0.1);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    margin-left: 8px;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h2 style="margin: 0;">Auto Analytical Models</h2>
    <a href="/Furniture/views/analytical_models/form.php" class="btn btn-primary">+ New Model</a>
</div>

<div style="background: rgba(255,248,231,0.8); padding: 16px; border-radius: 12px; margin-bottom: 24px; border-left: 4px solid var(--accent-wood);">
    <strong>💡 How it works:</strong> Models automatically assign cost centers to transactions. 
    More matching criteria = higher priority. A rule matching Product + Partner beats a rule matching only Category.
</div>

<!-- Status Tabs -->
<div class="status-tabs">
    <a href="?tab=draft" class="status-tab <?= $currentTab === 'draft' ? 'active' : '' ?>">
        Draft
    </a>
    <a href="?tab=confirmed" class="status-tab <?= $currentTab === 'confirmed' ? 'active' : '' ?>">
        Confirmed
    </a>
    <a href="?tab=cancelled" class="status-tab <?= $currentTab === 'cancelled' ? 'active' : '' ?>">
        Cancelled
    </a>
</div>

<!-- Models List -->
<?php if (empty($models)): ?>
    <div class="model-card" style="text-align: center; padding: 40px;">
        <p style="color: var(--text-secondary); margin-bottom: 16px;">No <?= $currentTab ?> models found.</p>
        <?php if ($currentTab === 'draft'): ?>
            <a href="/Furniture/views/analytical_models/form.php" class="btn btn-primary">Create your first model</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($models as $model): ?>
        <div class="model-card">
            <div class="model-header">
                <div class="model-name">
                    <?= sanitize($model['name']) ?>
                    <?php 
                    $criteriaCount = 0;
                    if ($model['product_id']) $criteriaCount++;
                    if ($model['partner_id']) $criteriaCount++;
                    if ($model['product_category']) $criteriaCount++;
                    if ($model['partner_tag_id']) $criteriaCount++;
                    ?>
                    <span class="priority-badge">Priority: <?= $criteriaCount ?>/4</span>
                </div>
                <div style="display: flex; gap: 8px;">
                    <?php if ($currentTab === 'draft'): ?>
                        <a href="?action=confirm&id=<?= $model['id'] ?>" class="btn btn-primary btn-sm">Confirm</a>
                        <a href="/Furniture/views/analytical_models/form.php?id=<?= $model['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                    <?php endif; ?>
                    <?php if ($currentTab !== 'cancelled'): ?>
                        <a href="?action=cancel&id=<?= $model['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this model?')">Cancel</a>
                    <?php endif; ?>
                    <a href="?action=delete&id=<?= $model['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete permanently?')">Delete</a>
                </div>
            </div>
            
            <div class="criteria-grid">
                <div class="criteria-item">
                    <div class="criteria-label">Partner Tag</div>
                    <div class="<?= $model['partner_tag_name'] ? 'criteria-value' : 'criteria-empty' ?>">
                        <?= $model['partner_tag_name'] ? sanitize($model['partner_tag_name']) : 'Any' ?>
                    </div>
                </div>
                <div class="criteria-item">
                    <div class="criteria-label">Product Category</div>
                    <div class="<?= $model['product_category'] ? 'criteria-value' : 'criteria-empty' ?>">
                        <?= $model['product_category'] ? sanitize($model['product_category']) : 'Any' ?>
                    </div>
                </div>
                <div class="criteria-item">
                    <div class="criteria-label">Partner</div>
                    <div class="<?= $model['partner_name'] ? 'criteria-value' : 'criteria-empty' ?>">
                        <?= $model['partner_name'] ? sanitize($model['partner_name']) : 'Any' ?>
                    </div>
                </div>
                <div class="criteria-item">
                    <div class="criteria-label">Product</div>
                    <div class="<?= $model['product_name'] ? 'criteria-value' : 'criteria-empty' ?>">
                        <?= $model['product_name'] ? sanitize($model['product_name']) : 'Any' ?>
                    </div>
                </div>
            </div>
            
            <div class="allocation-box">
                🎯 Allocates to: <strong><?= sanitize($model['cost_center_name']) ?></strong>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
