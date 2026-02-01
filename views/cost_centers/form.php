<?php
/**
 * Cost Center Form (Create/Edit)
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../controllers/CostCenterController.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$costCenter = $id ? CostCenterController::getById($id) : null;
$isEdit = $costCenter !== null;

$errors = [];

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? '')
    ];

    // Validate
    if (empty($data['name'])) {
        $errors[] = 'Name is required.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            CostCenterController::update($id, $data);
            setFlash('success', 'Cost center updated.');
        } else {
            CostCenterController::create($data);
            setFlash('success', 'Cost center created.');
        }
        redirect('/Furniture/views/cost_centers/index.php');
    }
}

$pageTitle = $isEdit ? 'Edit Cost Center' : 'New Cost Center';
include __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? '✏️ Edit' : '➕ New' ?> Cost Center</h1>
    <a href="/Furniture/views/cost_centers/index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="flash-message error">
                <?php foreach ($errors as $e): ?>
                    <div><?= sanitize($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Name *</label>
                <input type="text" id="name" name="name" class="form-control" required
                    value="<?= sanitize($costCenter['name'] ?? $_POST['name'] ?? '') ?>">
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?> Cost Center</button>
                <a href="/Furniture/views/cost_centers/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>