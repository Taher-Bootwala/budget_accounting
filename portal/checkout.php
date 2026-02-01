<?php
/**
 * Customer Portal - Checkout
 * Review cart and place order
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

requirePortal();

$userId = getCurrentUserId();
$portalAccess = dbFetchOne("
    SELECT pa.*, c.name as contact_name, c.id as contact_id
    FROM portal_access pa
    JOIN contacts c ON pa.contact_id = c.id
    WHERE pa.user_id = ?
", [$userId]);

if (!$portalAccess) {
    die("Portal access not configured.");
}

$contactId = $portalAccess['contact_id'];
$error = '';
$success = '';

// Handle order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartData = $_POST['cart'] ?? '';
    $cart = json_decode($cartData, true);

    if (empty($cart)) {
        $error = 'Your cart is empty.';
    } else {
        try {
            // Calculate total
            $totalAmount = 0;
            $lines = [];
            foreach ($cart as $item) {
                $product = dbFetchOne("SELECT * FROM products WHERE id = ?", [$item['id']]);
                if ($product) {
                    $lineTotal = $product['price'] * $item['qty'];
                    $totalAmount += $lineTotal;
                    $lines[] = [
                        'product_id' => $item['id'],
                        'quantity' => $item['qty'],
                        'price' => $product['price'],
                        'line_total' => $lineTotal
                    ];
                }
            }

            if (empty($lines)) {
                $error = 'No valid products in cart.';
            } else {
                // Calculate GST (18%)
                $gstRate = 0.18;
                $gstAmount = $totalAmount * $gstRate;
                $grandTotal = $totalAmount + $gstAmount;
                
                // Create Sales Order in draft status
                require_once __DIR__ . '/../controllers/DocumentController.php';
                
                $docId = DocumentController::create([
                    'doc_type' => 'SO',
                    'contact_id' => $contactId,
                    'cost_center_id' => null,
                    'total_amount' => $grandTotal
                ], $lines);

                // Clear cart
                $success = 'Order placed successfully! Order #' . str_pad($docId, 4, '0', STR_PAD_LEFT) . ' is pending approval.';
            }
        } catch (Exception $e) {
            $error = 'Failed to place order: ' . $e->getMessage();
        }
    }
}

// Get products for display
$products = dbFetchAll("SELECT * FROM products");
$productMap = [];
foreach ($products as $p) {
    $productMap[$p['id']] = $p;
}

$pageTitle = 'Checkout';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/Furniture/assets/css/style.css">
    <style>
        .checkout-card {
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 32px;
            border: 1px solid rgba(255,255,255,0.3);
            max-width: 700px;
            margin: 0 auto;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .item-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .item-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #8B5A2B, #C19A6B);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        .item-name {
            font-weight: 600;
            font-size: 15px;
        }
        .item-qty {
            font-size: 13px;
            color: var(--text-secondary);
        }
        .item-total {
            font-weight: 700;
            font-size: 16px;
        }
        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 0;
            border-top: 2px solid rgba(0,0,0,0.1);
            margin-top: 16px;
        }
        .total-label {
            font-size: 18px;
            font-weight: 600;
        }
        .total-amount {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        .empty-cart i {
            font-size: 60px;
            opacity: 0.3;
            margin-bottom: 16px;
        }
        .success-box {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 24px;
        }
        .success-box i {
            font-size: 40px;
            margin-bottom: 12px;
        }
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>

    <!-- Ambient BG -->
    <div class="fluid-background"></div>

    <div class="dashboard-frame" style="flex-direction: column;">
        <!-- Top Navigation Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 30px 40px; border-bottom: 1px solid rgba(255,255,255,0.2);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: var(--text-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i class="ri-shopping-cart-2-line" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px; font-weight: 700; white-space: nowrap;">Checkout</h2>
                    <div style="font-size: 13px; opacity: 0.7;">Review your order</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 16px; align-items: center; margin-left: auto;">
                <a href="/Furniture/portal/products.php" class="btn btn-sm btn-secondary" style="border-radius: 12px;"><i class="ri-arrow-left-line"></i> Continue Shopping</a>
            </div>
        </div>

        <div class="dashboard-content" style="padding: 40px;">
            
            <div class="checkout-card">
                <?php if ($success): ?>
                    <div class="success-box">
                        <i class="ri-checkbox-circle-line"></i>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Order Placed!</div>
                        <div><?= $success ?></div>
                    </div>
                    <div style="text-align: center;">
                        <a href="/Furniture/portal/index.php" class="btn btn-primary" style="border-radius: 12px;">
                            <i class="ri-home-line"></i> Back to Dashboard
                        </a>
                    </div>
                    <script>localStorage.removeItem('shivCart');</script>
                <?php else: ?>
                    <?php if ($error): ?>
                        <div class="error-box"><?= sanitize($error) ?></div>
                    <?php endif; ?>

                    <h2 style="margin-bottom: 24px; font-size: 22px;">Your Cart</h2>
                    
                    <div id="cartItems">
                        <!-- Populated by JS -->
                    </div>

                    <div id="cartEmpty" class="empty-cart" style="display: none;">
                        <i class="ri-shopping-cart-line"></i>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Your cart is empty</div>
                        <div>Go back to browse products and add items.</div>
                        <a href="/Furniture/portal/products.php" class="btn btn-primary" style="margin-top: 20px; border-radius: 12px;">
                            Browse Products
                        </a>
                    </div>

                    <div id="cartFooter" style="display: none;">
                        <div style="padding: 16px 0; border-top: 1px solid rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                <span style="color: var(--text-secondary);">Subtotal</span>
                                <span id="subtotal" style="font-weight: 500;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                <span style="color: var(--text-secondary);">GST (18%)</span>
                                <span id="gstAmount" style="font-weight: 500;">₹0.00</span>
                            </div>
                        </div>
                        <div class="cart-total">
                            <span class="total-label">Grand Total</span>
                            <span class="total-amount" id="grandTotal">₹0.00</span>
                        </div>

                        <form method="POST" id="orderForm">
                            <input type="hidden" name="cart" id="cartInput">
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; border-radius: 14px; font-size: 16px;">
                                <i class="ri-check-line"></i> Place Order
                            </button>
                        </form>
                        <div style="text-align: center; margin-top: 12px; font-size: 13px; color: var(--text-secondary);">
                            Your order will be reviewed by Shiv Enterprise.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <?php if (!$success): ?>
    <script>
        // Product data from PHP
        const productMap = <?= json_encode($productMap) ?>;
        
        const cart = JSON.parse(localStorage.getItem('shivCart') || '{}');

        function renderCheckout() {
            const cartItems = document.getElementById('cartItems');
            const cartEmpty = document.getElementById('cartEmpty');
            const cartFooter = document.getElementById('cartFooter');
            const grandTotal = document.getElementById('grandTotal');
            const cartInput = document.getElementById('cartInput');

            const items = Object.values(cart);
            
            if (items.length === 0) {
                cartEmpty.style.display = 'block';
                cartFooter.style.display = 'none';
                cartItems.innerHTML = '';
                return;
            }

            cartEmpty.style.display = 'none';
            cartFooter.style.display = 'block';

            let html = '';
            let subtotal = 0;

            items.forEach(item => {
                const product = productMap[item.id];
                if (product) {
                    const lineTotal = product.price * item.qty;
                    subtotal += lineTotal;
                    html += `
                        <div class="cart-item">
                            <div class="item-info">
                                <div class="item-icon"><i class="ri-box-3-line"></i></div>
                                <div>
                                    <div class="item-name">${product.name}</div>
                                    <div class="item-qty">₹${parseFloat(product.price).toLocaleString('en-IN')} × ${item.qty}</div>
                                </div>
                            </div>
                            <div class="item-total">₹${lineTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                        </div>
                    `;
                }
            });

            // Calculate GST (18%)
            const gstRate = 0.18;
            const gstAmount = subtotal * gstRate;
            const grandTotalAmount = subtotal + gstAmount;

            cartItems.innerHTML = html;
            document.getElementById('subtotal').textContent = '₹' + subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
            document.getElementById('gstAmount').textContent = '₹' + gstAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
            grandTotal.textContent = '₹' + grandTotalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
            cartInput.value = JSON.stringify(items);
        }

        renderCheckout();
    </script>
    <?php endif; ?>

</body>
</html>
