<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cookie configuration
define('COOKIE_EXPIRY', 30 * 24 * 60 * 60); // 30 days
define('COOKIE_PATH', '/');
define('COOKIE_SECURE', true); // Set to true in production
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Strict');

/**
 * Set a secure cookie with proper security flags
 * @param string $name Cookie name
 * @param string $value Cookie value
 * @param int $expiry Cookie expiry time in seconds
 * @return bool Whether the cookie was set successfully
 */
function setSecureCookie($name, $value, $expiry = COOKIE_EXPIRY) {
    try {
        return setcookie(
            $name,
            $value,
            [
                'expires' => time() + $expiry,
                'path' => COOKIE_PATH,
                'secure' => COOKIE_SECURE,
                'httponly' => COOKIE_HTTPONLY,
                'samesite' => COOKIE_SAMESITE
            ]
        );
    } catch (Exception $e) {
        error_log("Error setting cookie: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a cookie securely
 * @param string $name Cookie name
 * @return bool Whether the cookie was deleted successfully
 */
function deleteCookie($name) {
    try {
        return setcookie(
            $name,
            '',
            [
                'expires' => time() - 3600,
                'path' => COOKIE_PATH,
                'secure' => COOKIE_SECURE,
                'httponly' => COOKIE_HTTPONLY,
                'samesite' => COOKIE_SAMESITE
            ]
        );
    } catch (Exception $e) {
        error_log("Error deleting cookie: " . $e->getMessage());
        return false;
    }
}

/**
 * Set remember me cookie with additional security
 * @param int $userId User ID
 * @param string $role User role
 * @return bool Whether the remember me cookie was set successfully
 */
function setRememberMe($userId, $role) {
    try {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        
        // Store token in database (you should implement this)
        // storeTokenInDatabase($userId, $token);
        
        // Set the cookies
        $success = setSecureCookie('remember_token', $token);
        $success = $success && setSecureCookie('user_role', $role);
        
        if ($success) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_role'] = $role;
            return true;
        }
        return false;
    } catch (Exception $e) {
        error_log("Error setting remember me: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in via session or cookie
 * @return bool
 */
function isLoggedIn() {
    try {
        // Check session first
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        
        // Check remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            // Here you would validate the token against your database
            // For now, we'll just check if it exists
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error checking login status: " . $e->getMessage());
        return false;
    }
}

/**
 * Logout user and clear all session/cookie data
 * @return bool Whether the logout was successful
 */
function logout() {
    try {
        // Clear remember me cookies
        if (isset($_COOKIE['remember_token'])) {
            deleteCookie('remember_token');
            deleteCookie('user_role');
        }
        
        // Clear session
        $_SESSION = array();
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
        session_destroy();
        return true;
    } catch (Exception $e) {
        error_log("Error during logout: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current user role
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? $_COOKIE['user_role'] ?? null;
}

/**
 * Get current user ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}
