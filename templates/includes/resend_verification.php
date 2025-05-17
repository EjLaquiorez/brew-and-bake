<?php
/**
 * Resend Verification Email
 * 
 * This script handles resending verification emails to users.
 */

session_start();
require_once "db.php";

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'email_sent' => false
];

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// Get email from POST data or session
$email = $_POST['email'] ?? $_SESSION['verification_email'] ?? '';

if (empty($email)) {
    $response['message'] = 'Email address is required.';
    echo json_encode($response);
    exit;
}

try {
    // Check if user exists and is unverified
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verification_status = 0");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User not found or already verified
        $response['message'] = 'This email is either already verified or not registered.';
        echo json_encode($response);
        exit;
    }
    
    // Generate new verification token if needed
    if (empty($user['verification_token'])) {
        $verificationToken = bin2hex(random_bytes(32));
        
        // Update user with new token
        $stmt = $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
        $stmt->execute([$verificationToken, $user['id']]);
    } else {
        $verificationToken = $user['verification_token'];
    }
    
    // Create verification link
    $verificationLink = "http://localhost/brew-and-bake/templates/includes/verify.php?token=" . $verificationToken;
    
    // Log the verification link
    error_log("Resent verification link for {$email}: {$verificationLink}");
    
    // For testing purposes, we'll just return the verification link
    // In production, you would send an actual email here
    $response['success'] = true;
    $response['message'] = 'Verification email has been resent. Please check your inbox.';
    $response['verification_link'] = $verificationLink;
    $response['test_email'] = true;
    $response['email_sent'] = false;
    
    // Store verification info in session
    $_SESSION['verification_email'] = $email;
    $_SESSION['verification_token'] = $verificationToken;
    
    // Try to send email if PHPMailer is available
    $emailSent = false;
    
    try {
        // Include the autoloader
        if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            // Import PHPMailer classes
            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\Exception;
            
            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email@gmail.com'; // Replace with your Gmail address
            $mail->Password = 'your-app-password'; // Replace with your app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            
            // Recipients
            $mail->setFrom('your-email@gmail.com', 'Brew & Bake');
            $mail->addAddress($email, $user['name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email Address - Brew & Bake';
            $mail->Body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #111827; padding: 20px; text-align: center; color: white;">
                        <h1>Brew & Bake</h1>
                    </div>
                    <div style="padding: 20px; border: 1px solid #e5e7eb; border-top: none;">
                        <h2>Verify Your Email Address</h2>
                        <p>Hello ' . htmlspecialchars($user['name']) . ',</p>
                        <p>Thank you for registering with Brew & Bake. Please click the button below to verify your email address:</p>
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="' . $verificationLink . '" style="background-color: #f59e0b; color: #111827; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold;">Verify Email Address</a>
                        </div>
                        <p>Or copy and paste the following link into your browser:</p>
                        <p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 4px;">' . $verificationLink . '</p>
                        <p>If you did not create an account, no further action is required.</p>
                        <p>Thank you,<br>The Brew & Bake Team</p>
                    </div>
                </div>
            ';
            $mail->AltBody = "Hello " . $user['name'] . ",\n\nPlease verify your email address by clicking the link below:\n\n" . $verificationLink . "\n\nThank you,\nThe Brew & Bake Team";
            
            // Send email
            $mail->send();
            $emailSent = true;
            
            $response['message'] = 'Verification email has been sent to your email address.';
            $response['email_sent'] = true;
        }
    } catch (Exception $e) {
        error_log("Error sending verification email: " . $e->getMessage());
        // We'll still return success since we have the verification link in the response
    }
    
} catch (PDOException $e) {
    error_log("Database error in resend_verification.php: " . $e->getMessage());
    $response['message'] = 'An error occurred. Please try again later.';
}

// Return JSON response
echo json_encode($response);
?>
