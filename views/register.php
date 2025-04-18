<?php
require_once "../includes/db.php";
require '../vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(32));

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_token) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $token]);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@gmail.com'; // Your email
        $mail->Password   = 'your_app_password'; // App password or real pass (not recommended)
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('your_email@gmail.com', 'Brew & Bake');
        $mail->addAddress($email, $name);

        $verify_link = "http://localhost/brew-and-bake/verify.php?token=$token";

        $mail->isHTML(true);
        $mail->Subject = 'Verify your email';
        $mail->Body    = "Click the link to verify your account: <a href='$verify_link'>Verify Email</a>";

        $mail->send();
        echo "<div class='alert alert-success'>Verification email sent!</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
    }
}
?>


<!DOCTYPE html>
<html>
<head>
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

