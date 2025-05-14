<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // This should be relative to this file

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'ejlqrz@gmail.com';
    $mail->Password = 'nhnm zuna mamd ydzf'; // Replace with your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('ejlqrz@gmail.com', 'Brew & Bake');
    $mail->addAddress('ejlqrzl@gmail.com'); // Replace with your own email

    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Brew & Bake';
    $mail->Body = '✅ This is a test email to check if SMTP is working.';

    $mail->send();
    echo '✅ Email has been sent!';
} catch (Exception $e) {
    echo "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
