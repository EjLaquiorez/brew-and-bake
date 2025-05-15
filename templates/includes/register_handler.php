<?php
session_start();
require_once "db.php";

// Set the content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => ''
];

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    // Validate input
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }

    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        exit;
    }

    if ($password !== $confirmPassword) {
        $response['message'] = 'Passwords do not match.';
        echo json_encode($response);
        exit;
    }

    if (!$terms) {
        $response['message'] = 'You must agree to the Terms of Service and Privacy Policy.';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $response['message'] = 'Email address already in use.';
            echo json_encode($response);
            exit;
        }

        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role, verification_token, verification_status, created_at) VALUES (?, ?, ?, ?, 'client', ?, 0, NOW())");
        $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $verificationToken]);
        
        // Send verification email (simplified for this example)
        // In a real application, you would use a proper email library
        $verificationLink = "http://localhost/brew-and-bake/templates/views/verify.php?token=" . $verificationToken;
        
        // For demonstration purposes, we'll just set success to true
        // In a real application, you would check if the email was sent successfully
        $response['success'] = true;
        $response['message'] = 'Registration successful! Please check your email to verify your account.';
        
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
echo json_encode($response);
