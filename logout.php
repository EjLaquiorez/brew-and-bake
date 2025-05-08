<?php
require_once "includes/auth.php";

// Clear all session data
$_SESSION = array();

// Clear remember me cookies if they exist
if (isset($_COOKIE['remember_token'])) {
    deleteCookie('remember_token');
    deleteCookie('user_role');
}

// Clear session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set a logout message in a temporary session
session_start();
$_SESSION['logout_message'] = "You have been successfully logged out.";
session_write_close();

// Redirect to login page
header("Location: views/login.php");
exit;
?>
