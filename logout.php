<?php
/**
 * ============================================================================
 * USER LOGOUT HANDLER
 * ============================================================================
 * 
 * Securely terminates user session
 * 
 * LOGOUT PROCESS:
 * 1. Destroy all session data
 * 2. Set success flash message
 * 3. Redirect to login page
 * 
 * SECURITY:
 * - Clears $_SESSION array completely
 * - Destroys session on server side
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

require_once __DIR__ . '/config/auth.php';

logoutUser();
setFlash('success', 'You have been logged out successfully.');
redirect('/Furniture/login.php');
