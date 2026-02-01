<?php
/**
 * Customer Portal - Product Catalog
 * Browse and add products to cart
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

requirePortal();

$userId = getCurrentUserId();
// Security: Get contact ID
$portalAccess = dbFetchOne("
    SELECT pa.*, c.name as contact_name, c.id as contact_id, c.type as contact_type
    FROM portal_access pa
    JOIN contacts c ON pa.contact_id = c.id
    WHERE pa.user_id = ?
", [$userId]);

if (!$portalAccess) {
    die("Portal access not configured.");
}

// Enforce Customer Role
if ($portalAccess['contact_type'] !== 'customer' && $portalAccess['contact_type'] !== 'both') {
    setFlash('error', 'Access denied: Customer privileges required.');
    redirect('/Furniture/portal/index.php');
}

// Get all products
$products = dbFetchAll("SELECT * FROM products ORDER BY category, name");

// Group by category
$categories = [];
foreach ($products as $p) {
    $cat = $p['category'] ?? 'Other';
    if (!isset($categories[$cat])) {
        $categories[$cat] = [];
    }
    $categories[$cat][] = $p;
}

$pageTitle = 'Products';
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
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }
        .product-card {
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid rgba(255,255,255,0.3);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        }
        .product-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #8B5A2B, #C19A6B);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 16px;
        }
        .product-name {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .product-category {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 12px;
        }
        .product-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
        }
        .qty-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .qty-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
            background: white;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }
        .qty-btn:hover {
            background: var(--accent-wood);
            color: white;
            border-color: var(--accent-wood);
        }
        .qty-display {
            font-size: 16px;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        .cart-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #8B5A2B, #5D3A1A);
            color: white;
            padding: 16px 28px;
            border-radius: 50px;
            box-shadow: 0 10px 40px rgba(139, 90, 43, 0.4);
            display: none;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            z-index: 100;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .cart-floating:hover {
            transform: scale(1.05);
        }
        .cart-floating.show {
            display: flex;
        }
        .cart-count {
            background: white;
            color: var(--accent-wood);
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .category-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-secondary);
            margin: 32px 0 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .category-title:first-child {
            margin-top: 0;
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
                    <i class="ri-store-2-line" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px; font-weight: 700; white-space: nowrap;">Product Catalog</h2>
                    <div style="font-size: 13px; opacity: 0.7;">Shiv Enterprise</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 16px; align-items: center; margin-left: auto;">
                <a href="/Furniture/portal/index.php" class="btn btn-sm btn-secondary" style="border-radius: 12px;"><i class="ri-arrow-left-line"></i> Back to Dashboard</a>
                <a href="/Furniture/logout.php" class="btn btn-sm btn-secondary" style="border-radius: 12px;"><i class="ri-logout-box-r-line"></i> Logout</a>
            </div>
        </div>

        <div class="dashboard-content" style="padding: 40px;">
            
            <div style="margin-bottom: 32px;">
                <h1 style="font-size: 28px; margin-bottom: 8px;">Browse Products</h1>
                <p class="text-secondary">Select products and quantities, then proceed to checkout.</p>
            </div>

            <?php foreach ($categories as $catName => $catProducts): ?>
                <div class="category-title"><?= sanitize($catName) ?></div>
                <div class="product-grid">
                    <?php foreach ($catProducts as $product): ?>
                        <div class="product-card" data-id="<?= $product['id'] ?>" data-name="<?= sanitize($product['name']) ?>" data-price="<?= $product['price'] ?>">
                            <div class="product-icon">
                                <i class="ri-box-3-line"></i>
                            </div>
                            <div class="product-name"><?= sanitize($product['name']) ?></div>
                            <div class="product-category"><?= sanitize($product['category'] ?? 'General') ?></div>
                            <div class="product-price"><?= formatCurrency($product['price']) ?></div>
                            <div class="qty-controls">
                                <button class="qty-btn" onclick="updateQty(<?= $product['id'] ?>, -1)">−</button>
                                <span class="qty-display" id="qty-<?= $product['id'] ?>">0</span>
                                <button class="qty-btn" onclick="updateQty(<?= $product['id'] ?>, 1)">+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <!-- Floating Cart Button -->
    <a href="/Furniture/portal/checkout.php" class="cart-floating" id="cartFloating">
        <i class="ri-shopping-cart-2-line" style="font-size: 20px;"></i>
        <span>View Cart</span>
        <span class="cart-count" id="cartCount">0</span>
    </a>

    <script>
        // Cart stored in localStorage
        let cart = JSON.parse(localStorage.getItem('shivCart') || '{}');

        function updateQty(productId, delta) {
            const card = document.querySelector(`.product-card[data-id="${productId}"]`);
            const name = card.dataset.name;
            const price = parseFloat(card.dataset.price);

            if (!cart[productId]) {
                cart[productId] = { id: productId, name: name, price: price, qty: 0 };
            }

            cart[productId].qty = Math.max(0, cart[productId].qty + delta);

            if (cart[productId].qty === 0) {
                delete cart[productId];
            }

            localStorage.setItem('shivCart', JSON.stringify(cart));
            renderCart();
        }

        function renderCart() {
            let totalItems = 0;
            for (const id in cart) {
                totalItems += cart[id].qty;
                document.getElementById('qty-' + id).textContent = cart[id].qty;
            }

            // Update all qty displays (in case some are 0)
            document.querySelectorAll('.qty-display').forEach(el => {
                const id = el.id.replace('qty-', '');
                el.textContent = cart[id] ? cart[id].qty : 0;
            });

            const floatingCart = document.getElementById('cartFloating');
            const cartCount = document.getElementById('cartCount');
            
            cartCount.textContent = totalItems;
            if (totalItems > 0) {
                floatingCart.classList.add('show');
            } else {
                floatingCart.classList.remove('show');
            }
        }

        // Initial render
        renderCart();
    </script>

</body>
</html>
