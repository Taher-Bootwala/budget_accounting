<?php
/**
 * Layout Header - "Frame" Style from Reference Image 2
 */
require_once __DIR__ . '/../../config/auth.php';

$currentUri = $_SERVER['REQUEST_URI'];
$isActive = function ($path) use ($currentUri) {
    if ($path === 'dashboard.php' && strpos($currentUri, 'dashboard.php') !== false)
        return 'active';
    if ($path !== 'dashboard.php' && strpos($currentUri, $path) !== false)
        return 'active';
    return '';
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Payrix Style Dashboard' ?></title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/Furniture/assets/css/style.css">
</head>

<body>

    <!-- 1. Ambient Background -->
    <div class="fluid-background"></div>
    <!-- 2. Animated Blob -->
    <div class="shape-blob blob-main"></div>

    <!-- 3. Main Glass Frame -->
    <div class="dashboard-frame">

        <?php if (!isset($hideNavbar) || !$hideNavbar): ?>
        <!-- Top Dock Navigation -->
        <div class="top-dock">
            <!-- Central Navigation Pills -->
            <div class="dock-center-pill">
                <a href="/Furniture/dashboard.php" class="pill-tab <?= $isActive('dashboard.php') ?>">Overview</a>
                <a href="/Furniture/views/cost_centers/index.php" class="pill-tab <?= $isActive('cost_centers') ?>">
                    Cost Centers
                </a>
                <a href="/Furniture/views/budgets/index.php" class="pill-tab <?= $isActive('budgets') ?>">Budgets</a>
                <a href="/Furniture/views/analytical_models/index.php" class="pill-tab <?= $isActive('analytical_models') ?>">Auto Rules</a>
                <a href="/Furniture/views/products/index.php" class="pill-tab <?= $isActive('products') ?>">Products</a>
                <a href="/Furniture/views/contacts/index.php" class="pill-tab <?= $isActive('contacts') ?>">Contacts</a>
                <a href="/Furniture/views/reports/budget_vs_actual.php" class="pill-tab <?= $isActive('reports') ?>">Reports</a>
            </div>

            <!-- Right Side Settings Dock -->
            <div class="dock-item" title="Pending Orders" style="position: relative;">
                <a href="/Furniture/views/orders/pending.php" style="color: inherit; text-decoration: none;">
                    <i class="ri-shopping-cart-2-line"></i>
                    <?php 
                    $pendingOrderCount = dbFetchValue("SELECT COUNT(*) FROM documents WHERE doc_type = 'SO' AND status = 'draft'");
                    if ($pendingOrderCount > 0): ?>
                        <span style="position: absolute; top: -2px; right: -2px; background: #ef4444; color: white; font-size: 10px; font-weight: 700; min-width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><?= $pendingOrderCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <div class="dock-item" title="Documents">
                <a href="/Furniture/views/documents/index.php" style="color: inherit; text-decoration: none;"><i
                        class="ri-file-text-line"></i></a>
            </div>
            <?php if (isAdmin()): ?>
            <div class="dock-item add-user-dock-btn" title="Add User">
                <a href="/Furniture/views/users/create.php" style="color: inherit; text-decoration: none;"><i
                        class="ri-user-add-line"></i></a>
            </div>
            <?php endif; ?>
            <div class="dock-item" title="Profile">
                <i class="ri-user-smile-line"></i>
            </div>
            <div class="dock-item" title="Logout">
                <a href="/Furniture/logout.php" style="color: inherit;"><i class="ri-logout-box-r-line"></i></a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Left Minimal Sidebar (Design Element) -->


        <!-- Scrollable Content Area -->
        <div class="dashboard-content">
            <!-- Header Inclusion End -->

            <!-- (Page content injects here) -->