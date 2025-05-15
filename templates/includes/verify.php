<?php
session_start();
require_once "db.php";

// Initialize variables
$message = "";
$status = "error";

// Check if token is provided
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);

    if (empty($token)) {
        $status = "warning";
        $message = createMessage(
            'warning',
            'Verification Link Incomplete',
            'The verification token is empty.',
            'Please use the complete verification link from your email.'
        );
    } else {
        try {
            // Check if token exists in database
            $stmt = $conn->prepare("SELECT * FROM users WHERE verification_token = ?");
            $stmt->execute([$token]);

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user['verification_status'] == 0) {
                    // Update user verification status
                    $updateStmt = $conn->prepare("UPDATE users SET verification_status = 1, verification_token = NULL WHERE verification_token = ?");
                    $updateStmt->execute([$token]);

                    // Log successful verification
                    error_log("User {$user['id']} ({$user['email']}) verified their account successfully.");

                    // Set session variables for login page
                    $_SESSION['verification_success'] = true;
                    $_SESSION['verification_email'] = $user['email'];

                    // Create success message
                    $status = "success";
                    $message = "
                    <div class='verification-success text-center'>
                        <div class='verification-icon mb-4'>
                            <i class='bi bi-check-circle-fill text-success' style='font-size: 4rem;'></i>
                        </div>
                        <h4 class='mb-3'>Email Verified Successfully!</h4>
                        <p class='mb-4'>Thank you, <strong>{$user['name']}</strong>! Your email address <strong>{$user['email']}</strong> has been verified.</p>
                        <div class='alert alert-success mb-4'>
                            <i class='bi bi-shield-check me-2'></i> Your account is now active and you can log in to access all features.
                        </div>
                        <a href='../../index.php' class='btn btn-primary btn-lg'>
                            <i class='bi bi-box-arrow-in-right me-2'></i> Log In Now
                        </a>
                    </div>";
                } else {
                    // Account already verified
                    $status = "info";
                    $message = "
                    <div class='verification-already text-center'>
                        <div class='verification-icon mb-4'>
                            <i class='bi bi-check-circle-fill text-info' style='font-size: 4rem;'></i>
                        </div>
                        <h4 class='mb-3'>Account Already Verified</h4>
                        <p class='mb-4'>Hello, <strong>{$user['name']}</strong>! Your account has already been verified.</p>
                        <div class='alert alert-info mb-4'>
                            <i class='bi bi-info-circle me-2'></i> You can log in to access all features of Brew & Bake.
                        </div>
                        <a href='../../index.php' class='btn btn-primary btn-lg'>
                            <i class='bi bi-box-arrow-in-right me-2'></i> Go to Login
                        </a>
                    </div>";
                }
            } else {
                // Invalid token
                $status = "danger";
                $message = "
                <div class='verification-error text-center'>
                    <div class='verification-icon mb-4'>
                        <i class='bi bi-x-circle-fill text-danger' style='font-size: 4rem;'></i>
                    </div>
                    <h4 class='mb-3'>Verification Failed</h4>
                    <p class='mb-4'>The verification link is invalid or has expired.</p>
                    <div class='alert alert-danger mb-4'>
                        <i class='bi bi-exclamation-triangle me-2'></i> Please try registering again or contact support if you continue to experience issues.
                    </div>
                    <a href='../../index.php' class='btn btn-primary btn-lg'>
                        <i class='bi bi-house me-2'></i> Return to Homepage
                    </a>
                </div>";
            }
        } catch (PDOException $e) {
            // Log the error
            error_log("Verification error: " . $e->getMessage());

            // Database error
            $status = "danger";
            $message = "
            <div class='verification-error text-center'>
                <div class='verification-icon mb-4'>
                    <i class='bi bi-database-x text-danger' style='font-size: 4rem;'></i>
                </div>
                <h4 class='mb-3'>Database Error</h4>
                <p class='mb-4'>An error occurred while verifying your account.</p>
                <div class='alert alert-danger mb-4'>
                    <i class='bi bi-exclamation-triangle me-2'></i> Please try again later or contact support.
                </div>
                <a href='../../index.php' class='btn btn-primary btn-lg'>
                    <i class='bi bi-house me-2'></i> Return to Homepage
                </a>
            </div>";
        }
    }
} else {
    // No token provided
    $status = "warning";
    $message = "
    <div class='verification-error text-center'>
        <div class='verification-icon mb-4'>
            <i class='bi bi-question-circle-fill text-warning' style='font-size: 4rem;'></i>
        </div>
        <h4 class='mb-3'>Verification Link Incomplete</h4>
        <p class='mb-4'>No verification token was found in the URL.</p>
        <div class='alert alert-warning mb-4'>
            <i class='bi bi-exclamation-triangle me-2'></i> Please use the complete verification link from your email or registration confirmation.
        </div>
        <a href='../../index.php' class='btn btn-primary btn-lg'>
            <i class='bi bi-house me-2'></i> Return to Homepage
        </a>
    </div>";
}

/**
 * Helper function to create a formatted message (not used in this version but available for future use)
 */
function createMessage($type, $title, $message, $details) {
    $icons = [
        'success' => 'bi-check-circle-fill text-success',
        'info' => 'bi-info-circle-fill text-info',
        'warning' => 'bi-exclamation-circle-fill text-warning',
        'danger' => 'bi-x-circle-fill text-danger'
    ];

    $buttonText = ($type === 'success' || $type === 'info') ? 'Log In Now' : 'Return to Homepage';
    $buttonIcon = ($type === 'success' || $type === 'info') ? 'bi-box-arrow-in-right' : 'bi-house';

    return "
    <div class='verification-result text-center'>
        <div class='verification-icon mb-4'>
            <i class='bi {$icons[$type]}' style='font-size: 4rem;'></i>
        </div>
        <h4 class='mb-3'>{$title}</h4>
        <p class='mb-4'>{$message}</p>
        <div class='alert alert-{$type} mb-4'>
            <i class='bi bi-info-circle me-2'></i> {$details}
        </div>
        <a href='../../index.php' class='btn btn-primary btn-lg'>
            <i class='bi {$buttonIcon} me-2'></i> {$buttonText}
        </a>
    </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #111827;
            --color-secondary: #4B5563;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 2rem;
        }
        .verification-card {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        /* Verification status styling */
        .verification-success,
        .verification-already,
        .verification-error {
            padding: 1rem;
        }

        .verification-icon {
            display: inline-block;
            padding: 1rem;
            border-radius: 50%;
            background-color: #f8f9fa;
        }

        .btn-primary {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }

        .alert {
            border-left: 4px solid;
        }

        .alert-success {
            border-left-color: var(--color-secondary);
        }

        .alert-info {
            border-left-color: #0dcaf0;
        }

        .alert-warning {
            border-left-color: #ffc107;
        }

        .alert-danger {
            border-left-color: #dc3545;
        }
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        .logo i {
            color: var(--color-secondary);
            margin-right: 0.5rem;
        }
        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
        }
        .btn-primary:hover {
            background-color: var(--color-secondary);
            border-color: var(--color-secondary);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-card">
            <div class="logo">
                <i class="bi bi-cup-hot"></i> Brew & Bake
            </div>
            <h3 class="mb-4 text-center">Email Verification</h3>
            <?= $message ?>
            <div class="text-center mt-4">
                <a href="../../index.php" class="btn btn-primary">Return to Homepage</a>
            </div>
        </div>
    </div>
</body>
</html>
