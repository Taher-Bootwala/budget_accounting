<?php
/**
 * Create/Edit Document with Cost Center Preview
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../controllers/DocumentController.php';
require_once __DIR__ . '/../../controllers/ContactController.php';
require_once __DIR__ . '/../../controllers/ProductController.php';

requireAdmin();

$type = $_GET['type'] ?? 'CustomerInvoice';
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$document = $id ? DocumentController::getById($id) : null;
$pageTitle = $document ? 'Edit Document' : 'New ' . $type;

// Get only relevant contacts
$contactType = in_array($type, ['PO', 'VendorBill']) ? 'vendor' : 'customer';
$contacts = ContactController::getAll($contactType);
$products = ProductController::getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'doc_type' => $type,
        'contact_id' => intval($_POST['contact_id']),
        'total_amount' => floatval($_POST['total_amount'])
    ];
    
    $lines = [];
    if (isset($_POST['product_id'])) {
        foreach ($_POST['product_id'] as $i => $productId) {
            if (!$productId) continue;
            $lines[] = [
                'product_id' => intval($productId),
                'quantity' => intval($_POST['quantity'][$i]),
                'price' => floatval($_POST['price'][$i]),
                'line_total' => floatval($_POST['line_total'][$i])
            ];
        }
    }
    
    if ($id) {
        DocumentController::update($id, $data, $lines);
        setFlash('success', 'Document updated.');
    } else {
        $id = DocumentController::create($data, $lines);
        setFlash('success', 'Document created.');
    }
    redirect('/Furniture/views/documents/view.php?id=' . $id);
}

include __DIR__ . '/../layouts/header.php';
?>

<a href="/Furniture/views/documents/index.php" class="btn btn-secondary btn-sm" style="margin-bottom: 20px;">
    ← Back to Documents
</a>

<form method="POST" id="documentForm">
    <input type="hidden" name="total_amount" id="grandTotalInput" value="0">
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Main Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?= $pageTitle ?></h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label"><?= $contactType === 'vendor' ? 'Vendor' : 'Customer' ?> *</label>
                    <select name="contact_id" class="form-control form-select" required>
                        <option value="">Select <?= ucfirst($contactType) ?></option>
                        <?php foreach ($contacts as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($document['contact_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= sanitize($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <h4 style="margin: 24px 0 16px;">Line Items</h4>
                
                <div id="lineItemsContainer">
                    <?php if ($document && !empty($document['lines'])): ?>
                        <?php foreach ($document['lines'] as $i => $line): ?>
                            <div class="line-item" style="display: grid; grid-template-columns: 3fr 1fr 1fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: start;">
                                <select name="product_id[]" id="productSelect" class="form-control form-select line-product" onchange="updateLineTotal(this.parentElement)">
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>" <?= $line['product_id'] == $p['id'] ? 'selected' : '' ?>>
                                            <?= sanitize($p['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantity[]" class="form-control line-qty" placeholder="Qty" value="<?= $line['quantity'] ?>" onchange="updateLineTotal(this.parentElement)">
                                <input type="number" name="price[]" class="form-control line-price" placeholder="Price" step="0.01" value="<?= $line['price'] ?>" onchange="updateLineTotal(this.parentElement)">
                                <input type="number" name="line_total[]" class="form-control line-total" placeholder="Total" step="0.01" value="<?= $line['line_total'] ?>" readonly>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeLineItem(this)">✕</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-secondary" onclick="addLineItem()">+ Add Line</button>
                
                <!-- Cost Center Preview -->
                <div id="costCenterPreview" class="cost-center-preview" style="display: none;"></div>
            </div>
        </div>
        
        <!-- Summary Sidebar -->
        <div>
            <div class="card" style="position: sticky; top: 100px;">
                <div class="card-header">
                    <h3 class="card-title">Summary</h3>
                </div>
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                        <span>Document Type:</span>
                        <strong><?= $type ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 16px; border-top: 1px solid var(--light); padding-top: 16px;">
                        <span style="font-size: 18px;">Grand Total:</span>
                        <strong style="font-size: 24px; color: var(--primary);" id="grandTotal">₹0.00</strong>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        Save Document
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Line Item Template -->
<template id="lineItemTemplate">
    <div class="line-item" style="display: grid; grid-template-columns: 3fr 1fr 1fr 1fr auto; gap: 12px; margin-bottom: 12px; align-items: start;">
        <select name="product_id[]" id="productSelect" class="form-control form-select line-product" onchange="onProductChange(this)">
            <option value="">Select Product</option>
            <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= sanitize($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="quantity[]" class="form-control line-qty" placeholder="Qty" value="1" onchange="updateLineTotal(this.parentElement)">
        <input type="number" name="price[]" class="form-control line-price" placeholder="Price" step="0.01" onchange="updateLineTotal(this.parentElement)">
        <input type="number" name="line_total[]" class="form-control line-total" placeholder="Total" step="0.01" readonly>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeLineItem(this)">✕</button>
    </div>
</template>

<script>
// Add initial line if empty
<?php if (!$document): ?>
document.addEventListener('DOMContentLoaded', () => addLineItem());
<?php else: ?>
document.addEventListener('DOMContentLoaded', () => updateTotals());
<?php endif; ?>

function onProductChange(select) {
    const lineItem = select.parentElement;
    const option = select.options[select.selectedIndex];
    
    if (option.dataset.price) {
        lineItem.querySelector('.line-price').value = option.dataset.price;
    }
    
    updateLineTotal(lineItem);
    updateCostCenterPreview(select.value);
}

async function updateCostCenterPreview(productId) {
    const preview = document.getElementById('costCenterPreview');
    if (!productId) {
        preview.style.display = 'none';
        return;
    }
    
    try {
        const response = await fetch(`/Furniture/api/cost_center_preview.php?product_id=${productId}`);
        const data = await response.json();
        
        if (data.cost_center) {
            preview.innerHTML = `
                <span class="preview-icon">💡</span>
                <span class="preview-text">
                    This transaction will be assigned to: 
                    <span class="preview-name">${data.cost_center}</span>
                </span>
            `;
        } else {
            preview.innerHTML = `
                <span class="preview-icon">ℹ️</span>
                <span class="preview-text">No auto-assignment rule found. Default cost center will be used.</span>
            `;
        }
        preview.style.display = 'flex';
    } catch (e) {
        console.error('Preview error:', e);
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
