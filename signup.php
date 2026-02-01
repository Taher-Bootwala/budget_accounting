<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Basic Validation
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check availability
        $existing = dbFetchOne("SELECT id FROM users WHERE email = ? OR username = ?", [$email, $username]);
        if ($existing) {
            $error = 'Email or Login ID already taken.';
        } else {
            try {
                // 1. Create User
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                dbExecute("INSERT INTO users (name, email, username, role, password_hash) VALUES (?, ?, ?, 'portal', ?)", 
                    [$name, $email, $username, $passwordHash]);
                $userId = dbFetchValue("SELECT LAST_INSERT_ID()");

                // 2. Create Contact (Customer)
                // We'll use the user's name as the contact name
                dbExecute("INSERT INTO contacts (name, type) VALUES (?, 'customer')", [$name]);
                $contactId = dbFetchValue("SELECT LAST_INSERT_ID()");

                // 3. Link Portal Access
                dbExecute("INSERT INTO portal_access (user_id, contact_id) VALUES (?, ?)", [$userId, $contactId]);

                // 4. Auto Login
                $user = dbFetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
                loginUser($user);
                setFlash('success', 'Account created successfully! Welcome to the portal.');
                redirectByRole();

            } catch (Exception $e) {
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/Furniture/assets/css/style.css">
</head>
<body>

    <!-- Ambient Background -->
    <div class="fluid-background"></div>
    <div class="shape-blob blob-main"></div>

    <div class="login-frame">
        <div class="auth-glass-card" style="padding-top: 40px; padding-bottom: 40px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="font-size: 28px; margin-bottom: 8px;">Create Account</h1>
                <p style="color: var(--text-secondary);">Join our customer portal</p>
            </div>

            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                    <?= sanitize($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 500; margin-bottom: 6px; display: block; color: var(--text-secondary);">Full Name</label>
                    <input type="text" name="name" class="auth-input" value="<?= sanitize($name ?? '') ?>" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 500; margin-bottom: 6px; display: block; color: var(--text-secondary);">Login ID</label>
                    <input type="text" name="username" class="auth-input" value="<?= sanitize($username ?? '') ?>" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 500; margin-bottom: 6px; display: block; color: var(--text-secondary);">Email Address</label>
                    <input type="email" name="email" class="auth-input" value="<?= sanitize($email ?? '') ?>" required>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: 500; margin-bottom: 6px; display: block; color: var(--text-secondary);">Password</label>
                    <input type="password" name="password" class="auth-input" required>
                </div>

                <div style="margin-bottom: 32px;">
                    <label style="font-size: 13px; font-weight: 500; margin-bottom: 6px; display: block; color: var(--text-secondary);">Re-Enter Password</label>
                    <input type="password" name="confirm_password" class="auth-input" required>
                </div>

                <button type="submit" class="auth-btn">
                    Sign Up
                </button>
            </form>

            <div style="margin-top: 24px; font-size: 13px; color: var(--text-secondary); text-align: center;">
                Already have an account? <a href="login.php" style="color: var(--text-primary); text-decoration: none; font-weight: 600;">Sign In</a>
            </div>
        </div>
    </div>

</body>
</html>
