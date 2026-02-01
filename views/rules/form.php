<?php
/**
 * Auto Analytical Rule Form
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/RuleController.php';
require_once __DIR__ . '/../../controllers/CostCenterController.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$rule = $id ? RuleController::getById($id) : null;
$pageTitle = $rule ? 'Edit Rule' : 'New Rule';

$costCenters = CostCenterController::getAll();
$products = ProductController::getAll();
$categories = ProductController::getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'rule_type' => $_POST['rule_type'],
        'rule_value' => $_POST['rule_value'],
        'cost_center_id' => intval($_POST['cost_center_id'])
    ];

    if ($id) {
        RuleController::update($id, $data);
        setFlash('success', 'Rule updated.');
    } else {
        RuleController::create($data);
        setFlash('success', 'Rule created.');
    }
    redirect('/Furniture/views/rules/index.php');
}

include __DIR__ . '/../layouts/header.php';
?>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">
            <?= $pageTitle ?>
        </h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Rule Type *</label>
                <select name="rule_type" id="ruleType" class="form-control form-select" required>
                    <option value="product" <?= ($rule['rule_type'] ?? '') === 'product' ? 'selected' : '' ?>>Product
                    </option>
                    <option value="category" <?= ($rule['rule_type'] ?? '') === 'category' ? 'selected' : '' ?>>Category
                    </option>
                </select>
            </div>

            <div class="form-group" id="productGroup">
                <label class="form-label">Product *</label>
                <select name="rule_value" class="form-control form-select">
                    <option value="">Select Product</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($rule['rule_value'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                            <?= sanitize($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="categoryGroup" style="display: none;">
                <label class="form-label">Category *</label>
                <select name="rule_value_cat" class="form-control form-select">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= sanitize($c['category']) ?>" <?= ($rule['rule_value'] ?? '') === $c['category'] ? 'selected' : '' ?>>
                            <?= sanitize($c['category']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Assign to Cost Center *</label>
                <select name="cost_center_id" class="form-control form-select" required>
                    <option value="">Select Cost Center</option>
                    <?php foreach ($costCenters as $cc): ?>
                        <option value="<?= $cc['id'] ?>" <?= ($rule['cost_center_id'] ?? '') == $cc['id'] ? 'selected' : '' ?>
                            >
                            <?= sanitize($cc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Save Rule</button>
                <a href="/Furniture/views/rules/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('ruleType').addEventListener('change', function () {
        const productGroup = document.getElementById('productGroup');
        const categoryGroup = document.getElementById('categoryGroup');

        if (this.value === 'product') {
            productGroup.style.display = 'block';
            categoryGroup.style.display = 'none';
            productGroup.querySelector('select').name = 'rule_value';
            categoryGroup.querySelector('select').name = 'rule_value_cat';
        } else {
            productGroup.style.display = 'none';
            categoryGroup.style.display = 'block';
            productGroup.querySelector('select').name = 'rule_value_prod';
            categoryGroup.querySelector('select').name = 'rule_value';
        }
    });

// Initialize on load
<?php if (($rule['rule_type'] ?? 'product') === 'category'): ?>
            document.getElementById('ruleType').dispatchEvent(new Event('change'));
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>