<?php
session_start();
require_once "db.php";

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    // Server-side validation
    $errors = [];

    // Check required fields
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $errors[] = 'All fields are required.';
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate password
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    // Check password strength
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^a-zA-Z0-9]/', $password);

    if (!($hasLowercase && ($hasUppercase || $hasNumber || $hasSpecial))) {
        $errors[] = 'Password must include lowercase letters and at least one uppercase letter, number, or special character.';
    }

    // Check passwords match
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    // Check terms agreement
    if (!$terms) {
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy.';
    }

    // Return errors if any
    if (!empty($errors)) {
        $response['message'] = $errors[0]; // Return first error
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

        // Combine first and last name
        $fullName = $firstName . ' ' . $lastName;

        // Set verification status to 0 (unverified)
        $verificationStatus = 0;

        // Begin transaction
        $conn->beginTransaction();

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, verification_token, verification_status, created_at) VALUES (?, ?, ?, 'client', ?, ?, NOW())");
        $stmt->execute([$fullName, $email, $hashedPassword, $verificationToken, $verificationStatus]);

        // Get the new user ID
        $userId = $conn->lastInsertId();

        // Create verification link
        $verificationLink = "http://localhost/brew-and-bake/templates/includes/verify.php?token=" . $verificationToken;

        // Log the registration
        error_log("New user registered: ID {$userId}, {$email} with verification token: {$verificationToken}");
        error_log("Verification link: {$verificationLink}");

        // Automatically send verification email
        $emailSent = false;
        $emailError = '';

        // Include the autoloader
        require_once __DIR__ . '/../../vendor/autoload.php';

        // Try to send email using PHPMailer
        try {
            $mail = new PHPMailer(true);

            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ejlqrz@gmail.com'; // Your Gmail address
            $mail->Password = 'nhnm zuna mamd ydzf'; // Your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('ejlqrz@gmail.com', 'Brew & Bake');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - Brew & Bake';

            // Create HTML email body with verification link
            $emailBody = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; padding: 20px 0; }
                    .logo { font-size: 24px; font-weight: bold; color: #111827; }
                    .content { background-color: #f9f9f9; padding: 20px; border-radius: 5px; }
                    .button { display: inline-block; background-color: #111827; color: white; padding: 12px 24px;
                              text-decoration: none; border-radius: 4px; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <div class="logo">Brew & Bake</div>
                    </div>
                    <div class="content">
                        <h2>Hello, ' . htmlspecialchars($fullName) . '!</h2>
                        <p>Thank you for registering with Brew & Bake. To complete your registration and verify your email address, please click the button below:</p>

                        <div style="text-align: center;">
                            <a href="' . $verificationLink . '" class="button">Verify My Email</a>
                        </div>

                        <p>If the button above doesn\'t work, you can also copy and paste the following link into your browser:</p>
                        <p style="word-break: break-all;"><a href="' . $verificationLink . '">' . $verificationLink . '</a></p>

                        <p>This link will expire in 24 hours for security reasons.</p>
                    </div>
                    <div class="footer">
                        <p>This email was sent to ' . htmlspecialchars($email) . ' because you registered at Brew & Bake.</p>
                        <p>&copy; ' . date('Y') . ' Brew & Bake. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>';

            $mail->Body = $emailBody;
            $mail->AltBody = "Hello, {$fullName}! Please verify your email by clicking this link: {$verificationLink}";

            $mail->send();
            $emailSent = true;

            // Log success
            error_log("Verification email automatically sent to: {$email}");

        } catch (Exception $e) {
            // Log error
            $emailError = $e->getMessage();
            error_log("Automatic email sending failed: " . $emailError);
        }

        // Commit transaction
        $conn->commit();

        // Set success response
        $response['success'] = true;
        $response['message'] = 'Registration successful!';
        $response['verification_link'] = $verificationLink;
        $response['email_sent'] = $emailSent;

        // Generate a QR code for the verification link
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verificationLink);
        $response['qr_code'] = $qrCodeUrl;

        // Add test email flag (only if automatic email failed)
        $response['test_email'] = !$emailSent;

    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        // Log the error but don't expose details to the user
        error_log("Registration error: " . $e->getMessage());
        $response['message'] = 'An error occurred during registration. Please try again.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Return JSON response
echo json_encode($response);
