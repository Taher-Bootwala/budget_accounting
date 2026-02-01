<?php
/**
 * Admin - Pending Orders Queue
 * Accept or Reject customer orders
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../controllers/DocumentController.php';

requireAdmin();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $orderId = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        DocumentController::post($orderId);
        setFlash('success', 'Order #' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . ' has been approved.');
    } elseif ($action === 'reject') {
        DocumentController::cancel($orderId);
        setFlash('success', 'Order #' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . ' has been rejected.');
    }
    
    redirect('/Furniture/views/orders/pending.php');
}

// Get pending Sales Orders
$pendingOrders = dbFetchAll("
    SELECT d.*, c.name as contact_name,
           CONCAT('SO-', LPAD(d.id, 4, '0')) as order_number
    FROM documents d
    LEFT JOIN contacts c ON d.contact_id = c.id
    WHERE d.doc_type = 'SO' AND d.status = 'draft'
    ORDER BY d.created_at DESC
");

$pageTitle = 'Pending Orders';

include __DIR__ . '/../layouts/header.php';
?>

<div class="page-header anim-fade-up">
    <div style="font-size: 12px; color: var(--text-secondary); opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
        Order Management
    </div>
    <h1>Pending Orders</h1>
</div>

<?php if (empty($pendingOrders)): ?>
    <div class="glass-widget" style="text-align: center; padding: 60px 40px;">
        <div style="font-size: 60px; opacity: 0.2; margin-bottom: 16px;">
            <i class="ri-checkbox-circle-line"></i>
        </div>
        <h3 style="margin-bottom: 8px; color: var(--text-primary);">All Caught Up!</h3>
        <p style="color: var(--text-secondary);">There are no pending orders to review.</p>
    </div>
<?php else: ?>
    <div class="glass-widget">
        <div class="table-responsive">
            <table class="data-table" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="padding-left: 24px;">Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th style="text-align: right;">Amount</th>
                        <th style="text-align: center; padding-right: 24px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingOrders as $order): ?>
                        <tr>
                            <td style="padding-left: 24px;">
                                <strong><?= $order['order_number'] ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 600;"><?= sanitize($order['contact_name']) ?></div>
                            </td>
                            <td><?= formatDate($order['created_at']) ?></td>
                            <td style="text-align: right; font-weight: 700; font-size: 15px;">
                                <?= formatCurrency($order['total_amount']) ?>
                            </td>
                            <td style="text-align: center; padding-right: 24px;">
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <a href="/Furniture/views/documents/view.php?id=<?= $order['id'] ?>" 
                                       class="btn btn-sm btn-secondary" 
                                       title="View Details">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="?action=approve&id=<?= $order['id'] ?>" 
                                       class="btn btn-sm" 
                                       style="background: #10b981; color: white; border: none;"
                                       onclick="return confirm('Approve this order?')"
                                       title="Approve">
                                        <i class="ri-check-line"></i> Approve
                                    </a>
                                    <a href="?action=reject&id=<?= $order['id'] ?>" 
                                       class="btn btn-sm" 
                                       style="background: #ef4444; color: white; border: none;"
                                       onclick="return confirm('Reject this order? This cannot be undone.')"
                                       title="Reject">
                                        <i class="ri-close-line"></i> Reject
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- All Orders Section -->
<div style="margin-top: 40px;">
    <h2 style="font-size: 20px; margin-bottom: 20px;">All Sales Orders</h2>
    
    <?php
    $allOrders = dbFetchAll("
        SELECT d.*, c.name as contact_name,
               CONCAT('SO-', LPAD(d.id, 4, '0')) as order_number
        FROM documents d
        LEFT JOIN contacts c ON d.contact_id = c.id
        WHERE d.doc_type = 'SO'
        ORDER BY d.created_at DESC
        LIMIT 20
    ");
    ?>
    
    <?php if (empty($allOrders)): ?>
        <div class="glass-widget" style="text-align: center; padding: 40px;">
            <p style="color: var(--text-secondary);">No sales orders found.</p>
        </div>
    <?php else: ?>
        <div class="glass-widget">
            <div class="table-responsive">
                <table class="data-table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding-left: 24px;">Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th style="text-align: right;">Amount</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: right; padding-right: 24px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allOrders as $order): 
                            $statusClass = 'badge-secondary';
                            $statusLabel = ucfirst($order['status']);
                            if ($order['status'] === 'draft') {
                                $statusClass = 'badge-warning';
                                $statusLabel = 'Pending';
                            } elseif ($order['status'] === 'posted') {
                                $statusClass = 'badge-success';
                                $statusLabel = 'Approved';
                            } elseif ($order['status'] === 'cancelled') {
                                $statusClass = 'badge-danger';
                                $statusLabel = 'Rejected';
                            }
                        ?>
                            <tr>
                                <td style="padding-left: 24px;">
                                    <strong><?= $order['order_number'] ?></strong>
                                </td>
                                <td><?= sanitize($order['contact_name']) ?></td>
                                <td><?= formatDate($order['created_at']) ?></td>
                                <td style="text-align: right; font-weight: 600;">
                                    <?= formatCurrency($order['total_amount']) ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td style="text-align: right; padding-right: 24px;">
                                    <a href="/Furniture/views/documents/view.php?id=<?= $order['id'] ?>" 
                                       class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
