<?php
require_once "includes/db.php";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $update->execute([$token]);
        echo "✅ Your email has been verified. You can now <a href='views/login.php'>Login</a>";
    } else {
        echo "❌ Invalid or already used token.";
    }
}
?>
