<?php
/**
 * ============================================================================
 * CREATE USER - ADMIN ONLY
 * ============================================================================
 * 
 * Beautiful glassmorphism form for creating new users with:
 * - Login ID validation (6-12 unique chars)
 * - Email uniqueness check  
 * - Password strength indicator
 * - Role selection cards (Admin/Customer/Vendor)
 * 
 * @author    Shiv Furniture ERP
 * @version   1.0.0
 * ============================================================================
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

requireAdmin();

$error = '';
$success = '';
$formData = [
    'name' => '',
    'login_id' => '',
    'email' => '',
    'role' => 'customer'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['login_id'] = trim($_POST['login_id'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['role'] = $_POST['role'] ?? 'customer';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    $errors = [];

    // Name validation
    if (empty($formData['name'])) {
        $errors[] = 'Full name is required.';
    }

    // Login ID validation (6-12 characters, alphanumeric)
    if (empty($formData['login_id'])) {
        $errors[] = 'Login ID is required.';
    } elseif (strlen($formData['login_id']) < 6 || strlen($formData['login_id']) > 12) {
        $errors[] = 'Login ID must be between 6-12 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $formData['login_id'])) {
        $errors[] = 'Login ID can only contain letters, numbers, and underscores.';
    } else {
        // Check uniqueness
        $existing = dbFetchOne("SELECT id FROM users WHERE username = ?", [$formData['login_id']]);
        if ($existing) {
            $errors[] = 'Login ID is already taken.';
        }
    }

    // Email validation
    if (empty($formData['email'])) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Check uniqueness
        $existing = dbFetchOne("SELECT id FROM users WHERE email = ?", [$formData['email']]);
        if ($existing) {
            $errors[] = 'Email is already registered.';
        }
    }

    // Password validation (8+ chars, uppercase, lowercase, special char)
    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = 'Password must contain at least one special character.';
    }

    // Confirm password
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Role validation
    $validRoles = ['admin', 'customer', 'vendor'];
    if (!in_array($formData['role'], $validRoles)) {
        $errors[] = 'Please select a valid role.';
    }

    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Map customer/vendor to 'portal' role for DB (they have same portal access)
            $dbRole = ($formData['role'] === 'admin') ? 'admin' : 'portal';
            
            dbExecute(
                "INSERT INTO users (name, email, username, role, password_hash) VALUES (?, ?, ?, ?, ?)",
                [$formData['name'], $formData['email'], $formData['login_id'], $dbRole, $passwordHash]
            );
            $userId = dbFetchValue("SELECT LAST_INSERT_ID()");

            // If customer or vendor, create contact and portal access
            if ($formData['role'] !== 'admin') {
                $contactType = $formData['role']; // 'customer' or 'vendor'
                dbExecute("INSERT INTO contacts (name, type) VALUES (?, ?)", [$formData['name'], $contactType]);
                $contactId = dbFetchValue("SELECT LAST_INSERT_ID()");
                dbExecute("INSERT INTO portal_access (user_id, contact_id) VALUES (?, ?)", [$userId, $contactId]);
            }

            $success = 'User created successfully!';
            // Reset form
            $formData = ['name' => '', 'login_id' => '', 'email' => '', 'role' => 'customer'];

        } catch (Exception $e) {
            $error = "Failed to create user: " . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$pageTitle = 'Create User';
include __DIR__ . '/../layouts/header.php';
?>

<style>
/* Add User Form Styles */
.create-user-container {
    max-width: 700px;
    margin: 0 auto;
}

.form-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border-radius: 32px;
    padding: 40px;
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
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

.form-header {
    text-align: center;
    margin-bottom: 36px;
}

.form-header h1 {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 8px;
    background: linear-gradient(135deg, var(--text-primary), var(--accent-wood));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.form-header p {
    color: var(--text-secondary);
    font-size: 14px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 24px;
    position: relative;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-label i {
    font-size: 16px;
    color: var(--accent-wood);
}

.form-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.7);
    border: 2px solid rgba(139, 90, 43, 0.1);
    padding: 14px 18px;
    border-radius: 14px;
    font-size: 15px;
    color: var(--text-primary);
    font-family: var(--font-body);
    transition: all 0.3s ease;
    outline: none;
}

.form-input:focus {
    background: white;
    border-color: var(--accent-wood);
    box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
}

.form-input.valid {
    border-color: var(--success);
    background: rgba(46, 125, 50, 0.05);
}

.form-input.invalid {
    border-color: var(--danger);
    background: rgba(198, 40, 40, 0.05);
}

/* Role Selection Cards */
.role-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.role-card {
    position: relative;
    cursor: pointer;
}

.role-card input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.role-card-content {
    background: rgba(255, 255, 255, 0.6);
    border: 2px solid rgba(139, 90, 43, 0.1);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}

.role-card:hover .role-card-content {
    border-color: rgba(139, 90, 43, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
}

.role-card input:checked + .role-card-content {
    border-color: var(--accent-wood);
    background: rgba(139, 90, 43, 0.08);
    box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
}

.role-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--accent-wood), var(--accent-oak));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    color: white;
    font-size: 22px;
    transition: transform 0.3s ease;
}

.role-card input:checked + .role-card-content .role-icon {
    transform: scale(1.1);
}

.role-name {
    font-weight: 600;
    font-size: 14px;
    color: var(--text-primary);
    margin-bottom: 4px;
}

.role-desc {
    font-size: 11px;
    color: var(--text-secondary);
}

/* Password Strength Indicator */
.password-strength {
    margin-top: 10px;
    display: flex;
    gap: 6px;
}

.strength-bar {
    height: 4px;
    flex: 1;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 2px;
    transition: all 0.3s ease;
}

.strength-bar.weak { background: var(--danger); }
.strength-bar.medium { background: var(--warning); }
.strength-bar.strong { background: var(--success); }

.strength-text {
    font-size: 11px;
    font-weight: 600;
    margin-top: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.strength-text.weak { color: var(--danger); }
.strength-text.medium { color: var(--warning); }
.strength-text.strong { color: var(--success); }

/* Alerts */
.alert {
    padding: 16px 20px;
    border-radius: 14px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    animation: shake 0.5s ease;
}

.alert i {
    font-size: 20px;
    flex-shrink: 0;
    margin-top: 2px;
}

.alert-error {
    background: rgba(198, 40, 40, 0.1);
    border: 1px solid rgba(198, 40, 40, 0.2);
    color: var(--danger);
}

.alert-success {
    background: rgba(46, 125, 50, 0.1);
    border: 1px solid rgba(46, 125, 50, 0.2);
    color: var(--success);
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-5px); }
    40% { transform: translateX(5px); }
    60% { transform: translateX(-5px); }
    80% { transform: translateX(5px); }
}

/* Submit Button */
.submit-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--text-primary), var(--accent-walnut));
    color: white;
    border: none;
    padding: 16px 32px;
    border-radius: 14px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 8px 24px rgba(62, 39, 35, 0.25);
}

.submit-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(62, 39, 35, 0.35);
}

.submit-btn:active {
    transform: translateY(0);
}

.submit-btn i {
    font-size: 18px;
    transition: transform 0.3s ease;
}

.submit-btn:hover i {
    transform: translateX(4px);
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 24px;
    transition: color 0.2s;
}

.back-link:hover {
    color: var(--text-primary);
}

/* Validation Icons */
.validation-icon {
    position: absolute;
    right: 14px;
    top: 42px;
    font-size: 18px;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.validation-icon.show {
    opacity: 1;
    transform: scale(1);
}

.validation-icon.valid {
    color: var(--success);
}

.validation-icon.invalid {
    color: var(--danger);
}

/* Responsive */
@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .role-cards {
        grid-template-columns: 1fr;
    }
    
    .form-card {
        padding: 24px;
        border-radius: 24px;
    }
}
</style>

<div class="page-header anim-fade-up">
    <a href="/Furniture/dashboard.php" class="back-link">
        <i class="ri-arrow-left-line"></i> Back to Dashboard
    </a>
    <h1>Create New User</h1>
</div>

<div class="create-user-container">
    <div class="form-card">
        <div class="form-header">
            <h1><i class="ri-user-add-line"></i> Add User</h1>
            <p>Create a new admin, customer, or vendor account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="ri-error-warning-line"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="ri-checkbox-circle-line"></i>
                <div><?= sanitize($success) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" id="createUserForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="ri-user-line"></i> Full Name
                    </label>
                    <input type="text" name="name" class="form-input" 
                           value="<?= sanitize($formData['name']) ?>" 
                           placeholder="John Doe" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="ri-fingerprint-line"></i> Login ID
                    </label>
                    <input type="text" name="login_id" id="loginId" class="form-input" 
                           value="<?= sanitize($formData['login_id']) ?>" 
                           placeholder="6-12 characters"
                           minlength="6" maxlength="12" required>
                    <i class="ri-check-line validation-icon valid" id="loginIdValid"></i>
                    <i class="ri-close-line validation-icon invalid" id="loginIdInvalid"></i>
                </div>
            </div>

            <div class="form-group full-width">
                <label class="form-label">
                    <i class="ri-mail-line"></i> Email Address
                </label>
                <input type="email" name="email" id="email" class="form-input" 
                       value="<?= sanitize($formData['email']) ?>" 
                       placeholder="user@example.com" required>
                <i class="ri-check-line validation-icon valid" id="emailValid"></i>
                <i class="ri-close-line validation-icon invalid" id="emailInvalid"></i>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        <i class="ri-lock-line"></i> Password
                    </label>
                    <input type="password" name="password" id="password" class="form-input" 
                           placeholder="Min 8 characters" required>
                    <div class="password-strength" id="strengthBars">
                        <div class="strength-bar" id="bar1"></div>
                        <div class="strength-bar" id="bar2"></div>
                        <div class="strength-bar" id="bar3"></div>
                        <div class="strength-bar" id="bar4"></div>
                    </div>
                    <div class="strength-text" id="strengthText"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="ri-lock-unlock-line"></i> Confirm Password
                    </label>
                    <input type="password" name="confirm_password" id="confirmPassword" class="form-input" 
                           placeholder="Re-enter password" required>
                    <i class="ri-check-line validation-icon valid" id="confirmValid"></i>
                    <i class="ri-close-line validation-icon invalid" id="confirmInvalid"></i>
                </div>
            </div>

            <div class="form-group full-width">
                <label class="form-label">
                    <i class="ri-shield-user-line"></i> User Role
                </label>
                <div class="role-cards">
                    <label class="role-card">
                        <input type="radio" name="role" value="admin" 
                               <?= $formData['role'] === 'admin' ? 'checked' : '' ?>>
                        <div class="role-card-content">
                            <div class="role-icon"><i class="ri-admin-line"></i></div>
                            <div class="role-name">Admin</div>
                            <div class="role-desc">Full access rights</div>
                        </div>
                    </label>
                    <label class="role-card">
                        <input type="radio" name="role" value="customer" 
                               <?= $formData['role'] === 'customer' ? 'checked' : '' ?>>
                        <div class="role-card-content">
                            <div class="role-icon"><i class="ri-user-heart-line"></i></div>
                            <div class="role-name">Customer</div>
                            <div class="role-desc">Portal access</div>
                        </div>
                    </label>
                    <label class="role-card">
                        <input type="radio" name="role" value="vendor" 
                               <?= $formData['role'] === 'vendor' ? 'checked' : '' ?>>
                        <div class="role-card-content">
                            <div class="role-icon"><i class="ri-store-2-line"></i></div>
                            <div class="role-name">Vendor</div>
                            <div class="role-desc">Portal access</div>
                        </div>
                    </label>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="ri-user-add-line"></i>
                Create User
                <i class="ri-arrow-right-line"></i>
            </button>
        </form>
    </div>
</div>

<script>
// Password Strength Checker
const passwordInput = document.getElementById('password');
const confirmInput = document.getElementById('confirmPassword');
const bars = [document.getElementById('bar1'), document.getElementById('bar2'), 
              document.getElementById('bar3'), document.getElementById('bar4')];
const strengthText = document.getElementById('strengthText');

passwordInput.addEventListener('input', function() {
    const val = this.value;
    let strength = 0;
    
    if (val.length >= 8) strength++;
    if (/[a-z]/.test(val)) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(val)) strength++;
    
    // Reset bars
    bars.forEach(bar => bar.className = 'strength-bar');
    
    if (strength === 0) {
        strengthText.textContent = '';
        strengthText.className = 'strength-text';
    } else if (strength <= 2) {
        bars.slice(0, strength).forEach(bar => bar.classList.add('weak'));
        strengthText.textContent = 'Weak';
        strengthText.className = 'strength-text weak';
    } else if (strength === 3) {
        bars.slice(0, 3).forEach(bar => bar.classList.add('medium'));
        strengthText.textContent = 'Medium';
        strengthText.className = 'strength-text medium';
    } else {
        bars.forEach(bar => bar.classList.add('strong'));
        strengthText.textContent = 'Strong';
        strengthText.className = 'strength-text strong';
    }
    
    checkConfirmPassword();
});

// Confirm Password Check
function checkConfirmPassword() {
    const confirmValid = document.getElementById('confirmValid');
    const confirmInvalid = document.getElementById('confirmInvalid');
    
    if (confirmInput.value === '') {
        confirmValid.classList.remove('show');
        confirmInvalid.classList.remove('show');
        confirmInput.classList.remove('valid', 'invalid');
        return;
    }
    
    if (confirmInput.value === passwordInput.value) {
        confirmValid.classList.add('show');
        confirmInvalid.classList.remove('show');
        confirmInput.classList.add('valid');
        confirmInput.classList.remove('invalid');
    } else {
        confirmValid.classList.remove('show');
        confirmInvalid.classList.add('show');
        confirmInput.classList.remove('valid');
        confirmInput.classList.add('invalid');
    }
}

confirmInput.addEventListener('input', checkConfirmPassword);

// Login ID Validation
const loginIdInput = document.getElementById('loginId');
const loginIdValid = document.getElementById('loginIdValid');
const loginIdInvalid = document.getElementById('loginIdInvalid');

loginIdInput.addEventListener('input', function() {
    const val = this.value;
    
    if (val === '') {
        loginIdValid.classList.remove('show');
        loginIdInvalid.classList.remove('show');
        this.classList.remove('valid', 'invalid');
        return;
    }
    
    const isValid = val.length >= 6 && val.length <= 12 && /^[a-zA-Z0-9_]+$/.test(val);
    
    if (isValid) {
        loginIdValid.classList.add('show');
        loginIdInvalid.classList.remove('show');
        this.classList.add('valid');
        this.classList.remove('invalid');
    } else {
        loginIdValid.classList.remove('show');
        loginIdInvalid.classList.add('show');
        this.classList.remove('valid');
        this.classList.add('invalid');
    }
});

// Email Validation
const emailInput = document.getElementById('email');
const emailValid = document.getElementById('emailValid');
const emailInvalid = document.getElementById('emailInvalid');

emailInput.addEventListener('input', function() {
    const val = this.value;
    
    if (val === '') {
        emailValid.classList.remove('show');
        emailInvalid.classList.remove('show');
        this.classList.remove('valid', 'invalid');
        return;
    }
    
    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
    
    if (isValid) {
        emailValid.classList.add('show');
        emailInvalid.classList.remove('show');
        this.classList.add('valid');
        this.classList.remove('invalid');
    } else {
        emailValid.classList.remove('show');
        emailInvalid.classList.add('show');
        this.classList.remove('valid');
        this.classList.add('invalid');
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
