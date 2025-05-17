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
 * Create a remember me token and store it in the database
 * @param int $userId User ID
 * @return bool Whether the token was created successfully
 */
function createRememberMeToken($userId) {
    global $conn;

    try {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));

        // Set expiry date (30 days from now)
        $expiresAt = date('Y-m-d H:i:s', time() + COOKIE_EXPIRY);

        // Get user role
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();

        if (!$role) {
            error_log("Could not find role for user ID: $userId");
            return false;
        }

        // Delete any existing tokens for this user
        $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Store token in database
        $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $success = $stmt->execute([$userId, $token, $expiresAt]);

        if (!$success) {
            error_log("Failed to store remember me token in database for user ID: $userId");
            return false;
        }

        // Set the cookies
        $cookieSuccess = setSecureCookie('remember_token', $token);
        $cookieSuccess = $cookieSuccess && setSecureCookie('user_id', $userId);

        return $cookieSuccess;
    } catch (Exception $e) {
        error_log("Error creating remember me token: " . $e->getMessage());
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
        // Create token in database
        $success = createRememberMeToken($userId);

        if ($success) {
            // Set session variables
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
    global $conn;

    try {
        // Check session first
        if (isset($_SESSION['user_id'])) {
            return true;
        }

        // Check remember me cookie
        if (isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
            $token = $_COOKIE['remember_token'];
            $userId = $_COOKIE['user_id'];

            // Validate token against database
            $stmt = $conn->prepare("
                SELECT u.id, u.name, u.email, u.role
                FROM remember_tokens rt
                JOIN users u ON rt.user_id = u.id
                WHERE rt.token = ?
                AND rt.user_id = ?
                AND rt.expires_at > NOW()
            ");
            $stmt->execute([$token, $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Valid token found, set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];

                // Extend the token expiry
                $expiresAt = date('Y-m-d H:i:s', time() + COOKIE_EXPIRY);
                $stmt = $conn->prepare("UPDATE remember_tokens SET expires_at = ? WHERE token = ?");
                $stmt->execute([$expiresAt, $token]);

                // Refresh the cookie
                setSecureCookie('remember_token', $token);
                setSecureCookie('user_id', $userId);

                return true;
            } else {
                // Invalid or expired token, clear cookies
                deleteCookie('remember_token');
                deleteCookie('user_id');
            }
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
    global $conn;

    try {
        // Remove remember me token from database
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];

            try {
                $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ?");
                $stmt->execute([$token]);
            } catch (PDOException $e) {
                error_log("Error removing remember token from database: " . $e->getMessage());
                // Continue with logout even if database operation fails
            }

            // Clear cookies
            deleteCookie('remember_token');
            deleteCookie('user_id');
        }

        // Store user ID before clearing session
        $userId = $_SESSION['user_id'] ?? null;

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

        // If we have a user ID, remove all their remember tokens
        if ($userId) {
            try {
                $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
                $stmt->execute([$userId]);
            } catch (PDOException $e) {
                error_log("Error removing all user tokens from database: " . $e->getMessage());
                // Continue with logout even if database operation fails
            }
        }

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

/**
 * Check if current user is an admin
 * @return bool True if user is logged in and has admin role
 */
function isAdmin() {
    $role = getCurrentUserRole();
    return $role === 'admin';
}
