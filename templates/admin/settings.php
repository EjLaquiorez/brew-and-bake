<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Try to include the regular settings file
$use_fallback = false;
try {
    require_once "../includes/settings.php";
} catch (Exception $e) {
    // If there's an error, use the fallback settings
    $use_fallback = true;
    require_once "../includes/settings_fallback.php";
}

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

// Get settings from the database
$generalSettings = $settings->getCategory('general');
$emailSettings = $settings->getCategory('email');
$paymentSettings = $settings->getCategory('payment');
$orderSettings = $settings->getCategory('order');
$notificationSettings = $settings->getCategory('notification');
$socialSettings = $settings->getCategory('social');
$systemSettings = $settings->getCategory('system');

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $updated = false;

    switch ($action) {
        case 'update_general':
            // Update general settings
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    $updated = $settings->set('general', $key, $value) || $updated;
                }
            }

            if ($updated) {
                $successMessage = "General settings updated successfully.";
                // Refresh settings
                $generalSettings = $settings->getCategory('general');
            } else {
                $errorMessage = "Failed to update general settings.";
            }
            break;

        case 'update_email':
            // Update email settings
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    $updated = $settings->set('email', $key, $value) || $updated;
                }
            }

            if ($updated) {
                $successMessage = "Email settings updated successfully.";
                // Refresh settings
                $emailSettings = $settings->getCategory('email');
            } else {
                $errorMessage = "Failed to update email settings.";
            }
            break;

        case 'update_payment':
            // Update payment settings
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    // Handle payment_methods as JSON array
                    if ($key === 'payment_methods' && is_array($value)) {
                        $value = json_encode($value);
                    }
                    $updated = $settings->set('payment', $key, $value) || $updated;
                }
            }

            if ($updated) {
                $successMessage = "Payment settings updated successfully.";
                // Refresh settings
                $paymentSettings = $settings->getCategory('payment');
            } else {
                $errorMessage = "Failed to update payment settings.";
            }
            break;

        case 'update_order':
            // Update order settings
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    $updated = $settings->set('order', $key, $value) || $updated;
                }
            }

            if ($updated) {
                $successMessage = "Order settings updated successfully.";
                // Refresh settings
                $orderSettings = $settings->getCategory('order');
            } else {
                $errorMessage = "Failed to update order settings.";
            }
            break;

        case 'update_notifications':
            // Update notification settings
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    // Convert checkbox values to boolean
                    if ($value === 'on') {
                        $value = '1';
                    }
                    $updated = $settings->set('notification', $key, $value) || $updated;
                }
            }

            // Handle unchecked checkboxes (not included in $_POST)
            $checkboxes = [
                'order_notifications', 'inventory_alerts', 'customer_feedback',
                'marketing_updates', 'system_alerts'
            ];

            foreach ($checkboxes as $checkbox) {
                if (!isset($_POST[$checkbox])) {
                    $updated = $settings->set('notification', $checkbox, '0') || $updated;
                }
            }

            if ($updated) {
                $successMessage = "Notification settings updated successfully.";
                // Refresh settings
                $notificationSettings = $settings->getCategory('notification');
            } else {
                $errorMessage = "Failed to update notification settings.";
            }
            break;

        case 'update_social':
            // Update social media settings
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    $updated = $settings->set('social', $key, $value) || $updated;
                }
            }

            if ($updated) {
                $successMessage = "Social media settings updated successfully.";
                // Refresh settings
                $socialSettings = $settings->getCategory('social');
            } else {
                $errorMessage = "Failed to update social media settings.";
            }
            break;

        case 'update_system':
            // Update system settings
            foreach ($_POST as $key => $value) {
                if ($key !== 'action') {
                    // Convert checkbox values to boolean
                    if ($value === 'on') {
                        $value = '1';
                    }
                    $updated = $settings->set('system', $key, $value) || $updated;
                }
            }

            // Handle unchecked checkboxes (not included in $_POST)
            $checkboxes = [
                'maintenance_mode', 'cache_enabled', 'debug_mode'
            ];

            foreach ($checkboxes as $checkbox) {
                if (!isset($_POST[$checkbox])) {
                    $updated = $settings->set('system', $checkbox, '0') || $updated;
                }
            }

            if ($updated) {
                $successMessage = "System settings updated successfully.";
                // Clear cache if settings were updated
                $settings->clearCache();
                // Refresh settings
                $systemSettings = $settings->getCategory('system');
            } else {
                $errorMessage = "Failed to update system settings.";
            }
            break;

        case 'clear_cache':
            // Clear settings cache
            $settings->clearCache();
            $successMessage = "Settings cache cleared successfully.";
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
    <?php include 'includes/css-includes.php'; ?>
</head>
<body>
<!-- Admin Container -->
<div class="admin-container">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

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

            <!-- Include Page Header -->
            <?php include 'includes/page-header.php'; ?>

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
                                                <?php
                                                // Get available payment methods
                                                $availableMethods = ['Cash', 'GCash', 'Maya', 'Credit Card', 'Bank Transfer'];

                                                // Parse payment methods from JSON if needed
                                                $paymentMethodsArray = $paymentSettings['payment_methods'] ?? [];
                                                if (is_string($paymentMethodsArray)) {
                                                    $paymentMethodsArray = json_decode($paymentMethodsArray, true) ?: [];
                                                }

                                                foreach ($availableMethods as $method):
                                                ?>
                                                    <div class="form-check mb-2">
                                                        <input class="form-check-input" type="checkbox"
                                                               id="payment_<?= strtolower(str_replace(' ', '_', $method)) ?>"
                                                               name="payment_methods[]"
                                                               value="<?= $method ?>"
                                                               <?= in_array($method, $paymentMethodsArray) ? 'checked' : '' ?>>
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
                                                    <?php
                                                    $defaultPayment = $paymentSettings['default_payment'] ?? 'Cash';
                                                    foreach ($availableMethods as $method):
                                                    ?>
                                                        <option value="<?= $method ?>" <?= $defaultPayment === $method ? 'selected' : '' ?>><?= $method ?></option>
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
                                    <form action="settings.php" method="post">
                                        <input type="hidden" name="action" value="update_system">

                                        <div class="maintenance-options">
                                            <div class="maintenance-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="maintenance-title">Maintenance Mode</h6>
                                                        <p class="maintenance-description">Enable maintenance mode to temporarily disable the website for visitors.</p>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?= $systemSettings['maintenance_mode'] ? 'checked' : '' ?>>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="maintenance-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="maintenance-title">Cache Settings</h6>
                                                        <p class="maintenance-description">Enable caching to improve performance.</p>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="cache_enabled" name="cache_enabled" <?= $systemSettings['cache_enabled'] ? 'checked' : '' ?>>
                                                    </div>
                                                </div>

                                                <div class="mt-3 mb-4">
                                                    <label for="cache_duration" class="form-label">Cache Duration (seconds)</label>
                                                    <input type="number" class="form-control" id="cache_duration" name="cache_duration" value="<?= htmlspecialchars($systemSettings['cache_duration']) ?>" min="60" step="60">
                                                    <div class="form-text">Recommended: 3600 seconds (1 hour)</div>
                                                </div>
                                            </div>

                                            <div class="maintenance-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="maintenance-title">Debug Mode</h6>
                                                        <p class="maintenance-description">Enable debug mode to show detailed error messages.</p>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" <?= $systemSettings['debug_mode'] ? 'checked' : '' ?>>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="maintenance-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="maintenance-title">Pagination Limit</h6>
                                                        <p class="maintenance-description">Default number of items to show per page.</p>
                                                    </div>
                                                    <div style="width: 100px;">
                                                        <input type="number" class="form-control" id="pagination_limit" name="pagination_limit" value="<?= htmlspecialchars($systemSettings['pagination_limit']) ?>" min="5" max="100" step="5">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-end mt-4">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save me-2"></i> Save System Settings
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <hr class="my-4">

                                    <div class="maintenance-options">
                                        <div class="maintenance-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="maintenance-title">Clear Cache</h6>
                                                    <p class="maintenance-description">Clear system cache to refresh data and improve performance.</p>
                                                </div>
                                                <form action="settings.php" method="post">
                                                    <input type="hidden" name="action" value="clear_cache">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm" id="clearCacheBtn">
                                                        <i class="bi bi-trash me-2"></i> Clear Cache
                                                    </button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="maintenance-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="maintenance-title">Optimize Database</h6>
                                                    <p class="maintenance-description">Optimize database tables to improve performance.</p>
                                                </div>
                                                <button class="btn btn-outline-primary btn-sm" id="optimizeDatabaseBtn">
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
                                                <button type="button" class="btn btn-outline-primary backup-btn" data-backup-type="Database">
                                                    <i class="bi bi-database me-2"></i> Database Only
                                                </button>
                                                <button type="button" class="btn btn-outline-primary backup-btn" data-backup-type="Files">
                                                    <i class="bi bi-file-earmark me-2"></i> Files Only
                                                </button>
                                                <button type="button" class="btn btn-primary backup-btn" data-backup-type="Full">
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
                                            <button type="button" class="btn btn-primary" id="restoreBackupBtn">
                                                <i class="bi bi-cloud-arrow-down me-2"></i> Restore Backup
                                            </button>
                                        </div>

                                        <hr class="my-4">

                                        <div class="backup-item">
                                            <h6 class="backup-title">Scheduled Backups</h6>
                                            <p class="backup-description">Configure automatic scheduled backups.</p>
                                            <form action="settings.php" method="post">
                                                <input type="hidden" name="action" value="update_system">

                                                <div class="form-check form-switch mb-3 mt-3">
                                                    <input class="form-check-input" type="checkbox" id="scheduled_backups" name="scheduled_backups" <?= isset($systemSettings['scheduled_backups']) && $systemSettings['scheduled_backups'] ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="scheduled_backups">
                                                        Enable Scheduled Backups
                                                    </label>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="backup_frequency" class="form-label">Frequency</label>
                                                        <select class="form-select" id="backup_frequency" name="backup_frequency">
                                                            <option value="daily" <?= isset($systemSettings['backup_frequency']) && $systemSettings['backup_frequency'] === 'daily' ? 'selected' : '' ?>>Daily</option>
                                                            <option value="weekly" <?= isset($systemSettings['backup_frequency']) && $systemSettings['backup_frequency'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                                                            <option value="monthly" <?= isset($systemSettings['backup_frequency']) && $systemSettings['backup_frequency'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="backup_time" class="form-label">Time</label>
                                                        <input type="time" class="form-control" id="backup_time" name="backup_time" value="<?= isset($systemSettings['backup_time']) ? htmlspecialchars($systemSettings['backup_time']) : '00:00' ?>">
                                                    </div>
                                                </div>
                                                <button type="submit" class="btn btn-primary" id="saveScheduleBtn">
                                                    <i class="bi bi-save me-2"></i> Save Schedule
                                                </button>
                                            </form>
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

<?php include 'includes/footer-scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {

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