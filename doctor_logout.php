<?php
// doctor_logout.php
session_start();

// Clear all session data
$_SESSION = [];

// If sessions use cookies, invalidate the cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy the session
session_destroy();

// Redirect to doctor login page (change the filename if yours is different)
header("Location: doctor_login.php");
exit;
