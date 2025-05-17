<?php
/**
 * Login Process Handler
 * 
 * This script handles traditional form submissions for the login form.
 * It's a fallback for browsers with JavaScript disabled.
 */

session_start();
require_once "db.php";
require_once "auth.php";

// Initialize variables
$redirect = '';
$error = '';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && ($_POST['remember'] == '1' || $_POST['remember'] == 'on');

    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // Check if user exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // User not found - don't reveal this information
                $error = 'Invalid email or password.';
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Check if account is verified
                    if (isset($user['verification_status']) && $user['verification_status'] == 1) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        
                        // Handle remember me
                        if ($remember) {
                            createRememberMeToken($user['id']);
                        }

                        // Role-based redirect
                        switch ($user['role']) {
                            case 'admin':
                                $redirect = '/brew-and-bake/templates/admin/dashboard.php';
                                break;
                            case 'staff':
                                $redirect = '/brew-and-bake/templates/staff/staff.php';
                                break;
                            case 'client':
                                $redirect = '/brew-and-bake/templates/client/client.php';
                                break;
                            default:
                                $error = 'Invalid user role.';
                                break;
                        }

                        // Log the login attempt
                        error_log("User {$user['id']} ({$user['email']}) logged in successfully with role {$user['role']}.");
                    } else {
                        // Account needs verification
                        $error = 'Please verify your email address before logging in.';
                        
                        // Store verification info in session for display
                        $_SESSION['verification_email'] = $user['email'];
                        $_SESSION['verification_token'] = $user['verification_token'] ?? '';
                        
                        // Redirect to verification page
                        $redirect = '/brew-and-bake/templates/views/verification_required.php';
                    }
                } else {
                    // Invalid password - don't reveal this information
                    $error = 'Invalid email or password.';
                }
            }
        } catch (PDOException $e) {
            // Log the error but don't expose details to the user
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred during login. Please try again.';
        }
    }
}

// Handle redirect or error
if (!empty($redirect)) {
    // Successful login with redirect
    header("Location: $redirect");
    exit;
} else {
    // Error occurred, redirect back to login page with error
    if (!empty($error)) {
        $_SESSION['login_error'] = $error;
    }
    
    // Determine where to redirect back to
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    
    if (strpos($referer, 'login.php') !== false) {
        // Came from login page
        header("Location: ../views/login.php");
    } else {
        // Came from homepage or elsewhere
        header("Location: ../../index.php");
    }
    exit;
}
?>
