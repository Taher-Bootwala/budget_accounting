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

<div class="glass-widget anim-fade-up" style="max-width: 800px; margin: 0 auto; padding: 40px;">
    <div style="margin-bottom: 32px; text-align: center;">
        <h1 style="font-size: 28px; background: linear-gradient(135deg, var(--text-primary), var(--accent-wood)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 8px;">
            <?= $pageTitle ?>
        </h1>
        <p style="color: var(--text-secondary);">Fill in the details below to <?= $id ? 'update' : 'create' ?> a product</p>
    </div>

    <form method="POST">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 24px;">
            <div class="form-group">
                <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px; display: block;">Product Name *</label>
                <div class="input-icon-wrapper" style="position: relative;">
                    <i class="ri-shopping-bag-3-line" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="text" name="name" class="form-control" value="<?= sanitize($product['name'] ?? '') ?>" 
                        placeholder="e.g. Executive Desk" required
                        style="padding-left: 44px; height: 48px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); background: rgba(255,255,255,0.5); font-size: 15px;">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px; display: block;">Category</label>
                <div class="input-icon-wrapper" style="position: relative;">
                    <i class="ri-folder-line" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary);"></i>
                    <input type="text" name="category" class="form-control" value="<?= sanitize($product['category'] ?? '') ?>"
                        placeholder="e.g. Furniture"
                        style="padding-left: 44px; height: 48px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); background: rgba(255,255,255,0.5); font-size: 15px;">
                </div>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 32px;">
            <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: 8px; display: block;">Selling Price *</label>
            <div class="input-icon-wrapper" style="position: relative;">
                <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); font-weight: 600;">₹</span>
                <input type="number" name="price" class="form-control" step="0.01" value="<?= $product['price'] ?? '0.00' ?>" required
                    style="padding-left: 36px; height: 48px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.1); background: rgba(255,255,255,0.5); font-size: 16px; font-weight: 600;">
            </div>
        </div>

        <div style="display: flex; gap: 16px; padding-top: 24px; border-top: 1px solid rgba(0,0,0,0.05);">
            <button type="submit" class="btn btn-primary" style="flex: 1; height: 48px; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="ri-save-line"></i> Save Product
            </button>
            <a href="/Furniture/views/products/index.php" class="btn btn-secondary" style="padding: 0 32px; height: 48px; display: flex; align-items: center; justify-content: center;">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>