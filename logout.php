<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) session_start();

// remove all session data
$_SESSION = [];

// kill the session cookie (recommended)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// redirect to public homepage
header('Location: index.php');
exit;
