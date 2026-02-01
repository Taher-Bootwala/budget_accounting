<?php
/**
 * Vendor Portal - Product Management
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

requirePortal();

$userId = getCurrentUserId();
$portalAccess = dbFetchOne("
    SELECT pa.*, c.id as contact_id, c.type as contact_type
    FROM portal_access pa
    JOIN contacts c ON pa.contact_id = c.id
    WHERE pa.user_id = ?
", [$userId]);

if (!$portalAccess) {
    die("Access denied.");
}

// Enforce Vendor Role
if ($portalAccess['contact_type'] !== 'vendor' && $portalAccess['contact_type'] !== 'both') {
    setFlash('error', 'Access denied: Partner privileges required.');
    redirect('/Furniture/portal/index.php');
}
$vendorId = $portalAccess['contact_id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = $_POST['name'];
        $price = floatval($_POST['price']);
        $category = $_POST['category'] ?? 'General';
        
        dbInsert("INSERT INTO products (name, category, price, type, vendor_id) VALUES (?, ?, ?, 'purchase', ?)", 
            [$name, $category, $price, $vendorId]);
            
        setFlash('success', 'Product added successfully.');
        redirect('/Furniture/portal/my_products.php');
        
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        // Verify ownership
        $p = dbFetchOne("SELECT id FROM products WHERE id = ? AND vendor_id = ?", [$id, $vendorId]);
        if ($p) {
            dbExecute("DELETE FROM products WHERE id = ?", [$id]);
            setFlash('success', 'Product deleted.');
        } else {
            setFlash('error', 'Unauthorized deletion.');
        }
        redirect('/Furniture/portal/my_products.php');
    }
}

// Fetch Vendor Products
$products = dbFetchAll("SELECT * FROM products WHERE vendor_id = ? ORDER BY id DESC", [$vendorId]);
$pageTitle = 'My Products';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/Furniture/assets/css/style.css">
    <style>
        .product-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 20px;
            margin-top: 24px;
        }
        .product-card {
            background: rgba(255,255,255,0.6);
            border: 1px solid rgba(255,255,255,0.4);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-2px);
            background: rgba(255,255,255,0.8);
        }
    </style>
</head>
<body>
    <div class="fluid-background"></div>
    
    <div class="dashboard-frame" style="flex-direction: column;">
        
        <!-- Header -->
        <div style="padding: 30px 40px; border-bottom: 1px solid rgba(255,255,255,0.2); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="/Furniture/portal/index.php" style="color: var(--text-primary); text-decoration: none; display: flex; align-items: center; gap: 8px;">
                     <i class="ri-arrow-left-line"></i> Back
                </a>
                <h2 style="margin: 0;">My Products</h2>
            </div>
            <button onclick="document.getElementById('addProductModal').classList.add('show')" class="btn btn-primary">
                <i class="ri-add-line"></i> Add Product
            </button>
        </div>

        <div class="dashboard-content" style="padding: 40px;">
            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 60px; color: var(--text-secondary);">
                    <i class="ri-box-3-line" style="font-size: 48px; opacity: 0.5;"></i>
                    <p style="margin-top: 16px;">You haven't added any products yet.</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <div class="product-card">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                <span class="badge badge-info"><?= sanitize($p['category']) ?></span>
                                <form method="POST" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" style="background: none; border: none; cursor: pointer; color: var(--danger);">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </form>
                            </div>
                            <h3 style="font-size: 18px; margin: 0 0 8px;"><?= sanitize($p['name']) ?></h3>
                            <div style="font-size: 20px; font-weight: 700; color: var(--primary);">
                                <?= formatCurrency($p['price']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal-overlay" id="addProductModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3>Add New Product</h3>
                <button class="modal-close" onclick="document.getElementById('addProductModal').classList.remove('show')">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Office Chair">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control form-select">
                            <option value="Furniture">Furniture</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Services">Services</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Price *</label>
                        <input type="number" name="price" class="form-control" required step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addProductModal').classList.remove('show')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
