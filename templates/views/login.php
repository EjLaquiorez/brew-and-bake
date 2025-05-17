<?php
session_start();
require_once "../includes/db.php";
require_once "../includes/auth.php";

// Handle login form submission
$alert = "";

// Display logout message if exists
if (isset($_SESSION['logout_message'])) {
    $alert = "<div class='alert alert-success'>✅ " . htmlspecialchars($_SESSION['logout_message']) . "</div>";
    unset($_SESSION['logout_message']);
}

// Display login error if exists
if (isset($_SESSION['login_error'])) {
    $alert = "<div class='alert alert-danger'>⚠️ " . htmlspecialchars($_SESSION['login_error']) . "</div>";
    unset($_SESSION['login_error']);
}

// Check if user is already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();

    // Role-based redirect
    switch ($role) {
        case 'admin':
            header("Location: /brew-and-bake/templates/admin/dashboard.php");
            exit;
        case 'staff':
            header("Location: /brew-and-bake/templates/staff/staff.php");
            exit;
        case 'client':
            header("Location: /brew-and-bake/templates/client/client.php");
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
    <link rel="stylesheet" href="../../assets/css/navigation.css">
</head>
<body>
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <div class="container">
        <div class="login-container">
            <div class="row login-row g-0">
                <div class="col-md-6 login-image"></div>
                <div class="col-md-6 login-form">
                    <div class="text-center mb-4">
                        <div class="brand-logo">
                            <i class="fas fa-coffee"></i> Brew & Bake
                        </div>
                        <h2 class="text-muted">Welcome Back!</h2>
                        <p class="text-muted">Sign in to continue to your account</p>
                    </div>

                    <?= $alert ?>

                    <form action="../includes/login_process.php" method="POST">
                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" name="email" class="form-control" placeholder="Email address" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label remember-me" for="remember">Remember me</label>
                        </div>

                        <button name="login" class="btn btn-login btn-primary w-100 mb-4">
                            <i class="fas fa-sign-in-alt me-2"></i> Sign In
                        </button>

                        <div class="divider">
                            <span>or continue with</span>
                        </div>

                        <div class="social-login mb-4">
                            <a href="#" class="social-btn google">
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="social-btn facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-btn twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>

                        <p class="text-center mb-0">
                            Don't have an account?
                            <a href="register.php" class="text-decoration-none" style="color: var(--coffee-brown);">
                                Sign up
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        // Show/Hide Password Toggle
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            // Toggle the password visibility
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Toggle the eye icon
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    </script>
</body>
</html>
