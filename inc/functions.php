<?php
// inc/functions.php
// Replace your current functions.php with this secure, session-aware version.
//
// NOTE: keep any other helper functions you already have (merge if needed).

// Start session if not started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape output for HTML
 */
function esc($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Check whether user is logged in (session-based)
 * Returns boolean
 */
function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

/**
 * Require login — redirect to login page and exit if not logged in
 * Also sends cache-control headers to prevent "Back button shows protected page" issue.
 */
function require_login() {
    // prevent caching of protected pages
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!is_logged_in()) {
        // preserve requested URL for redirect after login (optional)
        $request = $_SERVER['REQUEST_URI'] ?? '/';
        if (stripos($request, 'login.php') === false) {
            $_SESSION['after_login_redirect'] = $request;
        }
        header('Location: /login.php');
        exit;
    }
}

/**
 * Small helper to check admin flag.
 * It assumes you store is_admin (0/1) in session on login.
 */
function is_admin() {
    return !empty($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;
}

/**
 * Require admin (redirect to login or unauthorized)
 */
function require_admin() {
    // prevent caching for admin pages too
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
    if (!is_admin()) {
        // show 403 simple page (or redirect to home)
        http_response_code(403);
        echo '<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>';
        exit;
    }
}

/**
 * Utility: safe redirect after login
 */
function redirect_after_login($default = '/home.php') {
    $url = $_SESSION['after_login_redirect'] ?? $default;
    unset($_SESSION['after_login_redirect']);
    header('Location: ' . $url);
    exit;
}
