<?php
/**
 * ============================================================================
 * AUTHENTICATION MIDDLEWARE
 * ============================================================================
 * 
 * Role-Based Access Control (RBAC) for the application
 * 
 * USER ROLES:
 * - admin  : Full access to all features (budget management, reports, etc.)
 * - portal : Limited access for customers/vendors (view invoices, make payments)
 * 
 * GUARD FUNCTIONS:
 * - requireAuth()   : Ensures user is logged in, redirects to login if not
 * - requireAdmin()  : Ensures user is admin, redirects with error if not
 * - requirePortal() : Ensures user is portal user
 * 
 * SESSION MANAGEMENT:
 * - loginUser()     : Create user session with role
 * - logoutUser()    : Destroy session and logout
 * - redirectByRole(): Redirect to appropriate dashboard based on role
 * 
 * SECURITY FEATURES:
 * - Session regeneration on login (prevents session fixation)
 * - Password verification using password_verify() (bcrypt)
 * - Flash messages for user feedback
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/functions.php';

/**
 * Require authentication to access page
 * Redirects to login if not authenticated
 */
function requireAuth()
{
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue.');
        redirect('/Furniture/login.php');
    }
}

/**
 * Require admin role to access page
 * Redirects to dashboard with error if not admin
 */
function requireAdmin()
{
    requireAuth();
    if (!isAdmin()) {
        setFlash('error', 'You do not have permission to access this page.');
        redirect('/Furniture/portal/dashboard.php');
    }
}

/**
 * Require portal role to access page
 */
function requirePortal()
{
    requireAuth();
    if (!isPortal()) {
        setFlash('error', 'This page is for portal users only.');
        redirect('/Furniture/dashboard.php');
    }
}

/**
 * Redirect based on user role
 */
function redirectByRole()
{
    if (isAdmin()) {
        redirect('/Furniture/dashboard.php');
    } else {
        redirect('/Furniture/portal/dashboard.php');
    }
}

/**
 * Get user by email or username
 * @param string $identifier
 * @return array|false
 */
function getUserByEmailOrUsername($identifier)
{
    require_once __DIR__ . '/db.php';
    return dbFetchOne("SELECT * FROM users WHERE email = ? OR username = ?", [$identifier, $identifier]);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Login user
 * @param array $user
 */
function loginUser($user)
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Logout user
 */
function logoutUser()
{
    $_SESSION = [];
    session_destroy();
}

/**
 * Get the contact ID linked to portal user
 * @param int $userId
 * @return int|null
 */
function getPortalContactId($userId)
{
    require_once __DIR__ . '/db.php';
    return dbFetchValue("SELECT contact_id FROM portal_access WHERE user_id = ?", [$userId]);
}
