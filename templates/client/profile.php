<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please log in to access your account settings.";
    header("Location: ../../index.php");
    exit;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Handle messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get user information
$userId = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching user data: " . $e->getMessage();
    $user = [];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate inputs
    if (empty($name) || empty($email)) {
        $errorMessage = "Name and email are required fields.";
    } else {
        try {
            // Check if email already exists for another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->rowCount() > 0) {
                $errorMessage = "Email already in use by another account.";
            } else {
                // Update user profile
                $stmt = $conn->prepare("
                    UPDATE users
                    SET name = ?, email = ?, phone = ?, address = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $phone, $address, $userId]);

                $successMessage = "Profile updated successfully!";

                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $errorMessage = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = "All password fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "New passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $errorMessage = "New password must be at least 8 characters long.";
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $storedPassword = $stmt->fetchColumn();

            if (!password_verify($currentPassword, $storedPassword)) {
                $errorMessage = "Current password is incorrect.";
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);

                $successMessage = "Password changed successfully!";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error changing password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Account Settings - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/menu.css?v=<?= time() ?>">
    <style>
        /* Account settings specific styles */
        .account-section {
            padding: 2rem 0;
        }

        .account-header {
            margin-bottom: 2rem;
        }

        .account-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .account-subtitle {
            color: var(--color-gray-600);
            font-size: 1rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--color-white);
            border-bottom: 1px solid var(--color-gray-200);
            padding: 1.25rem 1.5rem;
        }

        .card-header h2 {
            font-size: 1.25rem;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            color: var(--color-primary);
        }

        .card-header h2 i {
            margin-right: 0.75rem;
            color: var(--color-secondary);
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--color-gray-700);
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: var(--color-primary-dark);
            border-color: var(--color-primary-dark);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .badge {
            padding: 0.35em 0.65em;
            font-weight: 600;
            border-radius: 6px;
        }

        /* Header and menu navigation styling */
        .site-header {
            background-color: var(--color-primary);
            position: relative;
            z-index: 99; /* Lower than menu-nav */
        }

        .menu-nav {
            position: sticky;
            top: 0;
            z-index: 101; /* Higher than site-header */
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            background-color: var(--color-white);
            border-bottom: 1px solid var(--color-gray-200);
        }

        .menu-tabs {
            display: flex;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .menu-tabs li {
            margin: 0;
        }

        .menu-tabs a {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--color-gray-700);
            padding: 1rem 1.5rem;
            display: block;
            position: relative;
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .menu-tabs a:hover {
            color: var(--color-primary);
        }

        .menu-tabs a.active {
            color: var(--color-primary);
            font-weight: 700;
        }

        .menu-tabs a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--color-secondary);
            border-radius: 4px 4px 0 0;
        }

        /* User dropdown styling */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--color-primary-light);
            box-shadow: var(--shadow-md);
            border-radius: var(--radius-md);
            width: 200px;
            padding: 0.5rem 0;
            display: none;
            z-index: 10;
        }

        .user-dropdown.show {
            display: block;
        }

        .user-dropdown ul {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .user-dropdown li a {
            display: block;
            padding: 0.75rem 1rem;
            transition: background-color 0.3s ease;
            color: var(--color-gray-300);
            text-decoration: none;
        }

        .user-dropdown li a:hover,
        .user-dropdown li a.active {
            background-color: var(--color-primary-dark);
            color: var(--color-white);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="../../index.php">
                        <i class="bi bi-cup-hot"></i> Brew & Bake
                    </a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="client.php" class="active">MENU</a></li>
                        <li><a href="client.php#about">ABOUT</a></li>
                        <li><a href="client.php#contact">CONTACT</a></li>
                    </ul>
                </nav>
                <div class="header-actions">
                    <a href="cart.php" class="cart-icon">
                        <i class="bi bi-cart"></i>
                    </a>
                    <a href="#" class="user-icon">
                        <i class="bi bi-person-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Menu Navigation -->
    <div class="menu-nav">
        <div class="container">
            <ul class="menu-tabs">
                <li><a href="client.php">Menu</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="profile.php" class="active">Account Settings</a></li>
            </ul>
        </div>
    </div>

    <!-- User Dropdown Menu -->
    <div class="user-dropdown">
        <ul>
            <li><a href="profile.php" class="active">Account Settings</a></li>
            <li><a href="orders.php">My Orders</a></li>
            <li><a href="../includes/logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="container">
        <section class="account-section">
            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="account-header">
                <h1 class="account-title">Account Settings</h1>
                <p class="account-subtitle">Manage your personal information and security settings</p>
            </div>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-person"></i> Personal Information</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Delivery Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-shield-lock"></i> Change Password</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="bi bi-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-info-circle"></i> Account Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Account Type:</strong> <?= htmlspecialchars(ucfirst($user['role'] ?? 'Client')) ?></p>
                                    <p><strong>Member Since:</strong> <?= isset($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A' ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Last Login:</strong> <?= isset($user['last_login']) ? date('F j, Y, g:i a', strtotime($user['last_login'])) : 'N/A' ?></p>
                                    <p><strong>Account Status:</strong> <span class="badge bg-success">Active</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="bi bi-cup-hot"></i> Brew & Bake
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>About Us</h4>
                        <ul>
                            <li><a href="client.php#about">Our Story</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Social Impact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Customer Service</h4>
                        <ul>
                            <li><a href="client.php#contact">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Store Locator</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="../../index.php">Home</a></li>
                            <li><a href="client.php">Menu</a></li>
                            <li><a href="orders.php">My Orders</a></li>
                            <li><a href="profile.php">Account Settings</a></li>
                            <li><a href="../includes/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Brew & Bake. All rights reserved.</p>
                <div class="social-links">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle user dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const userIcon = document.querySelector('.user-icon');
            const userDropdown = document.querySelector('.user-dropdown');

            if (userIcon && userDropdown) {
                userIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    userDropdown.classList.toggle('show');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userIcon.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('show');
                    }
                });
            }

            // Make menu-nav sticky on scroll
            const menuNav = document.querySelector('.menu-nav');
            const siteHeader = document.querySelector('.site-header');

            if (menuNav && siteHeader) {
                // Get the height of the site header
                const headerHeight = siteHeader.offsetHeight;

                // Initial check on page load
                if (window.scrollY > headerHeight) {
                    menuNav.classList.add('scrolled');
                }

                // Check on scroll
                window.addEventListener('scroll', function() {
                    if (window.scrollY > headerHeight) {
                        menuNav.classList.add('scrolled');
                    } else {
                        menuNav.classList.remove('scrolled');
                    }
                });
            }
        });
    </script>
</body>
</html>
