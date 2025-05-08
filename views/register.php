<?php
require_once "../includes/db.php";
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['register'])) {
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    // 🔍 Check if email already exists
    $checkStmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $checkStmt->execute([$email]);

    if ($checkStmt->rowCount() > 0) {
        $error = "Email already registered. Try logging in or use a different email.";
    } else {
        try {
            // ✅ Insert if email is unique
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_token) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password, $token]);

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ejlqrz@gmail.com';
                $mail->Password = 'nhnm zuna mamd ydzf'; // Your generated app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                
                $mail->setFrom('ejlqrz@gmail.com', 'Brew & Bake');
                $mail->addAddress($email, $name);

                $verify_link = "http://localhost/brew-and-bake/verify.php?token=$token";

                $mail->isHTML(true);
                $mail->Subject = 'Verify your email';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #6F4E37;'>Welcome to Brew & Bake!</h2>
                        <p>Thank you for registering. Please click the button below to verify your email address:</p>
                        <a href='$verify_link' style='display: inline-block; background-color: #6F4E37; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0;'>Verify Email</a>
                        <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
                        <p style='color: #666;'>$verify_link</p>
                        <p>This link will expire in 24 hours.</p>
                    </div>";

                if ($mail->send()) {
                    $success = "Registration successful! Please check your email to verify your account.";
                } else {
                    $warning = "Verification email could not be sent, but the registration was successful. Please try again.";
                }

            } catch (Exception $e) {
                $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

        } catch (PDOException $e) {
            $error = "Error occurred while registering. Please try again later.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h3><i class="bi bi-cup-hot"></i> Brew & Bake</h3>
            <p>Create your account to start your coffee journey</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if (isset($warning)): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $warning ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="registerForm">
            <div class="form-group">
                <input type="text" 
                       name="name" 
                       class="form-control" 
                       placeholder="Full Name" 
                       required 
                       pattern="[A-Za-z\s]+"
                       title="Please enter a valid name (letters and spaces only)">
            </div>

            <div class="form-group">
                <input type="email" 
                       name="email" 
                       class="form-control" 
                       placeholder="Email Address" 
                       required>
            </div>

            <div class="form-group">
                <input type="password" 
                       name="password" 
                       class="form-control" 
                       id="password"
                       placeholder="Password" 
                       required 
                       minlength="8"
                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                       title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                <div class="password-strength">
                    <div class="password-strength-bar"></div>
                </div>
                <small class="text-muted">
                    Password must be at least 8 characters long and include uppercase, lowercase, and numbers
                </small>
            </div>

            <button type="submit" name="register" class="btn btn-register">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>

    <script>
        // Password strength indicator
        const password = document.getElementById('password');
        const strengthBar = document.querySelector('.password-strength-bar');

        password.addEventListener('input', function() {
            const val = password.value;
            let strength = 0;
            
            if (val.length >= 8) strength += 1;
            if (val.match(/[a-z]/)) strength += 1;
            if (val.match(/[A-Z]/)) strength += 1;
            if (val.match(/[0-9]/)) strength += 1;
            if (val.match(/[^a-zA-Z0-9]/)) strength += 1;

            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const name = document.querySelector('input[name="name"]').value;
            
            if (!/^[A-Za-z\s]+$/.test(name)) {
                e.preventDefault();
                alert('Please enter a valid name (letters and spaces only)');
                return;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return;
            }

            if (!/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one number, one uppercase and one lowercase letter');
                return;
            }
        });
    </script>
</body>
</html>
