<?php
session_start();

// Check if verification info is available in session or query parameters
$email = $_SESSION['verification_email'] ?? '';
$token = $_SESSION['verification_token'] ?? '';

// If not in session, check query parameters
if (empty($email) || empty($token)) {
    if (isset($_GET['email']) && isset($_GET['token'])) {
        $email = $_GET['email'];
        $token = $_GET['token'];

        // Store in session for future use
        $_SESSION['verification_email'] = $email;
        $_SESSION['verification_token'] = $token;
    }
}

// If still not available, redirect to login page
if (empty($email) || empty($token)) {
    // Redirect to login page if no verification info
    header("Location: ../../index.php");
    exit;
}

$verificationLink = "http://localhost/brew-and-bake/templates/includes/verify.php?token=" . $token;
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($verificationLink);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Required - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/login.css">
    <link rel="stylesheet" href="../../assets/css/navigation.css">
    <style>
        .verification-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .verification-icon {
            font-size: 4rem;
            color: #f59e0b;
            margin-bottom: 1rem;
        }
        .verification-link {
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        .qr-code {
            margin: 1.5rem auto;
            max-width: 150px;
        }
    </style>
</head>
<body>
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <div class="container">
        <div class="verification-container text-center">
            <div class="verification-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h2>Email Verification Required</h2>
            <p class="lead">Please verify your email address to continue.</p>

            <div class="alert alert-info">
                <p>We've sent a verification link to <strong><?= htmlspecialchars($email) ?></strong>.</p>
                <p>Please check your inbox and click the verification link to activate your account.</p>
            </div>

            <div class="mt-4">
                <h5>Didn't receive the email?</h5>
                <p>Check your spam folder or use the verification link below:</p>

                <div class="verification-link">
                    <a href="<?= htmlspecialchars($verificationLink) ?>" target="_blank">
                        <?= htmlspecialchars($verificationLink) ?>
                    </a>
                </div>

                <div class="qr-code">
                    <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="Verification QR Code" class="img-fluid">
                </div>

                <p class="text-muted small">You can also scan this QR code to verify your email.</p>
            </div>

            <div class="mt-4">
                <a href="login.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Back to Login
                </a>
                <a href="#" class="btn btn-primary ms-2" id="resendBtn">
                    <i class="fas fa-paper-plane me-2"></i> Resend Verification Email
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Check for verification data in sessionStorage
        document.addEventListener('DOMContentLoaded', function() {
            const storedEmail = sessionStorage.getItem('verification_email');
            const storedToken = sessionStorage.getItem('verification_token');

            if (storedEmail && storedToken && (
                '<?= $email ?>' === '' || '<?= $token ?>' === ''
            )) {
                // Redirect with the stored data
                window.location.href = 'verification_required.php?email=' +
                    encodeURIComponent(storedEmail) + '&token=' +
                    encodeURIComponent(storedToken);
            }
        });

        // Handle resend verification email
        document.getElementById('resendBtn').addEventListener('click', function(e) {
            e.preventDefault();

            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';
            this.disabled = true;

            // Get email from page or sessionStorage
            const email = '<?= urlencode($email) ?>' || sessionStorage.getItem('verification_email');

            if (!email) {
                alert('Email address not found. Please try again.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Resend Verification Email';
                return;
            }

            // Send AJAX request to resend verification email
            fetch('../includes/resend_verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=' + email
            })
            .then(response => response.json())
            .then(data => {
                // Reset button
                this.disabled = false;

                if (data.success) {
                    this.innerHTML = '<i class="fas fa-check me-2"></i> Email Sent';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-success');

                    // Show success message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-success mt-3';
                    alertDiv.innerHTML = data.message;
                    document.querySelector('.verification-container').appendChild(alertDiv);

                    // Remove success message after 5 seconds
                    setTimeout(() => {
                        alertDiv.remove();
                        this.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Resend Verification Email';
                        this.classList.remove('btn-success');
                        this.classList.add('btn-primary');
                    }, 5000);
                } else {
                    this.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Resend Verification Email';

                    // Show error message
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger mt-3';
                    alertDiv.innerHTML = data.message || 'An error occurred. Please try again.';
                    document.querySelector('.verification-container').appendChild(alertDiv);

                    // Remove error message after 5 seconds
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Resend Verification Email';

                // Show error message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger mt-3';
                alertDiv.innerHTML = 'An error occurred. Please try again.';
                document.querySelector('.verification-container').appendChild(alertDiv);

                // Remove error message after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            });
        });
    </script>
</body>
</html>
