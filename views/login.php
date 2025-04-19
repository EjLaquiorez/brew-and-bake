<?php
session_start();
require_once "../includes/db.php";

// Handle login form submission
$alert = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user['verification_status'] == 1) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        
        // Role-based redirect
        switch ($user['role']) {
            case 'admin':
                header("Location: ../dashboard/admin.php");
                exit;
            case 'staff':
                header("Location: ../dashboard/staff.php");
                exit;
            case 'client':
                header("Location: ../dashboard/client.php");
                exit;
            default:
                $alert = "<div class='alert alert-warning'>Invalid user role.</div>";
        }
    } else {
        $alert = "<div class='alert alert-warning'>⚠️ Please verify your email address first.</div>";
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <h3 class="mb-4 text-center">☕ Login to <strong>Brew & Bake</strong></h3>

    <?= $alert ?>

    <form action="" method="POST" class="shadow p-4 bg-white rounded">
        <div class="mb-3">
            <label for="email" class="form-label">📧 Email</label>
            <input type="email" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">🔑 Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button name="login" class="btn btn-success w-100">Login</button>
    </form>

    <p class="text-center mt-3">
        Don’t have an account? <a href="register.php">Sign up</a>
    </p>
</div>
</body>
</html>
