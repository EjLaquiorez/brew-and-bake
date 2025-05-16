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
$userId = getCurrentUserId();
if ($userId) {
    try {
        // Get user data
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if client_addresses table exists
        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = 'client_addresses'
        ");
        $stmt->execute();
        $tableExists = $stmt->fetchColumn() > 0;

        if ($tableExists) {
            try {
                // Try to get address data with is_default column
                $stmt = $conn->prepare("
                    SELECT * FROM client_addresses
                    WHERE client_id = ? AND is_default = 1
                ");
                $stmt->execute([$userId]);
                $addressData = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // If is_default column doesn't exist, get the first address for the client
                if (strpos($e->getMessage(), "Unknown column 'is_default'") !== false) {
                    $stmt = $conn->prepare("
                        SELECT * FROM client_addresses
                        WHERE client_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$userId]);
                    $addressData = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    throw $e; // Re-throw if it's a different error
                }
            }

            // Add address and phone to user data if available
            if ($addressData) {
                $user['address'] = $addressData['address'];
                $user['phone'] = $addressData['phone'] ?? '';
            }
        }
    } catch (PDOException $e) {
        $errorMessage = "Error fetching user data: " . $e->getMessage();
        $user = [];
    }
} else {
    // Handle case where user ID is not available
    $errorMessage = "User information not available. Please log in again.";
    $user = [];
    // Redirect to login page
    $_SESSION['error'] = $errorMessage;
    header("Location: ../../index.php");
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Format phone number for Philippines (add +63 prefix if not already present)
    if (!empty($phone)) {
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // If the phone number starts with '0', remove it
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }

        // If the phone number doesn't start with '+63', add it
        if (substr($phone, 0, 3) !== '+63') {
            $phone = '+63' . $phone;
        }
    }

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
                    SET name = ?, email = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $userId]);

                // Check if client_addresses table exists
                $stmt = $conn->prepare("
                    SELECT COUNT(*)
                    FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                    AND table_name = 'client_addresses'
                ");
                $stmt->execute();
                $tableExists = $stmt->fetchColumn() > 0;

                if (!$tableExists) {
                    // Create the client_addresses table if it doesn't exist
                    $stmt = $conn->prepare("
                        CREATE TABLE IF NOT EXISTS client_addresses (
                            id INT NOT NULL AUTO_INCREMENT,
                            client_id INT NOT NULL,
                            address TEXT NOT NULL,
                            city VARCHAR(100) NOT NULL DEFAULT 'Manila',
                            state VARCHAR(100) NULL,
                            postal_code VARCHAR(20) NOT NULL DEFAULT '1000',
                            country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
                            phone VARCHAR(20) NULL,
                            is_default BOOLEAN NOT NULL DEFAULT 1,
                            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (id),
                            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
                        )
                    ");
                    $stmt->execute();
                }

                try {
                    // Check if client has an address record with is_default column
                    $stmt = $conn->prepare("SELECT id FROM client_addresses WHERE client_id = ? AND is_default = 1");
                    $stmt->execute([$userId]);
                    $addressExists = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($addressExists) {
                        // Update existing address with is_default column
                        $stmt = $conn->prepare("
                            UPDATE client_addresses
                            SET address = ?, phone = ?
                            WHERE client_id = ? AND is_default = 1
                        ");
                        $stmt->execute([$address, $phone, $userId]);
                    } else {
                        // Create new address record with is_default column
                        $stmt = $conn->prepare("
                            INSERT INTO client_addresses
                            (client_id, address, city, state, postal_code, country, phone, is_default)
                            VALUES (?, ?, 'Manila', NULL, '1000', 'Philippines', ?, 1)
                        ");
                        $stmt->execute([$userId, $address, $phone]);
                    }
                } catch (PDOException $e) {
                    // If is_default column doesn't exist
                    if (strpos($e->getMessage(), "Unknown column 'is_default'") !== false) {
                        // Check if the is_default column exists
                        $stmt = $conn->prepare("
                            SELECT COUNT(*)
                            FROM information_schema.columns
                            WHERE table_schema = DATABASE()
                            AND table_name = 'client_addresses'
                            AND column_name = 'is_default'
                        ");
                        $stmt->execute();
                        $isDefaultExists = $stmt->fetchColumn() > 0;

                        if (!$isDefaultExists) {
                            // Add is_default column if it doesn't exist
                            $stmt = $conn->prepare("
                                ALTER TABLE client_addresses
                                ADD COLUMN is_default BOOLEAN NOT NULL DEFAULT 1 AFTER phone
                            ");
                            $stmt->execute();
                        }

                        // Check if client has any address record
                        $stmt = $conn->prepare("SELECT id FROM client_addresses WHERE client_id = ? LIMIT 1");
                        $stmt->execute([$userId]);
                        $addressExists = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($addressExists) {
                            // Update existing address without is_default condition
                            $stmt = $conn->prepare("
                                UPDATE client_addresses
                                SET address = ?, phone = ?, is_default = 1
                                WHERE client_id = ? AND id = ?
                            ");
                            $stmt->execute([$address, $phone, $userId, $addressExists['id']]);
                        } else {
                            // Create new address record with is_default
                            $stmt = $conn->prepare("
                                INSERT INTO client_addresses
                                (client_id, address, city, state, postal_code, country, phone, is_default)
                                VALUES (?, ?, 'Manila', NULL, '1000', 'Philippines', ?, 1)
                            ");
                            $stmt->execute([$userId, $address, $phone]);
                        }
                    } else {
                        throw $e; // Re-throw if it's a different error
                    }
                }

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

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid var(--color-gray-300);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 0.25rem rgba(17, 24, 39, 0.1);
        }

        .form-text {
            color: var(--color-gray-600);
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: #f59e0b;
            border-color: #f59e0b;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            border-radius: 8px;
            color: #111827;
        }

        .btn-primary:hover {
            background-color: #d97706;
            border-color: #d97706;
            color: #111827;
        }

        .cart-dropdown .btn-primary {
            background-color: #f59e0b;
            border-color: #f59e0b;
            color: #111827;
        }

        .cart-dropdown .btn-primary:hover {
            background-color: #d97706;
            border-color: #d97706;
        }

        .cart-dropdown .text-muted {
            color: #94a3b8 !important;
        }

        .btn-outline-secondary {
            color: var(--color-gray-700);
            border-color: var(--color-gray-300);
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            border-radius: 8px;
        }

        .btn-outline-secondary:hover {
            background-color: var(--color-gray-100);
            color: var(--color-gray-900);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 0.5rem;
        }

        .password-strength .progress {
            height: 4px;
            margin-bottom: 0.25rem;
            background-color: #f0f0f0;
        }

        .password-strength .form-text {
            font-size: 0.75rem;
            color: #6c757d;
        }

        /* Form validation */
        .is-valid {
            border-color: #28a745 !important;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        /* Profile picture section */
        .profile-picture {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .profile-picture-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--color-white);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 1rem;
        }

        /* Address modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            overflow: auto;
        }

        .modal.show {
            display: block;
        }

        .modal-dialog {
            margin: 1.75rem auto;
            max-width: 500px;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            outline: 0;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-body {
            padding: 1rem;
        }

        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .btn-close {
            background: transparent;
            border: 0;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: #000;
            opacity: 0.5;
            cursor: pointer;
        }

        .region-tab.active {
            color: #f59e0b;
            font-weight: 600;
            border-bottom: 2px solid #f59e0b;
        }

        .list-group-item.region-item:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        body.modal-open {
            overflow: hidden;
        }

        .profile-picture-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--color-gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--color-gray-500);
            margin: 0 auto 1rem;
        }

        /* Header and menu navigation styling */
        .site-header {
            background-color: #111827;
            position: relative;
            z-index: 49; /* Lower than menu-nav */
            padding: 0.75rem 0;
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo a {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }

        .logo a i {
            margin-right: 0.5rem;
            font-size: 1.75rem;
        }

        /* Main navigation styles removed */

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-menu {
            position: relative;
        }

        .user-icon {
            color: #ffffff;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .menu-nav {
            position: sticky;
            top: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
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
            font-weight: 500;
            color: #4b5563;
            padding: 1rem 1.5rem;
            display: block;
            position: relative;
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .menu-tabs a:hover {
            color: #111827;
        }

        .menu-tabs a.active {
            color: #111827;
            font-weight: 600;
        }

        .menu-tabs a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #f59e0b;
            border-radius: 2px 2px 0 0;
        }

        /* User dropdown styling */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #1e293b;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            width: 200px;
            padding: 0.5rem 0;
            display: none;
            z-index: 100; /* Higher than menu-nav but lower than cart-dropdown */
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
            color: #f8fafc;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .user-dropdown li a:hover,
        .user-dropdown li a.active {
            background-color: #334155;
            color: #ffffff;
        }

        /* Cart styling */
        .cart-menu {
            position: relative;
        }

        .cart-icon {
            position: relative;
            font-size: 1.25rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #f59e0b;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background-color: #1e293b;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 0;
            z-index: 150; /* Higher than menu-nav and user-dropdown */
            display: none;
            overflow: hidden;
            margin-top: 10px;
        }

        .cart-dropdown.show {
            display: block;
        }

        .cart-dropdown-header {
            padding: 12px 15px;
            border-bottom: 1px solid #334155;
            background-color: #1e293b;
        }

        .cart-dropdown-header h6 {
            margin: 0;
            font-weight: 600;
            font-size: 0.9rem;
            color: #f8fafc;
        }

        .cart-dropdown-items {
            max-height: 300px;
            overflow-y: auto;
            padding: 0;
            background-color: #1e293b;
        }

        .cart-dropdown-loading {
            padding: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .cart-dropdown-item {
            padding: 12px 15px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-dropdown-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            background-color: #334155;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2px;
            color: #f8fafc;
        }

        .cart-item-price {
            font-size: 0.85rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cart-item-quantity {
            background-color: #334155;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #f8fafc;
        }

        .cart-dropdown-footer {
            padding: 12px 15px;
            border-top: 1px solid #334155;
            background-color: #1e293b;
        }

        .cart-dropdown-link {
            color: #f8fafc;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .cart-dropdown-link:hover {
            color: #f59e0b;
            text-decoration: underline;
        }

        .cart-empty {
            padding: 30px 15px;
            text-align: center;
            color: #94a3b8;
        }

        .cart-empty i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #64748b;
        }

        .cart-empty p {
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .account-title {
                font-size: 1.5rem;
            }

            .card-header h2 {
                font-size: 1.125rem;
            }

            .cart-dropdown {
                width: 300px;
                right: -50px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include_once "../includes/header.php"; ?>

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



    <!-- Main Content -->
    <main class="account-section">
        <div class="container">
            <!-- Page Header -->
            <div class="account-header">
                <h1 class="account-title">Account Settings</h1>
                <p class="account-subtitle">Manage your profile and security settings</p>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <!-- Profile Picture Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-person-circle"></i> Profile Picture</h2>
                        </div>
                        <div class="card-body">
                            <div class="profile-picture">
                                <?php if (!empty($user['profile_picture'])): ?>
                                    <img src="../../assets/images/users/<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile Picture" class="profile-picture-img">
                                <?php else: ?>
                                    <div class="profile-picture-placeholder">
                                        <i class="bi bi-person"></i>
                                    </div>
                                <?php endif; ?>
                                <p class="text-muted mb-3">Upload a new profile picture</p>
                                <form method="POST" enctype="multipart/form-data" action="upload_profile_picture.php">
                                    <div class="mb-3">
                                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                    </div>
                                    <button type="submit" class="btn btn-primary" name="upload_picture">
                                        <i class="bi bi-upload me-2"></i> Upload Picture
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <!-- Profile Information Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2><i class="bi bi-person-vcard"></i> Profile Information</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">+63</span>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                               value="<?= htmlspecialchars(preg_replace('/^\+63/', '', $user['phone'] ?? '')) ?>"
                                               placeholder="9XX XXX XXXX"
                                               pattern="[9][0-9]{9}"
                                               maxlength="10">
                                    </div>
                                    <div class="form-text">Optional: Add your Philippine mobile number (e.g., 9XXXXXXXXX) for delivery updates</div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Delivery Address</label>
                                    <div class="card border p-3 mb-2">
                                        <div id="delivery-address-display">
                                            <?php if (!empty($user['address'])): ?>
                                                <?php
                                                // Get address data
                                                $stmt = $conn->prepare("
                                                    SELECT * FROM client_addresses
                                                    WHERE client_id = ? AND is_default = 1
                                                ");
                                                $stmt->execute([$userId]);
                                                $addressData = $stmt->fetch(PDO::FETCH_ASSOC);

                                                if ($addressData):
                                                    $fullName = $addressData['full_name'] ?? $user['name'];
                                                    $phone = $addressData['phone'] ?? '';
                                                    $region = $addressData['region'] ?? '';
                                                    $province = $addressData['province'] ?? '';
                                                    $city = $addressData['city'] ?? '';
                                                    $barangay = $addressData['barangay'] ?? '';
                                                    $addressType = $addressData['address_type'] ?? 'Home';

                                                    $location = '';
                                                    if ($region) $location .= $region;
                                                    if ($province) $location .= $province ? ', ' . $province : '';
                                                    if ($city) $location .= $city ? ', ' . $city : '';
                                                    if ($barangay) $location .= $barangay ? ', ' . $barangay : '';

                                                    if (empty($location)) {
                                                        $location = $addressData['address'] ?? '';
                                                    }
                                                ?>
                                                    <strong><?= htmlspecialchars($fullName) ?></strong><br>
                                                    <?= htmlspecialchars($phone) ?><br>
                                                    <?= htmlspecialchars($addressData['street_address'] ?? '') ?><br>
                                                    <?= htmlspecialchars($location) ?><br>
                                                    <?= htmlspecialchars($addressData['postal_code'] ?? '') ?><br>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($addressType) ?></span>
                                                <?php else: ?>
                                                    <p class="text-muted mb-0">
                                                        <?= htmlspecialchars($user['address']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">No delivery address added yet.</p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="edit-address-btn">
                                                <i class="bi bi-pencil-square me-1"></i> Edit Address
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-text">Your delivery address will be used for faster checkout</div>

                                    <!-- Hidden address field for backward compatibility -->
                                    <input type="hidden" id="address" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary" name="update_profile">
                                        <i class="bi bi-check-circle me-2"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Security Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-shield-lock"></i> Security</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="passwordForm">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <div class="password-strength">
                                        <div class="progress">
                                            <div class="progress-bar" id="password-strength-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small class="form-text" id="password-strength-text">Password strength: Too weak</small>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary" name="change_password">
                                        <i class="bi bi-key me-2"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>


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

    <!-- Address Modal -->
    <div class="modal" id="address-modal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Address</h5>
                    <button type="button" class="btn-close" id="close-address-modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="alert-container"></div>

                    <form id="address-form">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   value="<?= htmlspecialchars($addressData['full_name'] ?? $user['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text">+63</span>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?= htmlspecialchars(preg_replace('/^\+63/', '', $addressData['phone'] ?? $user['phone'] ?? '')) ?>"
                                       placeholder="9XX XXX XXXX"
                                       pattern="[9][0-9]{9}"
                                       maxlength="10" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code"
                                   value="<?= htmlspecialchars($addressData['postal_code'] ?? '') ?>"
                                   placeholder="e.g., 5300" required>
                        </div>

                        <div class="mb-3">
                            <label for="street_address" class="form-label">Street Name, Building, House No.</label>
                            <input type="text" class="form-control" id="street_address" name="street_address"
                                   value="<?= htmlspecialchars($addressData['street_address'] ?? '') ?>"
                                   placeholder="e.g., Visapa Homes, Bgy. Irawan PPC" required>
                        </div>

                        <div class="mb-3">
                            <label for="location_display" class="form-label">Region, Province, City, Barangay</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="location-display" name="location"
                                       value="<?php
                                           $location = '';
                                           if (!empty($addressData)) {
                                               if (!empty($addressData['region'])) $location .= $addressData['region'];
                                               if (!empty($addressData['province'])) $location .= !empty($location) ? ', ' . $addressData['province'] : $addressData['province'];
                                               if (!empty($addressData['city'])) $location .= !empty($location) ? ', ' . $addressData['city'] : $addressData['city'];
                                               if (!empty($addressData['barangay'])) $location .= !empty($location) ? ', ' . $addressData['barangay'] : $addressData['barangay'];
                                           }
                                           echo htmlspecialchars($location);
                                       ?>"
                                       readonly required>
                                <button class="btn btn-outline-secondary" type="button" id="clear-location">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>

                            <!-- Hidden inputs for region, province, city, barangay -->
                            <input type="hidden" id="selected-region" name="selected_region"
                                   value="<?= htmlspecialchars($addressData['region'] ?? '') ?>">
                            <input type="hidden" id="selected-province" name="selected_province"
                                   value="<?= htmlspecialchars($addressData['province'] ?? '') ?>">
                            <input type="hidden" id="selected-city" name="selected_city"
                                   value="<?= htmlspecialchars($addressData['city'] ?? '') ?>">
                            <input type="hidden" id="selected-barangay" name="selected_barangay"
                                   value="<?= htmlspecialchars($addressData['barangay'] ?? '') ?>">

                            <!-- Location selector -->
                            <div id="location-selector" class="card mt-2" style="display: none;">
                                <div class="card-header p-0">
                                    <ul class="nav nav-tabs card-header-tabs">
                                        <li class="nav-item">
                                            <a class="nav-link region-tab active" href="#" data-tab="region-content">Region</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link region-tab" href="#" data-tab="province-content">Province</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link region-tab" href="#" data-tab="city-content">City</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link region-tab" href="#" data-tab="barangay-content">Barangay</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body p-0">
                                    <div id="region-content" class="tab-content p-3" style="max-height: 200px; overflow-y: auto;">
                                        <div class="list-group list-group-flush">
                                            <a href="#" class="list-group-item list-group-item-action region-item">Metro Manila</a>
                                            <a href="#" class="list-group-item list-group-item-action region-item">Mindanao</a>
                                            <a href="#" class="list-group-item list-group-item-action region-item">North Luzon</a>
                                            <a href="#" class="list-group-item list-group-item-action region-item">South Luzon</a>
                                            <a href="#" class="list-group-item list-group-item-action region-item">Visayas</a>
                                        </div>
                                    </div>
                                    <div id="province-content" class="tab-content p-3" style="display: none; max-height: 200px; overflow-y: auto;">
                                        <!-- Provinces will be loaded dynamically -->
                                        <div class="text-center py-3">
                                            <p>Please select a region first</p>
                                        </div>
                                    </div>
                                    <div id="city-content" class="tab-content p-3" style="display: none; max-height: 200px; overflow-y: auto;">
                                        <!-- Cities will be loaded dynamically -->
                                        <div class="text-center py-3">
                                            <p>Please select a province first</p>
                                        </div>
                                    </div>
                                    <div id="barangay-content" class="tab-content p-3" style="display: none; max-height: 200px; overflow-y: auto;">
                                        <!-- Barangays will be loaded dynamically -->
                                        <div class="text-center py-3">
                                            <p>Please select a city first</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Map display -->
                        <div class="mb-3">
                            <label class="form-label">Location on Map</label>
                            <div id="map-container" class="border rounded position-relative" style="height: 250px; background-color: #f8f9fa;">
                                <div id="map-canvas" style="height: 100%; width: 100%;"></div>
                                <div id="map-error" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center bg-white" style="display: none; z-index: 200;">
                                    <div class="text-danger mb-2"><i class="bi bi-exclamation-triangle-fill" style="font-size: 2rem;"></i></div>
                                    <p class="text-center px-3" id="map-error-message">Unable to load map. Please check your connection.</p>
                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="retry-map-load">
                                        <i class="bi bi-arrow-clockwise me-1"></i> Retry
                                    </button>
                                </div>
                                <div id="map-loading" class="position-absolute top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center bg-white" style="z-index: 100;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <div class="position-absolute bottom-0 end-0 m-2" style="z-index: 50;">
                                    <button type="button" class="btn btn-primary shadow-sm" id="use-current-location">
                                        <i class="bi bi-geo-alt-fill"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="form-text">Tap on the map to set your location</div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enable-precise-location" checked>
                                    <label class="form-check-label small" for="enable-precise-location">Precise location</label>
                                </div>
                            </div>

                            <!-- Hidden inputs for latitude and longitude -->
                            <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars($addressData['latitude'] ?? '') ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars($addressData['longitude'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Label As:</label>
                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="address_type" id="home" value="Home"
                                       <?= ($addressData['address_type'] ?? 'Home') === 'Home' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="home">Home</label>

                                <input type="radio" class="btn-check" name="address_type" id="work" value="Work"
                                       <?= ($addressData['address_type'] ?? '') === 'Work' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary" for="work">Work</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" checked>
                                <label class="form-check-label" for="is_default">
                                    Set as Default Address
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" id="cancel-address-btn">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <?php
    $root_path = '../../';
    include_once "../../templates/includes/footer-scripts.php";
    ?>
    <!-- Google Maps JavaScript API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&libraries=places"></script>
    <script src="../../assets/js/address-modal.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            // Password strength checker
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrengthBar = document.getElementById('password-strength-bar');
            const passwordStrengthText = document.getElementById('password-strength-text');
            const passwordForm = document.getElementById('passwordForm');

            if (newPasswordInput && passwordStrengthBar && passwordStrengthText) {
                newPasswordInput.addEventListener('input', function() {
                    const password = this.value;
                    const strength = checkPasswordStrength(password);

                    // Update progress bar
                    passwordStrengthBar.style.width = strength.score + '%';
                    passwordStrengthBar.setAttribute('aria-valuenow', strength.score);

                    // Update color
                    passwordStrengthBar.className = 'progress-bar';
                    if (strength.score < 25) {
                        passwordStrengthBar.classList.add('bg-danger');
                    } else if (strength.score < 50) {
                        passwordStrengthBar.classList.add('bg-warning');
                    } else if (strength.score < 75) {
                        passwordStrengthBar.classList.add('bg-info');
                    } else {
                        passwordStrengthBar.classList.add('bg-success');
                    }

                    // Update text
                    passwordStrengthText.textContent = 'Password strength: ' + strength.message;
                });
            }

            // Password confirmation validation
            if (confirmPasswordInput && newPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    if (this.value === newPasswordInput.value) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                });
            }

            // Password form validation
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const currentPassword = document.getElementById('current_password').value;
                    const newPassword = newPasswordInput.value;
                    const confirmPassword = confirmPasswordInput.value;

                    // Basic validation
                    if (!currentPassword || !newPassword || !confirmPassword) {
                        e.preventDefault();
                        alert('All password fields are required.');
                        return false;
                    }

                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('New passwords do not match.');
                        return false;
                    }

                    if (newPassword.length < 8) {
                        e.preventDefault();
                        alert('New password must be at least 8 characters long.');
                        return false;
                    }

                    const strength = checkPasswordStrength(newPassword);
                    if (strength.score < 40) {
                        if (!confirm('Your password is weak. Are you sure you want to use it?')) {
                            e.preventDefault();
                            return false;
                        }
                    }

                    return true;
                });
            }

            // Function to check password strength
            function checkPasswordStrength(password) {
                // Initialize score
                let score = 0;
                let message = 'Too weak';

                // No password, no score
                if (!password) {
                    return { score: 0, message: 'Too weak' };
                }

                // Award points for length
                if (password.length >= 8) {
                    score += 20;
                }
                if (password.length >= 12) {
                    score += 10;
                }

                // Award points for complexity
                if (/[a-z]/.test(password)) {
                    score += 10; // Has lowercase
                }
                if (/[A-Z]/.test(password)) {
                    score += 15; // Has uppercase
                }
                if (/[0-9]/.test(password)) {
                    score += 15; // Has number
                }
                if (/[^a-zA-Z0-9]/.test(password)) {
                    score += 20; // Has special character
                }

                // Determine message based on score
                if (score >= 80) {
                    message = 'Very strong';
                } else if (score >= 60) {
                    message = 'Strong';
                } else if (score >= 40) {
                    message = 'Moderate';
                } else if (score >= 20) {
                    message = 'Weak';
                }

                return { score, message };
            }
        });
    </script>
</body>
</html>
