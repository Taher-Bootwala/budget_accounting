5<?php
/**
 * Customer Portal Dashboard
 * Enhanced with Tabs for Invoices, Bills, SO, PO
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/functions.php';

requirePortal();

$userId = getCurrentUserId();

// Security: Get contact ID
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

// Get ALL documents
$documents = dbFetchAll("
    SELECT d.*, 
           (SELECT COALESCE(SUM(paid_amount), 0) FROM payments WHERE document_id = d.id) as paid_amount
    FROM documents d
    WHERE d.contact_id = ?
    ORDER BY d.created_at DESC
", [$contactId]);

// Group by type
$grouped = [
    'invoices' => [],
    'orders' => [], // SO + PO (Purchase Orders from Vendor perspective if allowed, or SOs placed by customer)
    'bills' => []
];

foreach ($documents as $doc) {
    if ($doc['doc_type'] === 'CustomerInvoice') {
        $grouped['invoices'][] = $doc;
    } elseif ($doc['doc_type'] === 'SO' || $doc['doc_type'] === 'SalesOrder' || $doc['doc_type'] === 'PurchaseOrder') {
        $grouped['orders'][] = $doc;
    } elseif ($doc['doc_type'] === 'VendorBill') {
        $grouped['bills'][] = $doc;
    }
}

$pageTitle = 'Dashboard';
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
</head>
<body>

    <!-- Ambient BG -->
    <div class="fluid-background"></div>

    <div class="dashboard-frame" style="flex-direction: column;">
        <!-- 1. Top Navigation Bar (Aligned) -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 30px 40px; border-bottom: 1px solid rgba(255,255,255,0.2);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: var(--text-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i class="ri-user-star-line" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px; font-weight: 700; white-space: nowrap;">Customer Portal</h2>
                    <div style="font-size: 13px; opacity: 0.7;">Furniture ERP</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 16px; align-items: center; margin-left: auto;">
                <div style="text-align: right; margin-right: 12px; display: none; @media(min-width: 768px){display:block;}">
                    <div style="font-weight: 600; font-size: 14px;"><?= sanitize($portalAccess['contact_name']) ?></div>
                    <div style="font-size: 12px; opacity: 0.7;">Client</div>
                </div>
                <a href="/Furniture/portal/products.php" class="btn btn-sm btn-primary" style="border-radius: 12px; background: linear-gradient(135deg, #8B5A2B, #5D3A1A); border: none;"><i class="ri-shopping-cart-2-line"></i> Place Order</a>
                <a href="/Furniture/logout.php" class="btn btn-sm btn-secondary" style="border-radius: 12px;"><i class="ri-logout-box-r-line"></i> Logout</a>
            </div>
        </div>

        <div class="dashboard-content" style="padding: 40px;">
            
            <!-- 2. Welcome & Stats Section -->
            <div style="margin-bottom: 40px;">
                <h1 style="font-size: 32px; margin-bottom: 8px;">Overview</h1>
                <p class="text-secondary" style="margin-bottom: 32px;">Here is what's happening with your account today.</p>

                <!-- Stats Grid -->
                <?php
                    // Calculate totals on the fly
                    $totalOutstanding = 0;
                    $totalPaid = 0;
                    $invCount = count($grouped['invoices']);
                    foreach ($grouped['invoices'] as $inv) {
                        $bal = $inv['total_amount'] - $inv['paid_amount'];
                        if ($bal > 0) $totalOutstanding += $bal;
                        $totalPaid += $inv['paid_amount'];
                    }
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px;">
                    <!-- Card 1: Outstanding -->
                    <div class="glass-widget" style="padding: 24px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div class="text-secondary" style="font-size: 13px; font-weight: 600; text-transform: uppercase;">Outstanding Due</div>
                            <div style="width: 32px; height: 32px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #991b1b;">
                                <i class="ri-alert-line"></i>
                            </div>
                        </div>
                        <div style="font-size: 32px; font-weight: 700; color: var(--text-primary);"><?= formatCurrency($totalOutstanding) ?></div>
                        <div style="font-size: 13px; color: var(--text-secondary);">Total unpaid invoices</div>
                    </div>

                    <!-- Card 2: Total Paid -->
                    <div class="glass-widget" style="padding: 24px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div class="text-secondary" style="font-size: 13px; font-weight: 600; text-transform: uppercase;">Total Paid</div>
                            <div style="width: 32px; height: 32px; background: #dcfce7; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #166534;">
                                <i class="ri-check-double-line"></i>
                            </div>
                        </div>
                        <div style="font-size: 32px; font-weight: 700; color: var(--text-primary);"><?= formatCurrency($totalPaid) ?></div>
                        <div style="font-size: 13px; color: var(--text-secondary);">Lifetime payments</div>
                    </div>

                    <!-- Card 3: Total Invoices -->
                    <div class="glass-widget" style="padding: 24px; display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div class="text-secondary" style="font-size: 13px; font-weight: 600; text-transform: uppercase;">Invoices</div>
                            <div style="width: 32px; height: 32px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #075985;">
                                <i class="ri-file-list-3-line"></i>
                            </div>
                        </div>
                        <div style="font-size: 32px; font-weight: 700; color: var(--text-primary);"><?= $invCount ?></div>
                        <div style="font-size: 13px; color: var(--text-secondary);">Total invoices generated</div>
                    </div>
                </div>
            </div>

            <!-- 3. Tabs & Content -->
            <div class="tabs-container">
                <button class="tab-btn active" onclick="openTab(event, 'tab-invoices')">
                    <i class="ri-bill-line" style="margin-right: 6px;"></i> Invoices (<?= count($grouped['invoices']) ?>)
                </button>
                <button class="tab-btn" onclick="openTab(event, 'tab-orders')">
                    <i class="ri-shopping-cart-2-line" style="margin-right: 6px;"></i> Orders (<?= count($grouped['orders']) ?>)
                </button>
                <?php if(count($grouped['bills']) > 0): ?>
                <button class="tab-btn" onclick="openTab(event, 'tab-bills')">Vendor Bills (<?= count($grouped['bills']) ?>)</button>
                <?php endif; ?>
            </div>

            <!-- INVOICES TAB -->
            <div id="tab-invoices" class="tab-content active">
                <div class="glass-widget">
                    <div class="table-responsive">
                    <table style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="padding-left: 24px;">Ref #</th>
                                <th>Date</th>
                                <th style="text-align: right;">Amount</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: right; padding-right: 24px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($grouped['invoices'])): ?>
                                <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-secondary);">No invoices found.</td></tr>
                            <?php else: foreach($grouped['invoices'] as $inv): ?>
                            <?php 
                                $bal = $inv['total_amount'] - $inv['paid_amount'];
                                $statusClass = ($bal <= 0) ? 'badge-inv' : 'badge-bill';
                                $statusText = ($bal <= 0) ? 'PAID' : 'DUE';
                            ?>
                            <tr>
                                <td style="padding-left: 24px;"><strong>#<?= str_pad($inv['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= formatDate($inv['created_at']) ?></td>
                                <td style="text-align: right; font-weight: 600;"><?= formatCurrency($inv['total_amount']) ?></td>
                                <td style="text-align: center;"><span class="badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                <td style="text-align: right; padding-right: 24px;">
                                    <?php if($bal > 0): ?>
                                    <a href="view_document.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-dark" style="background: var(--text-primary); color: white; margin-right: 8px;">Pay Now</a>
                                    <?php endif; ?>
                                    <a href="view_document.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- ORDERS TAB -->
            <div id="tab-orders" class="tab-content">
                <div class="glass-widget">
                    <div class="table-responsive">
                    <table style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="padding-left: 24px;">Ref #</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th style="text-align: right;">Total</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: right; padding-right: 24px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($grouped['orders'])): ?>
                                <tr><td colspan="6" style="text-align:center; padding: 40px; color: var(--text-secondary);">No orders found.</td></tr>
                            <?php else: foreach($grouped['orders'] as $ord): ?>
                            <tr>
                                <td style="padding-left: 24px;"><strong>#<?= str_pad($ord['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <span class="badge <?= $ord['doc_type'] == 'SalesOrder' ? 'badge-so' : 'badge-po' ?>">
                                        <?= $ord['doc_type'] == 'SalesOrder' ? 'SO' : 'PO' ?>
                                    </span>
                                </td>
                                <td><?= formatDate($ord['created_at']) ?></td>
                                <td style="text-align: right; font-weight: 600;"><?= formatCurrency($ord['total_amount']) ?></td>
                                <td style="text-align: center;">
                                    <?php 
                                        $orderStatus = $ord['status'];
                                        $statusClass = 'badge-secondary';
                                        $statusLabel = ucfirst($orderStatus);
                                        if ($orderStatus === 'draft') {
                                            $statusClass = 'badge-warning';
                                            $statusLabel = 'Pending Approval';
                                        } elseif ($orderStatus === 'posted') {
                                            $statusClass = 'badge-success';
                                            $statusLabel = 'Approved';
                                        } elseif ($orderStatus === 'cancelled') {
                                            $statusClass = 'badge-danger';
                                            $statusLabel = 'Rejected';
                                        }
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td style="text-align: right; padding-right: 24px;">
                                    <a href="view_document.php?id=<?= $ord['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- BILLS TAB -->
            <div id="tab-bills" class="tab-content">
                <div class="glass-widget">
                    <div class="table-responsive">
                    <table style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="padding-left: 24px;">Ref #</th>
                                <th>Date</th>
                                <th style="text-align: right;">Total</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: right; padding-right: 24px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($grouped['bills'] as $bill): ?>
                            <tr>
                                <td style="padding-left: 24px;"><strong>#<?= str_pad($bill['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= formatDate($bill['created_at']) ?></td>
                                <td style="text-align: right; font-weight: 600;"><?= formatCurrency($bill['total_amount']) ?></td>
                                <td style="text-align: center;"><?= ucfirst($bill['status']) ?></td>
                                <td style="text-align: right; padding-right: 24px;">
                                    <a href="view_document.php?id=<?= $bill['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    function openTab(evt, tabName) {
        // Hide all tab content
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }

        // Remove active class from buttons
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }

        // Show current tab and add active class
        document.getElementById(tabName).style.display = "block";
        setTimeout(() => document.getElementById(tabName).classList.add("active"), 10);
        evt.currentTarget.className += " active";
    }
    </script>
</body>
</html>