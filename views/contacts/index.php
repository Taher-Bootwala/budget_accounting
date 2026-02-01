<?php
/**
 * ============================================================================
 * CONTACT MASTER - LIST VIEW
 * ============================================================================
 * 
 * Beautiful glassmorphism list view for managing contacts with:
 * - New/Archived filter tabs
 * - Customer/Vendor type filter
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
    $contactId = $_POST['contact_id'] ?? 0;
    
    // Auto-add archived column if it doesn't exist
    if (($action === 'archive' || $action === 'restore') && $contactId) {
        try {
            $columns = dbFetchAll("SHOW COLUMNS FROM contacts LIKE 'archived'");
            if (empty($columns)) {
                dbExecute("ALTER TABLE contacts ADD COLUMN archived TINYINT(1) DEFAULT 0");
            }
        } catch (Exception $e) {
            // Column might already exist or other error
        }
    }
    
    if ($action === 'archive' && $contactId) {
        try {
            dbExecute("UPDATE contacts SET archived = 1 WHERE id = ?", [$contactId]);
            setFlash('success', 'Contact archived successfully.');
        } catch (Exception $e) {
            setFlash('error', 'Could not archive contact.');
        }
        redirect('/Furniture/views/contacts/index.php');
    }
    
    if ($action === 'restore' && $contactId) {
        try {
            dbExecute("UPDATE contacts SET archived = 0 WHERE id = ?", [$contactId]);
            setFlash('success', 'Contact restored successfully.');
        } catch (Exception $e) {
            setFlash('error', 'Could not restore contact.');
        }
        redirect('/Furniture/views/contacts/index.php?tab=archived');
    }
    
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'customer';
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        
        if ($name && $type) {
            dbExecute(
                "INSERT INTO contacts (name, type, archived) VALUES (?, ?, 0)",
                [$name, $type]
            );
            setFlash('success', 'Contact created successfully!');
            redirect('/Furniture/views/contacts/index.php');
        }
    }
}

$activeTab = $_GET['tab'] ?? 'active';
$typeFilter = $_GET['type'] ?? 'all';

// Fetch contacts with proper archived filtering
try {
    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM documents d WHERE d.contact_id = c.id) as doc_count
            FROM contacts c WHERE 1=1";
    $params = [];
    
    if ($activeTab === 'archived') {
        $sql .= " AND c.archived = 1";
    } else {
        $sql .= " AND (c.archived = 0 OR c.archived IS NULL)";
    }
    
    if ($typeFilter !== 'all') {
        $sql .= " AND c.type = ?";
        $params[] = $typeFilter;
    }
    
    $sql .= " ORDER BY c.name ASC";
    $contacts = dbFetchAll($sql, $params);
} catch (Exception $e) {
    // If archived column doesn't exist, show empty for archived tab
    if ($activeTab === 'archived') {
        $contacts = [];
    } else {
        try {
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM documents d WHERE d.contact_id = c.id) as doc_count
                    FROM contacts c WHERE 1=1";
            $params = [];
            if ($typeFilter !== 'all') {
                $sql .= " AND c.type = ?";
                $params[] = $typeFilter;
            }
            $sql .= " ORDER BY c.name ASC";
            $contacts = dbFetchAll($sql, $params);
        } catch (Exception $e2) {
            $contacts = [];
        }
    }
}

// Count by type
try {
    if ($activeTab === 'archived') {
        $customerCount = dbFetchValue("SELECT COUNT(*) FROM contacts WHERE type = 'customer' AND archived = 1") ?: 0;
        $vendorCount = dbFetchValue("SELECT COUNT(*) FROM contacts WHERE type = 'vendor' AND archived = 1") ?: 0;
    } else {
        $customerCount = dbFetchValue("SELECT COUNT(*) FROM contacts WHERE type = 'customer' AND (archived = 0 OR archived IS NULL)") ?: 0;
        $vendorCount = dbFetchValue("SELECT COUNT(*) FROM contacts WHERE type = 'vendor' AND (archived = 0 OR archived IS NULL)") ?: 0;
    }
} catch (Exception $e) {
    $customerCount = dbFetchValue("SELECT COUNT(*) FROM contacts WHERE type = 'customer'") ?: 0;
    $vendorCount = dbFetchValue("SELECT COUNT(*) FROM contacts WHERE type = 'vendor'") ?: 0;
}

$pageTitle = 'Contact Master';
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
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 26px;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
}

.header-actions {
    display: flex;
    gap: 12px;
}

/* Filter Tabs */
.filter-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.filter-tabs {
    display: flex;
    gap: 16px;
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

/* Type Pills */
.type-pills {
    display: flex;
    gap: 8px;
}

.type-pill {
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.6);
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.3s ease;
}

.type-pill:hover {
    background: rgba(255, 255, 255, 0.8);
}

.type-pill.active {
    background: white;
    color: var(--text-primary);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.type-pill.customer.active {
    background: #dcfce7;
    color: #166534;
    border-color: #bbf7d0;
}

.type-pill.vendor.active {
    background: #fef3c7;
    color: #92400e;
    border-color: #fde68a;
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
    background: rgba(59, 130, 246, 0.05);
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
    background: rgba(59, 130, 246, 0.03);
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

/* Contact Name Cell */
.contact-cell {
    display: flex;
    align-items: center;
    gap: 14px;
}

.contact-avatar {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 700;
    flex-shrink: 0;
}

.contact-avatar.customer {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #166534;
}

.contact-avatar.vendor {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
}

.contact-info h4 {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.contact-info span {
    font-size: 12px;
    color: var(--text-secondary);
}

/* Type Badge */
.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.type-badge.customer {
    background: #dcfce7;
    color: #166534;
}

.type-badge.vendor {
    background: #fef3c7;
    color: #92400e;
}

/* Email Link */
.email-link {
    color: #3b82f6;
    text-decoration: none;
    font-size: 13px;
}

.email-link:hover {
    text-decoration: underline;
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
    background: rgba(59, 130, 246, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: #3b82f6;
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
    max-width: 550px;
    padding: 32px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: modalSlide 0.3s ease;
    max-height: 90vh;
    overflow-y: auto;
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

/* Type Selector */
.type-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 20px;
}

.type-option {
    position: relative;
    cursor: pointer;
}

.type-option input {
    position: absolute;
    opacity: 0;
}

.type-option-content {
    padding: 16px;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-radius: 14px;
    text-align: center;
    transition: all 0.3s ease;
}

.type-option:hover .type-option-content {
    border-color: rgba(0, 0, 0, 0.2);
}

.type-option input:checked + .type-option-content.customer {
    border-color: #22c55e;
    background: #f0fdf4;
}

.type-option input:checked + .type-option-content.vendor {
    border-color: #f59e0b;
    background: #fffbeb;
}

.type-option-icon {
    font-size: 28px;
    margin-bottom: 8px;
}

.type-option-content.customer .type-option-icon {
    color: #22c55e;
}

.type-option-content.vendor .type-option-icon {
    color: #f59e0b;
}

.type-option-label {
    font-weight: 600;
    font-size: 14px;
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
    accent-color: #3b82f6;
    cursor: pointer;
}

/* Phone display */
.phone-display {
    font-size: 13px;
    color: var(--text-secondary);
}
</style>

<div class="page-header anim-fade-up" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="margin-bottom: 8px;">Contacts</h1>
        <div class="filter-tabs">
            <a href="?tab=active&type=<?= $typeFilter ?>" class="filter-tab <?= $activeTab === 'active' ? 'active' : '' ?>">
                Active
            </a>
            <a href="?tab=archived&type=<?= $typeFilter ?>" class="filter-tab <?= $activeTab === 'archived' ? 'active' : '' ?>">
                Archived
            </a>
        </div>
    </div>
    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
        <button class="btn btn-primary" onclick="openModal()">
            + New Contact
        </button>
        <div class="type-pills">
            <a href="?tab=<?= $activeTab ?>&type=all" class="type-pill <?= $typeFilter === 'all' ? 'active' : '' ?>">
                All
            </a>
            <a href="?tab=<?= $activeTab ?>&type=customer" class="type-pill customer <?= $typeFilter === 'customer' ? 'active' : '' ?>">
                Customers (<?= $customerCount ?>)
            </a>
            <a href="?tab=<?= $activeTab ?>&type=vendor" class="type-pill vendor <?= $typeFilter === 'vendor' ? 'active' : '' ?>">
                Vendors (<?= $vendorCount ?>)
            </a>
        </div>
    </div>
</div>

<div class="master-container">

    <!-- List Card -->
    <div class="list-card">
        <!-- Search Bar -->
        <div class="search-bar">
            <div class="search-input-wrapper">
                <i class="ri-search-line"></i>
                <input type="text" id="searchInput" placeholder="Search contacts by name, email, or phone..." onkeyup="filterTable()">
            </div>
        </div>

        <!-- Table -->
        <?php if (empty($contacts)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="ri-user-add-line"></i>
                </div>
                <h3>No contacts found</h3>
                <p>Start by adding your first customer or vendor.</p>
            </div>
        <?php else: ?>
            <table class="list-table" id="contactTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <div class="checkbox-wrapper">
                                <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                            </div>
                        </th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Email</th>
                        <th>Documents</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                        <?php 
                        $initials = strtoupper(substr($contact['name'], 0, 2));
                        ?>
                        <tr data-id="<?= $contact['id'] ?>">
                            <td>
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" class="row-checkbox" value="<?= $contact['id'] ?>">
                                </div>
                            </td>
                            <td>
                                <div class="contact-cell">
                                    <div class="contact-avatar <?= $contact['type'] ?>">
                                        <?= $initials ?>
                                    </div>
                                    <div class="contact-info">
                                        <h4><?= sanitize($contact['name']) ?></h4>
                                        <span>ID: #<?= $contact['id'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="type-badge <?= $contact['type'] ?>">
                                    <i class="ri-<?= $contact['type'] === 'customer' ? 'user-heart-line' : 'store-2-line' ?>"></i>
                                    <?= ucfirst($contact['type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="email-link">—</span>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $contact['doc_count'] ?? 0 ?> docs</span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="action-btn-small edit" title="Edit" onclick="editContact(<?= $contact['id'] ?>)">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <?php if ($activeTab === 'archived'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
                                            <button type="submit" class="action-btn-small restore" title="Restore">
                                                <i class="ri-refresh-line"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Archive this contact?')">
                                            <input type="hidden" name="action" value="archive">
                                            <input type="hidden" name="contact_id" value="<?= $contact['id'] ?>">
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

<!-- New Contact Modal -->
<div class="modal-overlay" id="contactModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="ri-user-add-line" style="color: #3b82f6;"></i> New Contact</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <!-- Type Selector -->
            <div class="type-selector">
                <label class="type-option">
                    <input type="radio" name="type" value="customer" checked>
                    <div class="type-option-content customer">
                        <div class="type-option-icon"><i class="ri-user-heart-line"></i></div>
                        <div class="type-option-label">Customer</div>
                    </div>
                </label>
                <label class="type-option">
                    <input type="radio" name="type" value="vendor">
                    <div class="type-option-content vendor">
                        <div class="type-option-icon"><i class="ri-store-2-line"></i></div>
                        <div class="type-option-label">Vendor</div>
                    </div>
                </label>
            </div>
            
            <div class="form-group">
                <label class="form-label">Contact Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter contact name" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="email@example.com">
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-control" placeholder="+91 00000 00000">
            </div>
            
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2" placeholder="Street, City, State, Country, Pincode"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tags</label>
                <input type="text" name="tags" class="form-control" placeholder="B2B, MSME, Retailer, Local (comma separated)">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 16px;">
                <i class="ri-check-line"></i> Create Contact
            </button>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('contactModal').classList.add('show');
}

function closeModal() {
    document.getElementById('contactModal').classList.remove('show');
}

function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('contactTable');
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

function editContact(id) {
    alert('Edit contact #' + id + ' - Feature coming soon!');
}

// Close modal on outside click
document.getElementById('contactModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>