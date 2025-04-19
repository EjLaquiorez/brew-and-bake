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
        echo "<div class='alert alert-danger'>Email already registered. Try logging in or use a different email.</div>";
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
                $mail->Body    = "Click the link to verify your account: <a href='$verify_link'>Verify Email</a>";

                if ($mail->send()) {
                    echo "<div class='alert alert-success'>Verification email sent!</div>";
                } else {
                    echo "<div class='alert alert-warning'>Verification email could not be sent, but the registration was successful. Please try again.</div>";
                }

            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
            }

        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Error occurred while registering. Please try again later.</div>";
            // Log error for debugging purposes (optional)
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
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Register an Account</h3>
    <form action="" method="POST">
        <input type="text" name="name" class="form-control mb-2" placeholder="Name" required>
        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
        <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
        <button name="register" class="btn btn-primary">Register</button>
    </form>
</div>
</body>
</html>
