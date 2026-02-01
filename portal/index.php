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
    SELECT pa.*, c.name as contact_name, c.id as contact_id, c.type as contact_type
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

// Group by type and status
$grouped = [
    'invoices' => [],
    'orders' => [], 
    'bills' => [],
    'pending' => [] // New group for action required
];

foreach ($documents as $doc) {
    // Action Required: POs pending vendor approval
    if (($doc['doc_type'] === 'PO' || $doc['doc_type'] === 'PurchaseOrder') && $doc['status'] === 'pending_vendor') {
        $grouped['pending'][] = $doc;
    }

    if ($doc['doc_type'] === 'CustomerInvoice') {
        $grouped['invoices'][] = $doc;
    } elseif (in_array($doc['doc_type'], ['SO', 'SalesOrder', 'PO', 'PurchaseOrder'])) {
        $grouped['orders'][] = $doc;
    } elseif ($doc['doc_type'] === 'VendorBill') {
        $grouped['bills'][] = $doc;
    }
}

$contactType = $portalAccess['contact_type'];
$isVendor = ($contactType === 'vendor' || $contactType === 'both');
$isCustomer = ($contactType === 'customer' || $contactType === 'both');

$pageTitle = $isVendor ? 'Partner Portal' : 'Customer Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/Furniture/assets/css/style.css">
</head>
<body>

    <!-- Ambient BG -->
    <div class="fluid-background"></div>

    <div class="dashboard-frame" style="flex-direction: column;">
        <!-- 1. Top Navigation Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 30px 40px; border-bottom: 1px solid rgba(255,255,255,0.2);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: var(--text-primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i class="<?= $isVendor ? 'ri-store-3-line' : 'ri-user-star-line' ?>" style="font-size: 20px;"></i>
                </div>
                <div>
                    <h2 style="margin: 0; font-size: 18px; font-weight: 700; white-space: nowrap;"><?= $pageTitle ?></h2>
                    <div style="font-size: 13px; opacity: 0.7;">Furniture ERP</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 16px; align-items: center; margin-left: auto;">
                <div style="text-align: right; margin-right: 12px; display: none; @media(min-width: 768px){display:block;}">
                    <div style="font-weight: 600; font-size: 14px;"><?= sanitize($portalAccess['contact_name']) ?></div>
                    <div style="font-size: 12px; opacity: 0.7;"><?= ucfirst($contactType) ?></div>
                </div>
                
                <?php if ($isVendor): ?>
                    <a href="/Furniture/portal/my_products.php" class="btn btn-sm btn-secondary" style="border-radius: 12px;"><i class="ri-store-2-line"></i> My Products</a>
                <?php endif; ?>

                <?php if ($isCustomer): ?>
                    <a href="/Furniture/portal/products.php" class="btn btn-sm btn-primary" style="border-radius: 12px; background: linear-gradient(135deg, #8B5A2B, #5D3A1A); border: none;"><i class="ri-shopping-cart-2-line"></i> Place Order</a>
                <?php endif; ?>

                <a href="/Furniture/logout.php" class="btn btn-sm btn-secondary" style="border-radius: 12px;"><i class="ri-logout-box-r-line"></i> Logout</a>
            </div>
        </div>

        <div class="dashboard-content" style="padding: 40px;">
            
            <!-- 2. Welcome & Stats Section -->
            <div style="margin-bottom: 40px;">
                <h1 style="font-size: 32px; margin-bottom: 8px;">Overview</h1>
                <p class="text-secondary" style="margin-bottom: 32px;">Here is what's happening with your account today.</p>

                <!-- Action Required (Vendors Only) -->
                <?php if ($isVendor && !empty($grouped['pending'])): ?>
                <div class="card" style="border: 1px solid var(--warning); background: #fffbf0; margin-bottom: 30px;">
                    <div class="card-header" style="border-bottom-color: rgba(0,0,0,0.05);">
                        <h3 class="card-title" style="color: var(--warning-dark); display: flex; align-items: center; gap: 8px;">
                            <i class="ri-alert-line"></i> Action Required
                        </h3>
                    </div>
                    <div class="card-body">
                        <p>You have <strong><?= count($grouped['pending']) ?></strong> new purchase orders waiting for approval.</p>
                        <div style="display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;">
                            <?php foreach ($grouped['pending'] as $pDoc): ?>
                                <a href="/Furniture/portal/view_document.php?id=<?= $pDoc['id'] ?>" class="btn btn-sm btn-primary" style="background: var(--warning); border: none;">
                                    View Order #<?= $pDoc['id'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <?php
                    // Calculate totals on the fly
                    $stats = [
                        'outstanding' => 0,
                        'paid' => 0,
                        'invoices_count' => count($grouped['invoices']),
                        'bills_count' => count($grouped['bills']),
                        'pending_count' => count($grouped['pending'])
                    ];
                    
                    if ($isCustomer) {
                        foreach ($grouped['invoices'] as $inv) {
                            if ($inv['status'] !== 'cancelled') {
                                $stats['outstanding'] += ($inv['total_amount'] - $inv['paid_amount']);
                                $stats['paid'] += $inv['paid_amount'];
                            }
                        }
                    } elseif ($isVendor) {
                        foreach ($grouped['bills'] as $bill) {
                             if ($bill['status'] !== 'cancelled') {
                                 // outstanding = Amount Admin owes vendor
                                 $stats['outstanding'] += ($bill['total_amount'] - $bill['paid_amount']);
                                 $stats['paid'] += $bill['paid_amount'];
                             }
                        }
                    }
                ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                    
                    <?php if ($isCustomer): ?>
                    <!-- CUSTOMER STATS -->
                    <div class="stat-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; opacity: 0.6;">OUTSTANDING DUE</span>
                            <div style="width: 24px; height: 24px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="ri-alert-fill" style="font-size: 14px;"></i></div>
                        </div>
                        <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?= formatCurrency($stats['outstanding']) ?></h3>
                        <p style="font-size: 13px; opacity: 0.7; margin: 0;">Total unpaid invoices</p>
                    </div>

                    <div class="stat-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; opacity: 0.6;">TOTAL PAID</span>
                            <div style="width: 24px; height: 24px; background: rgba(34, 197, 94, 0.1); color: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="ri-check-double-line" style="font-size: 14px;"></i></div>
                        </div>
                        <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?= formatCurrency($stats['paid']) ?></h3>
                        <p style="font-size: 13px; opacity: 0.7; margin: 0;">Lifetime payments</p>
                    </div>

                    <div class="stat-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; opacity: 0.6;">INVOICES</span>
                            <div style="width: 24px; height: 24px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="ri-file-list-3-line" style="font-size: 14px;"></i></div>
                        </div>
                        <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?= $stats['invoices_count'] ?></h3>
                        <p style="font-size: 13px; opacity: 0.7; margin: 0;">Total invoices generated</p>
                    </div>
                    <?php endif; ?>

                    <?php if ($isVendor): ?>
                    <!-- VENDOR STATS -->
                    <div class="stat-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; opacity: 0.6;">TOTAL ORDERS</span>
                            <div style="width: 24px; height: 24px; background: rgba(249, 115, 22, 0.1); color: #f97316; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="ri-shopping-bag-3-line" style="font-size: 14px;"></i></div>
                        </div>
                        <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?= count($grouped['orders']) ?></h3>
                        <p style="font-size: 13px; opacity: 0.7; margin: 0;">Lifetime orders received</p>
                    </div>

                    <div class="stat-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; opacity: 0.6;">TOTAL EARNINGS</span>
                             <div style="width: 24px; height: 24px; background: rgba(34, 197, 94, 0.1); color: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="ri-money-dollar-circle-line" style="font-size: 14px;"></i></div>
                        </div>
                         <!-- Total we have paid them -->
                        <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?= formatCurrency($stats['paid']) ?></h3>
                        <p style="font-size: 13px; opacity: 0.7; margin: 0;">Total payments received</p>
                    </div>
                     
                    <div class="stat-card">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
                            <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.5px; opacity: 0.6;">BILLS</span>
                            <div style="width: 24px; height: 24px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="ri-file-text-line" style="font-size: 14px;"></i></div>
                        </div>
                        <h3 style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?= $stats['bills_count'] ?></h3>
                        <p style="font-size: 13px; opacity: 0.7; margin: 0;">Total bills submitted</p>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- 3. Documents List -->
            <div class="card" style="padding: 0; overflow: hidden;">
                <!-- Internal Filters -->
                <div style="padding: 20px; border-bottom: 1px solid #eee; display: flex; gap: 16px;">
                    <?php if ($isCustomer): ?>
                        <button class="tab-btn active" onclick="switchTab('invoices')"><i class="ri-file-text-line"></i> Invoices (<?= count($grouped['invoices']) ?>)</button>
                        <button class="tab-btn" onclick="switchTab('orders')"><i class="ri-shopping-cart-line"></i> Orders (<?= count($grouped['orders']) ?>)</button>
                    <?php endif; ?>
                    
                    <?php if ($isVendor): ?>
                         <button class="tab-btn active" onclick="switchTab('bills')"><i class="ri-bill-line"></i> My Bills (<?= count($grouped['bills']) ?>)</button>
                         <button class="tab-btn" onclick="switchTab('orders')"><i class="ri-shopping-bag-3-line"></i> Orders Received (<?= count($grouped['orders']) ?>)</button>
                    <?php endif; ?>
                </div>

            <!-- INVOICES TAB -->
            <div id="tab-invoices" class="tab-content <?= $isCustomer ? 'active' : '' ?>">
                <div class="glass-widget">
                    <!-- ... content ... -->
                    <div class="table-responsive">
                    <table style="margin: 0;">
                        <!-- ... (keep existing content for invoices) ... -->
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
                                $statusClass = 'badge-bill'; // Default Due
                                $statusText = 'DUE';
                                
                                if ($bal <= 0) {
                                    $statusClass = 'badge-inv'; // Paid
                                    $statusText = 'PAID';
                                } elseif ($inv['paid_amount'] > 0) {
                                    $statusClass = 'badge-warning'; // Partial
                                    $statusText = 'PARTIAL';
                                }
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
                 <!-- ... (keep existing content) ... -->
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
                                            $statusLabel = 'Pending';
                                        } elseif ($orderStatus === 'posted') {
                                            $statusClass = 'badge-success';
                                            $statusLabel = 'Approved';
                                        } elseif ($orderStatus === 'cancelled') {
                                            $statusClass = 'badge-danger';
                                            $statusLabel = 'Rejected';
                                        } elseif ($orderStatus === 'pending_vendor') {
                                            $statusClass = 'badge-warning';
                                            $statusLabel = $isVendor ? 'Action Required' : 'Pending Vendor';
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

            <!-- BILLS TAB (Visible Default for Vendor) -->
            <div id="tab-bills" class="tab-content <?= $isVendor ? 'active' : '' ?>">
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
                            <?php if(empty($grouped['bills'])): ?>
                                <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-secondary);">No bills found.</td></tr>
                            <?php else: foreach($grouped['bills'] as $bill): ?>
                            <tr>
                                <td style="padding-left: 24px;"><strong>#<?= str_pad($bill['id'], 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><?= formatDate($bill['created_at']) ?></td>
                                <td style="text-align: right; font-weight: 600;"><?= formatCurrency($bill['total_amount']) ?></td>
                                <td style="text-align: center;"><?= ucfirst($bill['status']) ?></td>
                                <td style="text-align: right; padding-right: 24px;">
                                    <a href="view_document.php?id=<?= $bill['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    function switchTab(tabName) {
        // Tab name maps to ID: invoices -> tab-invoices
        const targetId = 'tab-' + tabName;
        
        // Hide all tab content
        const tabcontent = document.getElementsByClassName("tab-content");
        for (let i = 0; i < tabcontent.length; i++) {
            tabcontent[i].classList.remove("active");
        }

        // Remove active class from buttons
        const tablinks = document.getElementsByClassName("tab-btn");
        for (let i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }

        // Show current tab and add active class
        const target = document.getElementById(targetId);
        if (target) {
            target.classList.add("active");
        }
        
        // Add active class to clicked button (or find it if called programmatically)
        // Since this is inline onclick, event.target is easiest but let's be safe
        if (event && event.currentTarget) {
             event.currentTarget.classList.add("active");
        }
    }
    </script>
</body>
</html>