<?php
/**
 * Product Form
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$product = $id ? ProductController::getById($id) : null;
$pageTitle = $product ? 'Edit Product' : 'New Product';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'category' => trim($_POST['category']),
        'price' => floatval($_POST['price'])
    ];

    if ($id) {
        ProductController::update($id, $data);
        setFlash('success', 'Product updated.');
    } else {
        ProductController::create($data);
        setFlash('success', 'Product created.');
    }
    redirect('/Furniture/views/products/index.php');
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
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" value="<?= sanitize($product['name'] ?? '') ?>"
                    required>
            </div>
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control"
                    value="<?= sanitize($product['category'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Price *</label>
                <input type="number" name="price" class="form-control" step="0.01"
                    value="<?= $product['price'] ?? '0.00' ?>" required>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="/Furniture/views/products/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>