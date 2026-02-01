<?php
/**
 * ============================================================================
 * UTILITY FUNCTIONS
 * ============================================================================
 * 
 * Common helper functions used throughout the application
 * 
 * FORMATTING FUNCTIONS:
 * - sanitize()       : XSS-safe HTML escaping for user input
 * - formatCurrency() : Format numbers as Indian Rupees (₹)
 * - formatDate()     : Format dates with customizable format
 * 
 * SESSION & FLASH MESSAGES:
 * - setFlash()       : Set one-time session message
 * - getFlash()       : Retrieve and clear flash message
 * 
 * NAVIGATION:
 * - redirect()       : HTTP redirect with exit
 * 
 * AUTHENTICATION HELPERS:
 * - isLoggedIn()     : Check if user is authenticated
 * - isAdmin()        : Check if user has admin role
 * - isPortal()       : Check if user has portal role
 * - currentUser()    : Get current logged-in user data
 * 
 * BUDGET HELPERS:
 * - getBudgetHealth(): Calculate health status from utilization %
 * - getTimeElapsedPercentage(): Calculate how much time has passed in budget period
 * 
 * @author    Yusuf Gundarwala
 * @version   1.0.0
 * @package   FurnitureERP
 * ============================================================================
 */

session_start();

/**
 * Sanitize input string
 * @param string $input
 * @return string
 */
function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency value
 * @param float $amount
 * @return string
 */
function formatCurrency($amount)
{
    return '₹' . number_format($amount, 2);
}

/**
 * Format date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd M Y')
{
    return date($format, strtotime($date));
}

/**
 * Set flash message
 * @param string $type success|error|warning|info
 * @param string $message
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect to URL
 * @param string $url
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool
 */
function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is portal user
 * @return bool
 */
function isPortal()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'portal';
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 * @return string
 */
function getCurrentUserName()
{
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Calculate percentage
 * @param float $part
 * @param float $whole
 * @return float
 */
function calculatePercentage($part, $whole)
{
    if ($whole == 0)
        return 0;
    return round(($part / $whole) * 100, 2);
}

/**
 * Get budget health status based on utilization percentage
 * @param float $percentage
 * @return array [status, color, icon]
 */
function getBudgetHealth($percentage)
{
    if ($percentage < 70) {
        return ['status' => 'Healthy', 'color' => 'success', 'icon' => ''];
    } elseif ($percentage <= 90) {
        return ['status' => 'Warning', 'color' => 'warning', 'icon' => ''];
    } else {
        return ['status' => 'Critical', 'color' => 'danger', 'icon' => ''];
    }
}

/**
 * Get payment status badge
 * @param string $status paid|partial|unpaid
 * @return array [label, color, icon]
 */
function getPaymentBadge($status)
{
    switch ($status) {
        case 'paid':
            return ['label' => 'Paid', 'color' => 'success', 'icon' => '🟢'];
        case 'partial':
            return ['label' => 'Partially Paid', 'color' => 'warning', 'icon' => '🟡'];
        default:
            return ['label' => 'Not Paid', 'color' => 'danger', 'icon' => '🔴'];
    }
}

/**
 * Get payment status from amounts
 * @param float $total
 * @param float $paid
 * @return string paid|partial|unpaid
 */
function getPaymentStatus($total, $paid)
{
    $total = floatval($total);
    $paid = floatval($paid ?? 0);

    if ($paid <= 0)
        return 'unpaid';
    if ($paid >= $total)
        return 'paid';
    return 'partial';
}

/**
 * Get document status badge
 * @param string $status
 * @return array
 */
function getDocumentStatusBadge($status)
{
    switch ($status) {
        case 'posted':
            return ['label' => 'Posted', 'color' => 'success'];
        case 'cancelled':
            return ['label' => 'Cancelled', 'color' => 'secondary'];
        default:
            return ['label' => 'Draft', 'color' => 'warning'];
    }
}

/**
 * Calculate time elapsed percentage for budget period
 * @param string $startDate
 * @param string $endDate
 * @return float
 */
function getTimeElapsedPercentage($startDate, $endDate)
{
    $start = strtotime($startDate);
    $end = strtotime($endDate);
    $now = time();

    if ($now < $start)
        return 0;
    if ($now > $end)
        return 100;

    $totalDuration = $end - $start;
    $elapsed = $now - $start;

    return round(($elapsed / $totalDuration) * 100, 2);
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate unique document number
 * @param string $prefix PO|SO|BILL|INV
 * @return string
 */
function generateDocumentNumber($prefix)
{
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}
