<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: ../../views/login.php");
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

// Simulate user data
$userData = [
    'id' => 1,
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'phone' => '09123456789',
    'address' => '123 Main Street, Manila, Philippines',
    'role' => 'admin',
    'created_at' => '2024-01-15 10:30:00'
];

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            // Process profile update
            $userData['name'] = $_POST['name'];
            $userData['email'] = $_POST['email'];
            $userData['phone'] = $_POST['phone'];
            $userData['address'] = $_POST['address'];

            // Show success message
            $successMessage = 'Profile updated successfully!';
        } elseif ($_POST['action'] === 'change_password') {
            // Process password change
            // In a real app, validate current password and update with new password

            // Show success message
            $successMessage = 'Password changed successfully!';
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
    <title>Profile - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
<!-- Admin Container -->
<div class="admin-container">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <div class="sidebar-logo">
                    <i class="bi bi-cup-hot"></i>
                </div>
                <div>
                    <h3 class="sidebar-title">Brew & Bake</h3>
                    <p class="sidebar-subtitle">Admin Dashboard</p>
                </div>
            </a>
            <button class="sidebar-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section">
                <h6 class="nav-section-title">Main</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="bi bi-speedometer2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link">
                            <i class="bi bi-receipt"></i>
                            Orders
                            <span class="nav-badge">5</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products.php" class="nav-link">
                            <i class="bi bi-box-seam"></i>
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link">
                            <i class="bi bi-tags"></i>
                            Categories
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h6 class="nav-section-title">Analytics</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="analytics.php" class="nav-link">
                            <i class="bi bi-bar-chart-line"></i>
                            Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="sales.php" class="nav-link">
                            <i class="bi bi-graph-up"></i>
                            Sales
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h6 class="nav-section-title">Settings</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link active">
                            <i class="bi bi-person"></i>
                            Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="bi bi-gear"></i>
                            System Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <?php include 'includes/sidebar-user-menu.php'; ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Include Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Content Area -->
        <div class="admin-content">
            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <div class="alert-content">
                            <h6 class="alert-title">Success</h6>
                            <p class="alert-text"><?= htmlspecialchars($successMessage) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <h6 class="alert-title">Error</h6>
                            <p class="alert-text"><?= htmlspecialchars($errorMessage) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h1 class="page-title">Profile</h1>
                        <p class="text-muted">Manage your account settings and preferences</p>
                    </div>
                </div>
            </div>

            <!-- Grid Layout -->
            <div class="row mb-4">
                <!-- First Row -->
                <div class="col-md-4 mb-4">
                    <!-- Profile Overview -->
                    <div class="card card-primary fade-in h-100" style="border: none;">
                        <div class="card-body">
                            <div class="d-flex flex-column align-items-center text-center">
                                <div class="profile-avatar mb-3" style="width: 80px; height: 80px; font-size: 2rem; background-color: var(--color-primary);">
                                    <?= substr($userData['name'], 0, 1) ?>
                                </div>
                                <h4 class="mb-1"><?= htmlspecialchars($userData['name']) ?></h4>
                                <p class="text-muted mb-2"><?= ucfirst($userData['role']) ?></p>
                                <p class="text-muted small">Member since <?= date('F Y', strtotime($userData['created_at'])) ?></p>
                                <button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#avatarModal">
                                    <i class="bi bi-camera me-2"></i> Change Avatar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8 mb-4">
                    <!-- Security Settings -->
                    <div class="card fade-in-right h-100" style="border: none;">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-shield-lock"></i> Security</h5>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="post">
                                <input type="hidden" name="action" value="change_password">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control form-control-sm" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control form-control-sm" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <input type="password" class="form-control form-control-sm" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="bi bi-key me-2"></i> Change Password
                                    </button>
                                </div>
                            </form>

                            <hr class="my-3">

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Two-Factor Authentication</h6>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="twoFactorToggle">
                                        <label class="form-check-label" for="twoFactorToggle">Enable 2FA</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6>Active Sessions</h6>
                                            <p class="text-muted small">1 active session</p>
                                        </div>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-box-arrow-right me-1"></i> Logout All
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <div class="row mb-4">
                <div class="col-md-8 mb-4">
                    <!-- Recent Activity -->
                    <div class="card fade-in h-100" style="border: none;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
                            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="compact-activity-list" style="max-height: 250px;">
                                <div class="activity-row">
                                    <div class="activity-icon-sm success">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div class="activity-content-compact">
                                        <h6 class="activity-title-sm">Profile Updated</h6>
                                        <p class="activity-text-sm">You updated your profile information.</p>
                                        <span class="activity-time-sm">Today at 10:30 AM</span>
                                    </div>
                                </div>

                                <div class="activity-row">
                                    <div class="activity-icon-sm primary">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                    <div class="activity-content-compact">
                                        <h6 class="activity-title-sm">Product Added</h6>
                                        <p class="activity-text-sm">You added a new product "Ube Cake".</p>
                                        <span class="activity-time-sm">Yesterday at 3:45 PM</span>
                                    </div>
                                </div>

                                <div class="activity-row">
                                    <div class="activity-icon-sm info">
                                        <i class="bi bi-receipt"></i>
                                    </div>
                                    <div class="activity-content-compact">
                                        <h6 class="activity-title-sm">Order Processed</h6>
                                        <p class="activity-text-sm">You processed order #1002.</p>
                                        <span class="activity-time-sm">May 14, 2025</span>
                                    </div>
                                </div>

                                <div class="activity-row">
                                    <div class="activity-icon-sm warning">
                                        <i class="bi bi-key"></i>
                                    </div>
                                    <div class="activity-content-compact">
                                        <h6 class="activity-title-sm">Password Changed</h6>
                                        <p class="activity-text-sm">You changed your account password.</p>
                                        <span class="activity-time-sm">May 10, 2025</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <!-- Account Actions -->
                    <div class="card fade-in-right h-100" style="border: none;">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-gear"></i> Account Actions</h5>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <p class="mb-4">Manage your account settings and preferences</p>

                                <div class="d-grid gap-3 mb-4">
                                    <a href="../logout.php" class="btn btn-outline-primary">
                                        <i class="bi bi-box-arrow-left me-2"></i> Logout
                                    </a>
                                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                                        <i class="bi bi-person-x me-2"></i> Deactivate Account
                                    </button>
                                </div>
                            </div>

                            <div class="mt-auto">
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <i class="bi bi-info-circle fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="alert-heading">Need Help?</h6>
                                            <p class="mb-0 small">Contact support for assistance with your account.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Third Row -->
            <div class="row mb-4">
                <!-- Profile Information (Two Yellow Boxes) -->
                <div class="col-md-4 mb-4">
                    <div class="card fade-in h-100" style="border: none;">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-person"></i> Personal Info</h5>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="post" class="h-100 d-flex flex-column">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($userData['name']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($userData['phone']) ?>">
                                </div>

                                <div class="mt-auto text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i> Save Personal Info
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card fade-in h-100" style="border: none;">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-geo-alt"></i> Address & Role</h5>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="post" class="h-100 d-flex flex-column">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" value="<?= ucfirst($userData['role']) ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="5"><?= htmlspecialchars($userData['address']) ?></textarea>
                                </div>

                                <div class="mt-auto text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-2"></i> Save Address
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <!-- Preferences -->
                    <div class="card fade-in-right h-100" style="border: none;">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-sliders"></i> Preferences</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h6>Notification Settings</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                    <label class="form-check-label" for="emailNotifications">Email Notifications</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="orderUpdates" checked>
                                    <label class="form-check-label" for="orderUpdates">Order Updates</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="productAlerts">
                                    <label class="form-check-label" for="productAlerts">Product Alerts</label>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6>Display Settings</h6>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="darkMode">
                                    <label class="form-check-label" for="darkMode">Dark Mode</label>
                                </div>
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="compactView">
                                    <label class="form-check-label" for="compactView">Compact View</label>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <button type="button" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i> Save Preferences
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Avatar Upload Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-camera text-primary me-2"></i>
                    Change Profile Picture
                </h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="profile-avatar mx-auto mb-3">
                        <?= substr($userData['name'], 0, 1) ?>
                    </div>
                    <h6>Current Profile Picture</h6>
                </div>

                <div class="mb-3">
                    <label for="avatarUpload" class="form-label">Upload New Picture</label>
                    <input class="form-control" type="file" id="avatarUpload">
                </div>

                <div class="alert alert-info">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div class="alert-content">
                            <p class="alert-text mb-0">Recommended image size: 300x300 pixels. Maximum file size: 2MB. Supported formats: JPG, PNG.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">
                    <i class="bi bi-cloud-upload me-2"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Account Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Deactivate Account
                </h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate your account? This action will:</p>
                <ul class="mb-4">
                    <li>Remove your access to the admin dashboard</li>
                    <li>Archive your account information</li>
                    <li>Preserve your data for future restoration</li>
                </ul>

                <div class="alert alert-danger">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <h6 class="alert-title">Warning</h6>
                            <p class="alert-text mb-0">This action requires approval from a super admin and cannot be immediately undone.</p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="deactivateReason" class="form-label">Reason for deactivation</label>
                    <textarea class="form-control" id="deactivateReason" rows="3" placeholder="Please provide a reason..."></textarea>
                </div>

                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm your password</label>
                    <input type="password" class="form-control" id="confirmPassword" placeholder="Enter your password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">
                    <i class="bi bi-person-x me-2"></i> Deactivate Account
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer-scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password validation
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (newPasswordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity("Passwords don't match");
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            });
        }
    });
</script>
</body>
</html>
