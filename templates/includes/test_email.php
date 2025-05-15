<?php
// Set content type to JSON for AJAX responses
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

// Check if required parameters are provided
if (!isset($_POST['email']) || !isset($_POST['token'])) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

$email = $_POST['email'];
$token = $_POST['token'];
$name = $_POST['name'] ?? 'User';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email address';
    echo json_encode($response);
    exit;
}

// Create verification link
$verificationLink = "http://localhost/brew-and-bake/templates/includes/verify.php?token=" . $token;

// Log the test email attempt
error_log("Test verification email requested for: {$email} with token: {$token}");
error_log("Verification link: {$verificationLink}");

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include the autoloader from the project root
require_once __DIR__ . '/../../vendor/autoload.php';

// Try to send email using PHPMailer
$emailSent = false;

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
                <h2>Hello, ' . htmlspecialchars($name) . '!</h2>
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
    $mail->AltBody = "Hello, {$name}! Please verify your email by clicking this link: {$verificationLink}";

    $mail->send();
    $emailSent = true;

    // Log success
    error_log("Verification email successfully sent to: {$email}");

} catch (Exception $e) {
    // Log error
    error_log("Email sending failed: " . $e->getMessage());
}

// Prepare response
if ($emailSent) {
    $response['success'] = true;
    $response['message'] = 'Verification email has been sent to ' . $email;
} else {
    // Even if email sending fails, provide a success response with the verification link
    $response['success'] = true;
    $response['message'] = 'Email sending failed, but verification is still possible using the link below.';
    $response['verification_link'] = $verificationLink;
}

// Return JSON response
echo json_encode($response);
