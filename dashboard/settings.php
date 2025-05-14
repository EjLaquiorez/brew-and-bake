<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: ../views/login.php");
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

// Placeholder settings data
$generalSettings = [
    'site_name' => 'Brew & Bake',
    'site_description' => 'Artisanal coffee and baked goods',
    'contact_email' => 'info@brewandbake.com',
    'contact_phone' => '+63 912 345 6789',
    'address' => '123 Main Street, Manila, Philippines',
    'business_hours' => 'Monday - Sunday: 7:00 AM - 10:00 PM',
    'currency' => 'PHP',
    'tax_rate' => 12,
    'timezone' => 'Asia/Manila'
];

$emailSettings = [
    'smtp_host' => 'smtp.example.com',
    'smtp_port' => 587,
    'smtp_username' => 'notifications@brewandbake.com',
    'smtp_encryption' => 'tls',
    'from_email' => 'no-reply@brewandbake.com',
    'from_name' => 'Brew & Bake'
];

$paymentSettings = [
    'payment_methods' => ['Cash', 'Credit Card', 'Digital Wallet', 'Bank Transfer'],
    'default_payment' => 'Cash',
    'min_order_amount' => 100
];

$notificationSettings = [
    'order_notifications' => true,
    'inventory_alerts' => true,
    'customer_feedback' => true,
    'marketing_updates' => false,
    'system_alerts' => true
];

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'update_general':
            // In a real application, this would update the database
            $successMessage = "General settings updated successfully.";
            break;

        case 'update_email':
            // In a real application, this would update the database
            $successMessage = "Email settings updated successfully.";
            break;

        case 'update_payment':
            // In a real application, this would update the database
            $successMessage = "Payment settings updated successfully.";
            break;

        case 'update_notifications':
            // In a real application, this would update the database
            $successMessage = "Notification settings updated successfully.";
            break;

        default:
            $errorMessage = "Invalid action.";
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
    <title>System Settings - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
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
                        <a href="profile.php" class="nav-link">
                            <i class="bi bi-person"></i>
                            Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link active">
                            <i class="bi bi-gear"></i>
                            System Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="user-menu">
                <div class="user-avatar">
                    <?= substr($_SESSION['user']['name'] ?? 'A', 0, 1) ?>
                </div>
                <div class="user-info">
                    <h6 class="user-name"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></h6>
                    <p class="user-role">Administrator</p>
                </div>
                <i class="bi bi-chevron-down user-menu-toggle"></i>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title">System Settings</h1>
            </div>
            <div class="topbar-right">
                <div class="topbar-icon">
                    <i class="bi bi-bell"></i>
                    <span class="topbar-badge">3</span>
                </div>
                <div class="topbar-icon">
                    <i class="bi bi-envelope"></i>
                    <span class="topbar-badge">5</span>
                </div>
                <div class="topbar-profile">
                    <div class="topbar-avatar">
                        <?= substr($_SESSION['user']['name'] ?? 'A', 0, 1) ?>
                    </div>
                    <span class="topbar-user d-none d-md-block"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></span>
                    <i class="bi bi-chevron-down topbar-dropdown"></i>
                </div>
            </div>
        </header>

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

            <!-- Settings Overview -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="card card-primary fade-in">
                        <div class="card-body p-5">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-2">System Settings</h2>
                                    <p class="text-muted mb-0">Configure your store settings and preferences</p>
                                </div>
                                <div>
                                    <button class="btn btn-outline-primary" id="backupBtn">
                                        <i class="bi bi-download me-2"></i> Backup Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-5">
                <!-- Settings Navigation -->
                <div class="col-lg-3 mb-4">
                    <div class="card fade-in-left">
                        <div class="card-body p-0">
                            <div class="settings-nav">
                                <a href="#general" class="settings-nav-item active" data-bs-toggle="tab" data-bs-target="#general">
                                    <i class="bi bi-gear"></i>
                                    <span>General</span>
                                </a>
                                <a href="#email" class="settings-nav-item" data-bs-toggle="tab" data-bs-target="#email">
                                    <i class="bi bi-envelope"></i>
                                    <span>Email</span>
                                </a>
                                <a href="#payment" class="settings-nav-item" data-bs-toggle="tab" data-bs-target="#payment">
                                    <i class="bi bi-credit-card"></i>
                                    <span>Payment</span>
                                </a>
                                <a href="#notifications" class="settings-nav-item" data-bs-toggle="tab" data-bs-target="#notifications">
                                    <i class="bi bi-bell"></i>
                                    <span>Notifications</span>
                                </a>
                                <a href="#maintenance" class="settings-nav-item" data-bs-toggle="tab" data-bs-target="#maintenance">
                                    <i class="bi bi-tools"></i>
                                    <span>Maintenance</span>
                                </a>
                                <a href="#backup" class="settings-nav-item" data-bs-toggle="tab" data-bs-target="#backup">
                                    <i class="bi bi-cloud-arrow-up"></i>
                                    <span>Backup & Restore</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="col-lg-9 mb-4">
                    <div class="tab-content">
                        <!-- General Settings -->
                        <div class="tab-pane fade show active" id="general">
                            <div class="card fade-in-right">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-gear"></i> General Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form action="settings.php" method="post">
                                        <input type="hidden" name="action" value="update_general">

                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="site_name" class="form-label">Site Name</label>
                                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?= htmlspecialchars($generalSettings['site_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="currency" class="form-label">Currency</label>
                                                <select class="form-select" id="currency" name="currency">
                                                    <option value="PHP" <?= $generalSettings['currency'] === 'PHP' ? 'selected' : '' ?>>Philippine Peso (₱)</option>
                                                    <option value="USD" <?= $generalSettings['currency'] === 'USD' ? 'selected' : '' ?>>US Dollar ($)</option>
                                                    <option value="EUR" <?= $generalSettings['currency'] === 'EUR' ? 'selected' : '' ?>>Euro (€)</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="site_description" class="form-label">Site Description</label>
                                            <textarea class="form-control" id="site_description" name="site_description" rows="2"><?= htmlspecialchars($generalSettings['site_description']) ?></textarea>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="contact_email" class="form-label">Contact Email</label>
                                                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?= htmlspecialchars($generalSettings['contact_email']) ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="contact_phone" class="form-label">Contact Phone</label>
                                                <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($generalSettings['contact_phone']) ?>">
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="address" class="form-label">Business Address</label>
                                            <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($generalSettings['address']) ?></textarea>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="business_hours" class="form-label">Business Hours</label>
                                                <input type="text" class="form-control" id="business_hours" name="business_hours" value="<?= htmlspecialchars($generalSettings['business_hours']) ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" value="<?= htmlspecialchars($generalSettings['tax_rate']) ?>" min="0" max="100" step="0.01">
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="timezone" class="form-label">Timezone</label>
                                            <select class="form-select" id="timezone" name="timezone">
                                                <option value="Asia/Manila" <?= $generalSettings['timezone'] === 'Asia/Manila' ? 'selected' : '' ?>>Philippines (GMT+8)</option>
                                                <option value="America/New_York" <?= $generalSettings['timezone'] === 'America/New_York' ? 'selected' : '' ?>>Eastern Time (GMT-5)</option>
                                                <option value="Europe/London" <?= $generalSettings['timezone'] === 'Europe/London' ? 'selected' : '' ?>>London (GMT+0)</option>
                                            </select>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Email Settings -->
                        <div class="tab-pane fade" id="email">
                            <div class="card fade-in-right">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-envelope"></i> Email Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form action="settings.php" method="post">
                                        <input type="hidden" name="action" value="update_email">

                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="smtp_host" class="form-label">SMTP Host</label>
                                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($emailSettings['smtp_host']) ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="smtp_port" class="form-label">SMTP Port</label>
                                                <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($emailSettings['smtp_port']) ?>">
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="smtp_username" class="form-label">SMTP Username</label>
                                                <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($emailSettings['smtp_username']) ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="smtp_password" class="form-label">SMTP Password</label>
                                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" placeholder="Enter password">
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="smtp_encryption" class="form-label">Encryption</label>
                                                <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                                    <option value="tls" <?= $emailSettings['smtp_encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                                                    <option value="ssl" <?= $emailSettings['smtp_encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                    <option value="none" <?= $emailSettings['smtp_encryption'] === 'none' ? 'selected' : '' ?>>None</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="from_email" class="form-label">From Email</label>
                                                <input type="email" class="form-control" id="from_email" name="from_email" value="<?= htmlspecialchars($emailSettings['from_email']) ?>">
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="from_name" class="form-label">From Name</label>
                                            <input type="text" class="form-control" id="from_name" name="from_name" value="<?= htmlspecialchars($emailSettings['from_name']) ?>">
                                        </div>

                                        <div class="d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-primary" id="testEmailBtn">
                                                <i class="bi bi-envelope-check me-2"></i> Send Test Email
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Settings -->
                        <div class="tab-pane fade" id="payment">
                            <div class="card fade-in-right">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-credit-card"></i> Payment Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form action="settings.php" method="post">
                                        <input type="hidden" name="action" value="update_payment">

                                        <div class="mb-4">
                                            <label class="form-label">Payment Methods</label>
                                            <div class="payment-methods-list">
                                                <?php foreach (['Cash', 'Credit Card', 'Digital Wallet', 'Bank Transfer'] as $method): ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox" id="payment_<?= strtolower(str_replace(' ', '_', $method)) ?>" name="payment_methods[]" value="<?= $method ?>" <?= in_array($method, $paymentSettings['payment_methods']) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="payment_<?= strtolower(str_replace(' ', '_', $method)) ?>">
                                                            <?= $method ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="default_payment" class="form-label">Default Payment Method</label>
                                                <select class="form-select" id="default_payment" name="default_payment">
                                                    <?php foreach ($paymentSettings['payment_methods'] as $method): ?>
                                                        <option value="<?= $method ?>" <?= $paymentSettings['default_payment'] === $method ? 'selected' : '' ?>><?= $method ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="min_order_amount" class="form-label">Minimum Order Amount</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" value="<?= htmlspecialchars($paymentSettings['min_order_amount']) ?>" min="0" step="1">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <div class="tab-pane fade" id="notifications">
                            <div class="card fade-in-right">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-bell"></i> Notification Settings</h5>
                                </div>
                                <div class="card-body">
                                    <form action="settings.php" method="post">
                                        <input type="hidden" name="action" value="update_notifications">

                                        <div class="notification-settings">
                                            <div class="notification-item">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="order_notifications" name="order_notifications" <?= $notificationSettings['order_notifications'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="order_notifications">
                                                        <span class="notification-title">Order Notifications</span>
                                                    </label>
                                                </div>
                                                <p class="notification-description">Receive notifications when new orders are placed.</p>
                                            </div>

                                            <div class="notification-item">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="inventory_alerts" name="inventory_alerts" <?= $notificationSettings['inventory_alerts'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="inventory_alerts">
                                                        <span class="notification-title">Inventory Alerts</span>
                                                    </label>
                                                </div>
                                                <p class="notification-description">Get notified when product inventory is low.</p>
                                            </div>

                                            <div class="notification-item">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="customer_feedback" name="customer_feedback" <?= $notificationSettings['customer_feedback'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="customer_feedback">
                                                        <span class="notification-title">Customer Feedback</span>
                                                    </label>
                                                </div>
                                                <p class="notification-description">Receive notifications for customer reviews and feedback.</p>
                                            </div>

                                            <div class="notification-item">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="marketing_updates" name="marketing_updates" <?= $notificationSettings['marketing_updates'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="marketing_updates">
                                                        <span class="notification-title">Marketing Updates</span>
                                                    </label>
                                                </div>
                                                <p class="notification-description">Get updates about marketing campaigns and promotions.</p>
                                            </div>

                                            <div class="notification-item">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="system_alerts" name="system_alerts" <?= $notificationSettings['system_alerts'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="system_alerts">
                                                        <span class="notification-title">System Alerts</span>
                                                    </label>
                                                </div>
                                                <p class="notification-description">Receive important system alerts and updates.</p>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Maintenance Settings -->
                        <div class="tab-pane fade" id="maintenance">
                            <div class="card fade-in-right">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-tools"></i> Maintenance</h5>
                                </div>
                                <div class="card-body">
                                    <div class="maintenance-options">
                                        <div class="maintenance-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="maintenance-title">Maintenance Mode</h6>
                                                    <p class="maintenance-description">Enable maintenance mode to temporarily disable the website for visitors.</p>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="maintenance_mode">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="maintenance-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="maintenance-title">Clear Cache</h6>
                                                    <p class="maintenance-description">Clear system cache to refresh data and improve performance.</p>
                                                </div>
                                                <button class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-trash me-2"></i> Clear Cache
                                                </button>
                                            </div>
                                        </div>

                                        <div class="maintenance-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="maintenance-title">Optimize Database</h6>
                                                    <p class="maintenance-description">Optimize database tables to improve performance.</p>
                                                </div>
                                                <button class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-database-check me-2"></i> Optimize
                                                </button>
                                            </div>
                                        </div>

                                        <div class="maintenance-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="maintenance-title">System Logs</h6>
                                                    <p class="maintenance-description">View and manage system logs for troubleshooting.</p>
                                                </div>
                                                <button class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-file-text me-2"></i> View Logs
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Backup & Restore -->
                        <div class="tab-pane fade" id="backup">
                            <div class="card fade-in-right">
                                <div class="card-header">
                                    <h5 class="card-title"><i class="bi bi-cloud-arrow-up"></i> Backup & Restore</h5>
                                </div>
                                <div class="card-body">
                                    <div class="backup-options">
                                        <div class="backup-item">
                                            <h6 class="backup-title">Create Backup</h6>
                                            <p class="backup-description">Create a backup of your database and settings.</p>
                                            <div class="d-flex gap-3 mt-3">
                                                <button class="btn btn-outline-primary">
                                                    <i class="bi bi-database me-2"></i> Database Only
                                                </button>
                                                <button class="btn btn-outline-primary">
                                                    <i class="bi bi-file-earmark me-2"></i> Files Only
                                                </button>
                                                <button class="btn btn-primary">
                                                    <i class="bi bi-cloud-arrow-up me-2"></i> Full Backup
                                                </button>
                                            </div>
                                        </div>

                                        <hr class="my-4">

                                        <div class="backup-item">
                                            <h6 class="backup-title">Restore Backup</h6>
                                            <p class="backup-description">Restore your system from a previous backup.</p>
                                            <div class="mb-3 mt-3">
                                                <input class="form-control" type="file" id="backupFile">
                                            </div>
                                            <button class="btn btn-primary">
                                                <i class="bi bi-cloud-arrow-down me-2"></i> Restore Backup
                                            </button>
                                        </div>

                                        <hr class="my-4">

                                        <div class="backup-item">
                                            <h6 class="backup-title">Scheduled Backups</h6>
                                            <p class="backup-description">Configure automatic scheduled backups.</p>
                                            <div class="form-check form-switch mb-3 mt-3">
                                                <input class="form-check-input" type="checkbox" id="scheduled_backups">
                                                <label class="form-check-label" for="scheduled_backups">
                                                    Enable Scheduled Backups
                                                </label>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="backup_frequency" class="form-label">Frequency</label>
                                                    <select class="form-select" id="backup_frequency">
                                                        <option value="daily">Daily</option>
                                                        <option value="weekly">Weekly</option>
                                                        <option value="monthly">Monthly</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label for="backup_time" class="form-label">Time</label>
                                                    <input type="time" class="form-control" id="backup_time" value="00:00">
                                                </div>
                                            </div>
                                            <button class="btn btn-primary">
                                                <i class="bi bi-save me-2"></i> Save Schedule
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-envelope-check text-primary me-2"></i>
                    Send Test Email
                </h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="testEmailAddress" class="form-label">Recipient Email</label>
                    <input type="email" class="form-control" id="testEmailAddress" placeholder="Enter email address">
                </div>
                <div class="mb-3">
                    <label for="testEmailSubject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="testEmailSubject" value="Test Email from Brew & Bake">
                </div>
                <div class="mb-3">
                    <label for="testEmailMessage" class="form-label">Message</label>
                    <textarea class="form-control" id="testEmailMessage" rows="3">This is a test email from your Brew & Bake admin panel. If you received this, your email settings are working correctly.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">
                    <i class="bi bi-send me-2"></i> Send Test
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const sidebarClose = document.querySelector('.sidebar-close');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.add('show');
            });
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', function() {
                sidebar.classList.remove('show');
            });
        }

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });

        // Test Email Modal
        const testEmailBtn = document.getElementById('testEmailBtn');
        if (testEmailBtn) {
            testEmailBtn.addEventListener('click', function() {
                const testEmailModal = new bootstrap.Modal(document.getElementById('testEmailModal'));
                testEmailModal.show();
            });
        }

        // Settings Navigation
        const settingsNavItems = document.querySelectorAll('.settings-nav-item');
        settingsNavItems.forEach(item => {
            item.addEventListener('click', function() {
                settingsNavItems.forEach(navItem => {
                    navItem.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    });
</script>
</body>
</html>