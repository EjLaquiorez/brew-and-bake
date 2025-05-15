<?php
session_start();
require_once "db.php";
require_once "auth.php";

// Set the content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] == '1';

    // Validate input
    if (empty($email) || empty($password)) {
        $response['message'] = 'Please enter both email and password.';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // User not found - don't reveal this information
            $response['message'] = 'Invalid email or password.';
            echo json_encode($response);
            exit;
        }

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Check if account is verified
            if ($user['verification_status'] == 1) {
                // Set session or remember me cookie
                if ($remember) {
                    setRememberMe($user['id'], $user['role']);
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                }

                // Set success response
                $response['success'] = true;
                $response['message'] = 'Login successful!';

                // Role-based redirect
                switch ($user['role']) {
                    case 'admin':
                        $response['redirect'] = '/brew-and-bake/templates/admin/dashboard.php';
                        break;
                    case 'staff':
                        $response['redirect'] = '/brew-and-bake/templates/staff/staff.php';
                        break;
                    case 'client':
                        $response['redirect'] = '/brew-and-bake/templates/client/client.php';
                        break;
                    default:
                        $response['success'] = false;
                        $response['message'] = 'Invalid user role.';
                        break;
                }

                // Log the login attempt
                error_log("User {$user['id']} ({$user['email']}) logged in successfully with role {$user['role']}.");
            } else {
                // Account needs verification
                $response['message'] = 'Please verify your email address before logging in.';

                // Include verification link in the response
                $verificationLink = "http://localhost/brew-and-bake/templates/includes/verify.php?token=" . $user['verification_token'];
                $response['verification_link'] = $verificationLink;

                // Generate a QR code for the verification link
                $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verificationLink);
                $response['qr_code'] = $qrCodeUrl;

                // Add test email flag and email_sent flag for consistency with register_handler.php
                $response['test_email'] = true;
                $response['email_sent'] = false;

                // Log the verification link
                error_log("Verification link for {$user['email']}: {$verificationLink}");
            }
        } else {
            // Invalid password - don't reveal this information
            $response['message'] = 'Invalid email or password.';
        }
    } catch (PDOException $e) {
        // Log the error but don't expose details to the user
        error_log("Login error: " . $e->getMessage());
        $response['message'] = 'An error occurred during login. Please try again.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
echo json_encode($response);
