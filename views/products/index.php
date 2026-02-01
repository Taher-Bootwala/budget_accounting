<?php
/**
 * ============================================================================
 * PRODUCT MASTER - LIST VIEW
 * ============================================================================
 * 
 * Beautiful glassmorphism list view for managing products with:
 * - New/Archived filter tabs
 * - Animated table rows
 * - Quick actions (edit, archive)
 * 
 * @author    Shiv Furniture ERP
 * @version   1.0.0
 * ============================================================================
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

requireAdmin();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = $_POST['product_id'] ?? 0;
    
    // Auto-add archived column if it doesn't exist
    if (($action === 'archive' || $action === 'restore') && $productId) {
        try {
            $columns = dbFetchAll("SHOW COLUMNS FROM products LIKE 'archived'");
            if (empty($columns)) {
                dbExecute("ALTER TABLE products ADD COLUMN archived TINYINT(1) DEFAULT 0");
            }
        } catch (Exception $e) {
            // Column might already exist or other error
        }
    }
    
    if ($action === 'archive' && $productId) {
        try {
            dbExecute("UPDATE products SET archived = 1 WHERE id = ?", [$productId]);
            setFlash('success', 'Product archived successfully.');
        } catch (Exception $e) {
            setFlash('error', 'Could not archive product.');
        }
        redirect('/Furniture/views/products/index.php');
    }
    
    if ($action === 'restore' && $productId) {
        try {
            dbExecute("UPDATE products SET archived = 0 WHERE id = ?", [$productId]);
            setFlash('success', 'Product restored successfully.');
        } catch (Exception $e) {
            setFlash('error', 'Could not restore product.');
        }
        redirect('/Furniture/views/products/index.php?tab=archived');
    }
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $purchase_price = floatval($_POST['purchase_price'] ?? 0);
        
        if ($name && $category) {
            dbExecute(
                "INSERT INTO products (name, category, price) VALUES (?, ?, ?)",
                [$name, $category, $price]
            );
            setFlash('success', 'Product created successfully!');
            redirect('/Furniture/views/products/index.php');
        }
    }
}

$activeTab = $_GET['tab'] ?? 'active';
$searchQuery = $_GET['q'] ?? '';

// Fetch products with proper archived filtering
try {
    if ($activeTab === 'archived') {
        $products = dbFetchAll("SELECT * FROM products WHERE archived = 1 ORDER BY name ASC");
    } else {
        $products = dbFetchAll("SELECT * FROM products WHERE archived = 0 OR archived IS NULL ORDER BY name ASC");
    }
} catch (Exception $e) {
    // If archived column doesn't exist, fetch all for active tab, empty for archived
    if ($activeTab === 'archived') {
        $products = [];
    } else {
        $products = dbFetchAll("SELECT * FROM products ORDER BY name ASC");
    }
}

// Get categories for dropdown
try {
    $categories = dbFetchAll("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
} catch (Exception $e) {
    $categories = [];
}

$pageTitle = 'Product Master';
include __DIR__ . '/../layouts/header.php';
?>

<style>
/* Master List Styles */
.master-container {
    max-width: 1200px;
    margin: 0 auto;
}

.master-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;
}

.master-title {
    display: flex;
    align-items: center;
    gap: 16px;
}

.master-title h1 {
    font-size: 36px;
    font-weight: 600;
    background: linear-gradient(135deg, var(--text-primary), var(--accent-wood));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.master-title-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, var(--accent-wood), var(--accent-oak));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 26px;
    box-shadow: 0 8px 24px rgba(139, 90, 43, 0.25);
}

.header-actions {
    display: flex;
    gap: 12px;
}

/* Tab Filters */
.filter-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
}

.filter-tab {
    padding: 10px 24px;
    background: rgba(255, 255, 255, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-tab:hover {
    background: rgba(255, 255, 255, 0.8);
    color: var(--text-primary);
    transform: translateY(-2px);
}

.filter-tab.active {
    background: var(--text-primary);
    color: white;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.filter-tab .count {
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 12px;
}

.filter-tab.active .count {
    background: rgba(255, 255, 255, 0.25);
}

/* List Card */
.list-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    animation: slideUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
    opacity: 0;
    transform: translateY(30px);
}

@keyframes slideUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Search Bar */
.search-bar {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    gap: 16px;
    align-items: center;
}

.search-input-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.6);
    border: 1px solid rgba(0, 0, 0, 0.05);
    border-radius: 12px;
    padding: 0 16px;
    transition: all 0.3s ease;
}

.search-input-wrapper:focus-within {
    background: white;
    border-color: var(--accent-wood);
    box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
}

.search-input-wrapper i {
    color: var(--text-secondary);
    font-size: 18px;
}

.search-input-wrapper input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px;
    font-size: 14px;
    color: var(--text-primary);
    outline: none;
}

/* Table Styles */
.list-table {
    width: 100%;
    border-collapse: collapse;
}

.list-table thead th {
    background: rgba(139, 90, 43, 0.05);
    padding: 16px 24px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.list-table tbody tr {
    transition: all 0.3s ease;
    animation: fadeInRow 0.5s ease forwards;
    opacity: 0;
}

.list-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
.list-table tbody tr:nth-child(2) { animation-delay: 0.15s; }
.list-table tbody tr:nth-child(3) { animation-delay: 0.2s; }
.list-table tbody tr:nth-child(4) { animation-delay: 0.25s; }
.list-table tbody tr:nth-child(5) { animation-delay: 0.3s; }
.list-table tbody tr:nth-child(n+6) { animation-delay: 0.35s; }

@keyframes fadeInRow {
    to {
        opacity: 1;
    }
}

.list-table tbody tr:hover {
    background: rgba(139, 90, 43, 0.03);
}

.list-table tbody td {
    padding: 18px 24px;
    font-size: 14px;
    color: var(--text-primary);
    border-bottom: 1px solid rgba(0, 0, 0, 0.03);
    vertical-align: middle;
}

.list-table tbody tr:last-child td {
    border-bottom: none;
}

/* Product Name Cell */
.product-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.product-icon {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, rgba(139, 90, 43, 0.1), rgba(193, 154, 107, 0.2));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent-wood);
    font-size: 20px;
    flex-shrink: 0;
}

.product-info h4 {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.product-info span {
    font-size: 12px;
    color: var(--text-secondary);
}

/* Category Badge */
.category-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: rgba(139, 90, 43, 0.08);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: var(--accent-wood);
}

/* Price Display */
.price-display {
    font-weight: 700;
    font-size: 15px;
    color: var(--text-primary);
}

.price-display.sales {
    color: var(--success);
}

/* Action Buttons */
.action-btns {
    display: flex;
    gap: 8px;
}

.action-btn-small {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: all 0.3s ease;
}

.action-btn-small.edit {
    background: rgba(59, 130, 246, 0.1);
    color: #3b82f6;
}

.action-btn-small.edit:hover {
    background: #3b82f6;
    color: white;
    transform: translateY(-2px);
}

.action-btn-small.archive {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.action-btn-small.archive:hover {
    background: #ef4444;
    color: white;
    transform: translateY(-2px);
}

.action-btn-small.restore {
    background: rgba(46, 125, 50, 0.1);
    color: var(--success);
}

.action-btn-small.restore:hover {
    background: var(--success);
    color: white;
    transform: translateY(-2px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 40px;
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    background: rgba(139, 90, 43, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: var(--accent-wood);
}

.empty-state h3 {
    font-size: 18px;
    margin-bottom: 8px;
    color: var(--text-primary);
}

.empty-state p {
    color: var(--text-secondary);
    font-size: 14px;
}

/* Modal */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(62, 39, 35, 0.5);
    backdrop-filter: blur(8px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-overlay.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 24px;
    width: 90%;
    max-width: 500px;
    padding: 32px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: modalSlide 0.3s ease;
}

@keyframes modalSlide {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.modal-header h2 {
    font-size: 22px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-secondary);
    transition: color 0.2s;
}

.modal-close:hover {
    color: var(--text-primary);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Checkbox styling */
.checkbox-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
}

.checkbox-wrapper input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--accent-wood);
    cursor: pointer;
}
</style>

<div class="page-header anim-fade-up" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1>Products</h1>
        <p style="color: var(--text-secondary); font-size: 14px; margin-top: 4px;">Manage your product catalog</p>
    </div>
    <button class="btn btn-primary" onclick="openModal()">
        + New Product
    </button>
</div>

<div class="master-container">

    <!-- Filter Tabs -->
    <div class="filter-tabs anim-fade-up delay-1">
        <a href="?tab=active" class="filter-tab <?= $activeTab === 'active' ? 'active' : '' ?>">
            <i class="ri-checkbox-circle-line"></i> Active
            <span class="count"><?= count($activeTab === 'active' ? $products : []) ?: '' ?></span>
        </a>
        <a href="?tab=archived" class="filter-tab <?= $activeTab === 'archived' ? 'active' : '' ?>">
            <i class="ri-archive-line"></i> Archived
        </a>
    </div>

    <!-- List Card -->
    <div class="list-card">
        <!-- Search Bar -->
        <div class="search-bar">
            <div class="search-input-wrapper">
                <i class="ri-search-line"></i>
                <input type="text" id="searchInput" placeholder="Search products..." onkeyup="filterTable()">
            </div>
        </div>

        <!-- Table -->
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="ri-box-3-line"></i>
                </div>
                <h3>No products found</h3>
                <p>Start by adding your first product to the catalog.</p>
            </div>
        <?php else: ?>
            <table class="list-table" id="productTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                            </div>
                        </th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Sales Price</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr data-id="<?= $product['id'] ?>">
                            <td>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" class="row-checkbox" value="<?= $product['id'] ?>">
                                </div>
                            </td>
                            <td>
                                <div class="product-cell">
                                    <div class="product-icon">
                                        <i class="ri-shopping-bag-3-line"></i>
                                    </div>
                                    <div class="product-info">
                                        <h4><?= sanitize($product['name']) ?></h4>
                                        <span>ID: #<?= $product['id'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="category-badge">
                                    <i class="ri-folder-line"></i>
                                    <?= sanitize($product['category'] ?? 'Uncategorized') ?>
                                </span>
                            </td>
                            <td>
                                <span class="price-display sales">
                                    <?= formatCurrency($product['price'] ?? 0) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn-small edit" title="Edit" onclick="editProduct(<?= $product['id'] ?>)">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <?php if ($activeTab === 'archived'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="action-btn-small restore" title="Restore">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Archive this product?')">
                                            <input type="hidden" name="action" value="archive">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" class="action-btn-small archive" title="Archive">
                                                <i class="ri-archive-line"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- New Product Modal -->
<div class="modal-overlay" id="productModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="ri-add-circle-line" style="color: var(--accent-wood);"></i> New Product</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter product name" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= sanitize($cat['category']) ?>"><?= sanitize($cat['category']) ?></option>
                        <?php endforeach; ?>
                        <option value="Raw Materials">Raw Materials</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Accessories">Accessories</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Sales Price (₹)</label>
                    <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">
                <i class="ri-check-line"></i> Create Product
            </button>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('productModal').classList.add('show');
}

function closeModal() {
    document.getElementById('productModal').classList.remove('show');
}

function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('productTable');
    if (!table) return;
    
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }
        
        rows[i].style.display = found ? '' : 'none';
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function editProduct(id) {
    alert('Edit product #' + id + ' - Feature coming soon!');
}

// Close modal on outside click
document.getElementById('productModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>