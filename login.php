<?php


require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/db.php';

if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['email'] ?? ''); // Input name is still 'email' but treated as identifier
    $password = $_POST['password'] ?? '';
    
    if (empty($identifier)) {
        $error = 'Email or User ID is required';
    } else {
        $user = getUserByEmailOrUsername($identifier);
        
        // Verify password if user exists and has a password hash
        if ($user && isset($user['password_hash']) && verifyPassword($password, $user['password_hash'])) {
            loginUser($user);
            setFlash('success', 'Welcome back, ' . $user['name']);
            redirectByRole();
        } elseif ($user && empty($user['password_hash'])) {
            // Fallback for legacy users without password (should check if we want to allow this)
            // For now, let's treat it as invalid credentials to enforce security
             $error = 'Account upgrade required. Please contact admin.';
        } else {
            $error = 'Invalid credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Payrix Style</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="/Furniture/assets/css/style.css">
</head>

<body>

    <!-- Ambient Background -->
    <div class="fluid-background"></div>
    <div class="shape-blob blob-main"></div>

    <div class="login-frame">
        <div class="auth-glass-card">
            <div
                style="width: 80px; height: 80px; background: white; border-radius: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; color: var(--text-primary); margin-bottom: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <i class="ri-command-fill"></i>
            </div>

            <h1 style="font-size: 32px; margin-bottom: 12px;">Welcome Back</h1>
            <p style="color: var(--text-secondary); margin-bottom: 40px;">Enter your credentials to access the
                workspace.</p>

            <?php if ($error): ?>
                <div
                    style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 12px; border-radius: 12px; margin-bottom: 24px; font-size: 14px;">
                    <?= sanitize($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="email" class="auth-input" placeholder="Email or User ID" required>
                <input type="password" name="password" class="auth-input" placeholder="Password" required>

                <button type="submit" class="auth-btn">
                    Sign In
                </button>
            </form>

            <div style="margin-top: 24px; font-size: 13px; color: var(--text-secondary); display: flex; justify-content: space-between;">
                <a href="#" style="color: inherit; text-decoration: none;">Forgot Password?</a>
                <a href="signup.php" style="color: var(--text-primary); text-decoration: none; font-weight: 500;">Create Account</a>
            </div>
        </div>
    </div>

</body>

</html>