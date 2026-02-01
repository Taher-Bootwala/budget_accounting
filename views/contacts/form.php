<?php
/**
 * Contact Form (Create/Edit)
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../controllers/ContactController.php';

requireAdmin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$contact = $id ? ContactController::getById($id) : null;
$isEdit = $contact !== null;

$errors = [];

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'type' => $_POST['type'] ?? 'customer'
    ];

    // Validate
    if (empty($data['name'])) {
        $errors[] = 'Name is required.';
    }

    if (empty($errors)) {
        if ($isEdit) {
            ContactController::update($id, $data);
            setFlash('success', 'Contact updated.');
        } else {
            ContactController::create($data);
            setFlash('success', 'Contact created.');
        }
        redirect('/Furniture/views/contacts/index.php');
    }
}

$pageTitle = $isEdit ? 'Edit Contact' : 'New Contact';
include __DIR__ . '/../layouts/header.php';
?>

<div class="page-header">
    <h1 class="page-title"><?= $isEdit ? '✏️ Edit' : '➕ New' ?> Contact</h1>
    <a href="/Furniture/views/contacts/index.php" class="btn btn-secondary">← Back</a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <?php if (!empty($errors)): ?>
            <div class="flash-message error">
                <?php foreach ($errors as $e): ?>
                    <div><?= sanitize($e) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Name *</label>
                <input type="text" id="name" name="name" class="form-control" required
                    value="<?= sanitize($contact['name'] ?? $_POST['name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="type">Type *</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="customer" <?= ($contact['type'] ?? 'customer') === 'customer' ? 'selected' : '' ?>>
                        Customer
                    </option>
                    <option value="vendor" <?= ($contact['type'] ?? '') === 'vendor' ? 'selected' : '' ?>>
                        Vendor
                    </option>
                </select>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update' : 'Create' ?> Contact</button>
                <a href="/Furniture/views/contacts/index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>