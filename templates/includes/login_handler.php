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
    $remember = isset($_POST['remember']);

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

        if ($user && password_verify($password, $user['password'])) {
            if ($user['verification_status'] == 1) {
                // Set session or remember me cookie
                if ($remember) {
                    setRememberMe($user['id'], $user['role']);
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                }

                // Set success response
                $response['success'] = true;
                $response['message'] = 'Login successful!';

                // Role-based redirect
                switch ($user['role']) {
                    case 'admin':
                        $response['redirect'] = '../admin/dashboard.php';
                        break;
                    case 'staff':
                        $response['redirect'] = '../staff/staff.php';
                        break;
                    case 'client':
                        $response['redirect'] = '../client/client.php';
                        break;
                    default:
                        $response['success'] = false;
                        $response['message'] = 'Invalid user role.';
                        break;
                }
            } else {
                $response['message'] = 'Please verify your email address first.';
            }
        } else {
            $response['message'] = 'Invalid email or password.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
echo json_encode($response);
