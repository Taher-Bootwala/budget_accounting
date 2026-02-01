<?php
/**
 * Auto Analytical Model Form
 * Create/Edit with 4 optional matching criteria
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/AnalyticalModelController.php';
require_once __DIR__ . '/../../controllers/CostCenterController.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../controllers/ContactController.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$model = $id ? AnalyticalModelController::getById($id) : null;
$pageTitle = $model ? 'Edit Analytical Model' : 'New Analytical Model';

// Get data for dropdowns
$costCenters = CostCenterController::getAll();
$products = ProductController::getAll();
$categories = AnalyticalModelController::getProductCategories();
$contacts = ContactController::getAll();
$contactTags = AnalyticalModelController::getContactTags();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'],
        'status' => $_POST['status'] ?? 'draft',
        'partner_tag_id' => $_POST['partner_tag_id'] ?: null,
        'product_category' => $_POST['product_category'] ?: null,
        'partner_id' => $_POST['partner_id'] ?: null,
        'product_id' => $_POST['product_id'] ?: null,
        'cost_center_id' => intval($_POST['cost_center_id'])
    ];

    if ($id) {
        AnalyticalModelController::update($id, $data);
        setFlash('success', 'Model updated.');
    } else {
        AnalyticalModelController::create($data);
        setFlash('success', 'Model created.');
    }
    redirect('/Furniture/views/analytical_models/index.php');
}

include __DIR__ . '/../layouts/header.php';
?>

<style>
.form-card {
    background: rgba(255, 255, 255, 0.8);
    border-radius: 24px;
    padding: 32px;
    max-width: 800px;
    border: 1px solid rgba(255, 255, 255, 0.6);
}
.form-section {
    margin-bottom: 32px;
}
.form-section-title {
    font-size: 14px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--accent-wood);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--accent-oak);
}
.criteria-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.hint {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 4px;
}
.priority-info {
    background: linear-gradient(135deg, var(--accent-wood) 0%, var(--accent-walnut) 100%);
    color: white;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}
</style>

<div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px;">
    <a href="/Furniture/views/analytical_models/index.php" class="btn btn-secondary">← Back</a>
    <h2 style="margin: 0;"><?= $pageTitle ?></h2>
</div>

<div class="priority-info">
    <strong>📊 Priority System:</strong> The more criteria you select, the more specific (higher priority) this rule becomes. 
    Rules with more criteria take precedence over generic rules.
</div>

<div class="form-card">
    <form method="POST">
        <!-- Basic Info -->
        <div class="form-section">
            <div class="form-section-title">Basic Information</div>
            <div class="form-group">
                <label class="form-label">Model Name * <small style="font-weight: normal; color: var(--text-light);">(Auto-generated)</small></label>
                <input type="text" name="name" id="modelName" class="form-control" required
                       value="<?= sanitize($model['name'] ?? '') ?>"
                       placeholder="Will be auto-generated based on your selections">
            </div>
        </div>

        <!-- Matching Criteria -->
        <div class="form-section">
            <div class="form-section-title">Matching Criteria (Optional - Select any combination)</div>
            
            <div class="criteria-row">
                <div class="form-group">
                    <label class="form-label">Partner Tag</label>
                    <select name="partner_tag_id" id="partnerTag" class="form-control form-select">
                        <option value="">-- Any Partner Tag --</option>
                        <?php foreach ($contactTags as $tag): ?>
                            <option value="<?= $tag['id'] ?>" <?= ($model['partner_tag_id'] ?? '') == $tag['id'] ? 'selected' : '' ?>>
                                <?= sanitize($tag['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">Matches partners with this tag</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product Category</label>
                    <select name="product_category" id="productCategory" class="form-control form-select">
                        <option value="">-- Any Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= sanitize($cat['category']) ?>" <?= ($model['product_category'] ?? '') === $cat['category'] ? 'selected' : '' ?>>
                                <?= sanitize($cat['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">Matches products in this category</div>
                </div>
            </div>
            
            <div class="criteria-row">
                <div class="form-group">
                    <label class="form-label">Specific Partner</label>
                    <select name="partner_id" id="partnerId" class="form-control form-select">
                        <option value="">-- Any Partner --</option>
                        <?php foreach ($contacts as $contact): ?>
                            <option value="<?= $contact['id'] ?>" <?= ($model['partner_id'] ?? '') == $contact['id'] ? 'selected' : '' ?>>
                                <?= sanitize($contact['name']) ?> (<?= ucfirst($contact['type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">Matches this specific partner only</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Specific Product</label>
                    <select name="product_id" id="productId" class="form-control form-select">
                        <option value="">-- Any Product --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>" <?= ($model['product_id'] ?? '') == $product['id'] ? 'selected' : '' ?>>
                                <?= sanitize($product['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">Matches this specific product only</div>
                </div>
            </div>
        </div>

        <!-- Allocation Target -->
        <div class="form-section">
            <div class="form-section-title">Auto Apply Analytical Model</div>
            <div class="form-group">
                <label class="form-label">Cost Center to Apply *</label>
                <select name="cost_center_id" id="costCenter" class="form-control form-select" required>
                    <option value="">-- Select Cost Center --</option>
                    <?php foreach ($costCenters as $cc): ?>
                        <option value="<?= $cc['id'] ?>" <?= ($model['cost_center_id'] ?? '') == $cc['id'] ? 'selected' : '' ?>>
                            <?= sanitize($cc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint">When criteria match, transactions will be allocated to this cost center</div>
            </div>
        </div>

        <!-- Status -->
        <div class="form-section">
            <div class="form-section-title">Status</div>
            <div class="form-group">
                <select name="status" class="form-control form-select">
                    <option value="draft" <?= ($model['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="confirmed" <?= ($model['status'] ?? '') === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="cancelled" <?= ($model['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <div class="hint">Only confirmed models are active and used for matching</div>
            </div>
        </div>

        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Save Model</button>
            <a href="/Furniture/views/analytical_models/index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
// Auto-generate model name based on selected criteria
function generateModelName() {
    const partnerTag = document.getElementById('partnerTag');
    const productCategory = document.getElementById('productCategory');
    const partnerId = document.getElementById('partnerId');
    const productId = document.getElementById('productId');
    const costCenter = document.getElementById('costCenter');
    const modelName = document.getElementById('modelName');
    
    let parts = [];
    
    // Add selected criteria to name
    if (productId.value) {
        parts.push(productId.options[productId.selectedIndex].text.split('(')[0].trim());
    }
    if (partnerId.value) {
        parts.push('for ' + partnerId.options[partnerId.selectedIndex].text.split('(')[0].trim());
    }
    if (productCategory.value) {
        parts.push(productCategory.value);
    }
    if (partnerTag.value) {
        parts.push(partnerTag.options[partnerTag.selectedIndex].text + ' partners');
    }
    
    // Add cost center
    if (costCenter.value) {
        parts.push('→ ' + costCenter.options[costCenter.selectedIndex].text);
    }
    
    // Build final name
    if (parts.length > 0) {
        modelName.value = parts.join(' ');
    } else {
        modelName.value = '';
    }
}

// Add event listeners to all criteria selects
document.getElementById('partnerTag').addEventListener('change', generateModelName);
document.getElementById('productCategory').addEventListener('change', generateModelName);
document.getElementById('partnerId').addEventListener('change', generateModelName);
document.getElementById('productId').addEventListener('change', generateModelName);
document.getElementById('costCenter').addEventListener('change', generateModelName);

// Generate initial name if editing (only if name is empty)
<?php if (!$model): ?>
// For new models, generate name on first selection
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
