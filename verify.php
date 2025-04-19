<?php
require_once "includes/db.php";
$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();

        if ($user['verification_status'] == 0) {
            $updateStmt = $conn->prepare("UPDATE users SET verification_status = 1, verification_token = NULL WHERE verification_token = ?");
            $updateStmt->execute([$token]);
            $message = "<div class='alert alert-success'>✅ Your account has been verified! You can now <a href='views/login.php'>log in</a>.</div>";
        } else {
            $message = "<div class='alert alert-info'>ℹ️ Your account is already verified. <a href='views/login.php'>Go to login</a>.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>❌ Invalid or expired verification link.</div>";
    }
} else {
    $message = "<div class='alert alert-danger'>⚠️ No verification token found in the URL.</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Account - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h3 class="mb-3">Email Verification</h3>
        <?= $message ?>
    </div>
</body>
</html>
