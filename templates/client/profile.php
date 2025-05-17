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

        // Get address data if table exists
        if ($tableExists) {
            $stmt = $conn->prepare("
                SELECT * FROM client_addresses
                WHERE client_id = ? AND is_default = 1
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $addressData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Add address and phone to user data if available
            if ($addressData) {
                $user['address'] = $addressData['address'];
                $user['phone'] = $addressData['phone'] ?? '';
                $user['city'] = $addressData['city'] ?? 'Manila';
                $user['province'] = $addressData['province'] ?? '';
                $user['postal_code'] = $addressData['postal_code'] ?? '1000';
                $user['country'] = $addressData['country'] ?? 'Philippines';
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

                // Update or create address record
                if ($tableExists) {
                    // Check if user has an address record
                    $stmt = $conn->prepare("
                        SELECT id FROM client_addresses
                        WHERE client_id = ? AND is_default = 1
                    ");
                    $stmt->execute([$userId]);
                    $addressId = $stmt->fetchColumn();

                    if ($addressId) {
                        // Check if fullname column exists in client_addresses table
                        $stmt = $conn->prepare("
                            SELECT COUNT(*)
                            FROM information_schema.columns
                            WHERE table_schema = DATABASE()
                            AND table_name = 'client_addresses'
                            AND column_name = 'fullname'
                        ");
                        $stmt->execute();
                        $fullnameColumnExists = $stmt->fetchColumn() > 0;

                        // Update existing address (normalized version without fullname)
                        $stmt = $conn->prepare("
                            UPDATE client_addresses
                            SET phone = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$phone, $addressId]);
                    } else {
                        // Check if fullname column exists in client_addresses table
                        $stmt = $conn->prepare("
                            SELECT COUNT(*)
                            FROM information_schema.columns
                            WHERE table_schema = DATABASE()
                            AND table_name = 'client_addresses'
                            AND column_name = 'fullname'
                        ");
                        $stmt->execute();
                        $fullnameColumnExists = $stmt->fetchColumn() > 0;

                        // Create new address record (normalized version without fullname)
                        $stmt = $conn->prepare("
                            INSERT INTO client_addresses
                            (client_id, address, city, postal_code, country, phone, is_default)
                            VALUES (?, ?, ?, ?, ?, ?, 1)
                        ");
                        $stmt->execute([
                            $userId,
                            $user['address'] ?? 'No address provided',
                            'Manila',
                            '1000',
                            'Philippines',
                            $phone
                        ]);
                    }
                } else {
                    // Create the client_addresses table if it doesn't exist (normalized version)
                    $stmt = $conn->prepare("
                        CREATE TABLE IF NOT EXISTS client_addresses (
                            id INT NOT NULL AUTO_INCREMENT,
                            client_id INT NOT NULL,
                            address TEXT NOT NULL,
                            city VARCHAR(100) NOT NULL DEFAULT 'Manila',
                            province VARCHAR(100) NULL,
                            postal_code VARCHAR(20) NOT NULL DEFAULT '1000',
                            country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
                            phone VARCHAR(20) NULL,
                            latitude VARCHAR(20) NULL DEFAULT '9.994295',
                            longitude VARCHAR(20) NULL DEFAULT '118.918419',
                            address_type VARCHAR(20) NOT NULL DEFAULT 'Home',
                            is_default BOOLEAN NOT NULL DEFAULT 1,
                            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (id),
                            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
                        )
                    ");
                    $stmt->execute();

                    // Insert new address record (normalized version without fullname)
                    $stmt = $conn->prepare("
                        INSERT INTO client_addresses
                        (client_id, address, phone, is_default)
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([
                        $userId,
                        'No address provided',
                        $phone
                    ]);
                }

                $successMessage = "Profile updated successfully!";

                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Get updated address data
                $stmt = $conn->prepare("
                    SELECT * FROM client_addresses
                    WHERE client_id = ? AND is_default = 1
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
                $addressData = $stmt->fetch(PDO::FETCH_ASSOC);

                // Update user data with address information
                if ($addressData) {
                    $user['address'] = $addressData['address'];
                    $user['phone'] = $addressData['phone'] ?? '';
                    $user['city'] = $addressData['city'] ?? 'Manila';
                    $user['province'] = $addressData['province'] ?? '';
                    $user['postal_code'] = $addressData['postal_code'] ?? '1000';
                    $user['country'] = $addressData['country'] ?? 'Philippines';
                }
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
        $errorMessage = "New password and confirm password do not match.";
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
                // Hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update password
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);

                $successMessage = "Password changed successfully!";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error changing password: " . $e->getMessage();
        }
    }
}

// Handle address save/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    $addressId = !empty($_POST['address_id']) ? $_POST['address_id'] : null;
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['province'] ?? ''); // Keep variable name for compatibility
    $postalCode = trim($_POST['postal_code']);
    $country = trim($_POST['country']);
    $modalPhone = trim($_POST['modal_phone'] ?? '');
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    $latitude = !empty($_POST['latitude']) ? $_POST['latitude'] : '9.994295';
    $longitude = !empty($_POST['longitude']) ? $_POST['longitude'] : '118.918419';

    // Format phone number for Philippines (add +63 prefix if not already present)
    if (!empty($modalPhone)) {
        // Remove any non-digit characters
        $modalPhone = preg_replace('/\D/', '', $modalPhone);

        // If the phone number starts with '0', remove it
        if (substr($modalPhone, 0, 1) === '0') {
            $modalPhone = substr($modalPhone, 1);
        }

        // If the phone number doesn't start with '+63', add it
        if (substr($modalPhone, 0, 3) !== '+63') {
            $modalPhone = '+63' . $modalPhone;
        }
    }

    // Validate inputs
    if (empty($address) || empty($city) || empty($postalCode) || empty($country)) {
        $errorMessage = "Address, city, postal code, and country are required fields.";
    } else {
        try {
            // Check if client_addresses table exists
            $stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_name = 'client_addresses'
            ");
            $stmt->execute();
            $tableExists = $stmt->fetchColumn() > 0;

            // Create the table if it doesn't exist
            if (!$tableExists) {
                $stmt = $conn->prepare("
                    CREATE TABLE IF NOT EXISTS client_addresses (
                        id INT NOT NULL AUTO_INCREMENT,
                        client_id INT NOT NULL,
                        address TEXT NOT NULL,
                        city VARCHAR(100) NOT NULL DEFAULT 'Manila',
                        province VARCHAR(100) NULL,
                        postal_code VARCHAR(20) NOT NULL DEFAULT '1000',
                        country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
                        phone VARCHAR(20) NULL,
                        latitude VARCHAR(20) NULL DEFAULT '9.994295',
                        longitude VARCHAR(20) NULL DEFAULT '118.918419',
                        address_type VARCHAR(20) NOT NULL DEFAULT 'Home',
                        is_default BOOLEAN NOT NULL DEFAULT 1,
                        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
                    )
                ");
                $stmt->execute();
            }

            // If this is set as default, update all other addresses to not be default
            if ($isDefault) {
                $stmt = $conn->prepare("
                    UPDATE client_addresses
                    SET is_default = 0
                    WHERE client_id = ?
                ");
                $stmt->execute([$userId]);
            }

            // Check if we're updating an existing address or creating a new one
            if ($addressId) {
                // Update existing address (normalized version without fullname)
                $stmt = $conn->prepare("
                    UPDATE client_addresses
                    SET address = ?, city = ?, province = ?, postal_code = ?, country = ?, phone = ?,
                        latitude = ?, longitude = ?, address_type = ?, is_default = ?
                    WHERE id = ? AND client_id = ?
                ");
                $stmt->execute([
                    $address, $city, $state, $postalCode, $country, $modalPhone,
                    $latitude, $longitude, 'Home', $isDefault,
                    $addressId, $userId
                ]);

                $successMessage = "Address updated successfully!";
            } else {
                // Insert new address (normalized version without fullname)
                $stmt = $conn->prepare("
                    INSERT INTO client_addresses
                    (client_id, address, city, province, postal_code, country, phone, latitude, longitude, address_type, is_default)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId, $address, $city, $state, $postalCode, $country, $modalPhone,
                    $latitude, $longitude, 'Home', $isDefault
                ]);

                $successMessage = "Address added successfully!";
            }

            // Refresh address data
            $stmt = $conn->prepare("
                SELECT * FROM client_addresses
                WHERE client_id = ? AND is_default = 1
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $addressData = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $errorMessage = "Error saving address: " . $e->getMessage();
        }
    }
}

// Handle messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
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
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link rel="stylesheet" href="../../assets/css/menu.css?v=<?= time() ?>">
    <style>
        /* Account settings specific styles */
        :root {
            --color-primary: #111827;
            --color-secondary: #f59e0b;
            --color-gray-100: #f3f4f6;
            --color-gray-200: #e5e7eb;
            --color-gray-300: #d1d5db;
            --color-gray-400: #9ca3af;
            --color-gray-500: #6b7280;
            --color-gray-600: #4b5563;
            --color-gray-700: #374151;
            --color-gray-800: #1f2937;
            --color-gray-900: #111827;
        }

        .account-section {
            padding: 3rem 0;
            background-color: var(--color-gray-100);
            min-height: calc(100vh - 200px);
        }

        .account-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .account-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-gray-900);
            margin-bottom: 0.5rem;
        }

        .account-subtitle {
            font-size: 1rem;
            color: var(--color-gray-600);
        }

        .account-card {
            background-color: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: none;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid var(--color-gray-200);
            padding: 1.25rem 1.5rem;
        }

        .card-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--color-gray-900);
            margin: 0;
            display: flex;
            align-items: center;
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
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid var(--color-gray-300);
            border-radius: 0.375rem;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: var(--color-secondary);
            box-shadow: 0 0 0 0.25rem rgba(245, 158, 11, 0.25);
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            color: #ffffff;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary:hover {
            background-color: var(--color-gray-800);
            border-color: var(--color-gray-800);
        }

        .btn-outline-primary {
            color: var(--color-primary);
            border-color: var(--color-primary);
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
        }

        .btn-outline-primary:hover {
            background-color: var(--color-primary);
            color: #ffffff;
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

        /* Map styles */
        #map-container {
            height: 400px;
            width: 100%;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border: 1px solid var(--color-gray-300);
        }

        .map-controls {
            margin-bottom: 1rem;
        }

        .address-card {
            border: 1px solid var(--color-gray-300);
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: var(--color-gray-50);
        }

        .address-card.active {
            border-color: var(--color-secondary);
            background-color: rgba(245, 158, 11, 0.05);
        }

        .address-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .address-card-title {
            font-weight: 600;
            margin: 0;
            font-size: 1rem;
        }

        .address-card-actions {
            display: flex;
            gap: 0.5rem;
        }

        .address-card-body {
            color: var(--color-gray-700);
            font-size: 0.875rem;
        }

        .address-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: var(--color-gray-500);
        }

        .modal-map-container {
            height: 400px;
            width: 100%;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            border: 1px solid var(--color-gray-300);
        }

        /* Style for Leaflet geocoder control */
        .leaflet-control-geocoder {
            border: none !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
            z-index: 1000 !important;
        }

        .leaflet-control-geocoder-icon {
            width: 36px !important;
            height: 36px !important;
            background-size: 20px !important;
            border-radius: 4px !important;
            background-color: white !important;
            box-shadow: 0 1px 5px rgba(0,0,0,0.2) !important;
        }

        .leaflet-control-geocoder-form {
            margin: 0 !important;
            padding: 0 !important;
        }

        .leaflet-control-geocoder-form input {
            font-family: 'Poppins', sans-serif !important;
            font-size: 14px !important;
            padding: 10px 12px !important;
            width: 300px !important;
            border: 1px solid var(--color-gray-300) !important;
            border-radius: 4px !important;
            box-shadow: 0 1px 5px rgba(0,0,0,0.1) !important;
            transition: all 0.2s ease !important;
        }

        .leaflet-control-geocoder-form input:focus {
            border-color: var(--color-secondary) !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25) !important;
            outline: none !important;
        }

        .leaflet-control-geocoder-form-no-error {
            color: var(--color-gray-600) !important;
            background-color: white !important;
            padding: 8px 12px !important;
            border-radius: 4px !important;
            margin-top: 4px !important;
            font-family: 'Poppins', sans-serif !important;
            font-size: 13px !important;
        }

        .leaflet-control-geocoder-alternatives {
            width: 300px !important;
            max-height: 300px !important;
            overflow-y: auto !important;
            background: white !important;
            border-radius: 4px !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2) !important;
            margin-top: 4px !important;
        }

        .leaflet-control-geocoder-alternatives li {
            padding: 10px 12px !important;
            border-bottom: 1px solid var(--color-gray-200) !important;
            font-family: 'Poppins', sans-serif !important;
            font-size: 13px !important;
            transition: background-color 0.2s ease !important;
        }

        .leaflet-control-geocoder-alternatives li:hover {
            background-color: var(--color-gray-100) !important;
            cursor: pointer !important;
        }

        .leaflet-control-geocoder-alternatives li:last-child {
            border-bottom: none !important;
        }

        /* Add a hint text below the map */
        .map-hint {
            font-size: 12px;
            color: var(--color-gray-600);
            margin-top: -15px;
            margin-bottom: 15px;
            text-align: center;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .account-title {
                font-size: 1.5rem;
            }

            .card-header h2 {
                font-size: 1.125rem;
            }

            #map-container,
            .modal-map-container {
                height: 250px;
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

            <!-- Alert Messages -->
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Personal Information -->
                <div class="col-lg-6 mb-4">
                    <div class="card account-card">
                        <div class="card-header">
                            <h2><i class="bi bi-person-circle"></i> Personal Information</h2>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="post">
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
                                    <div class="input-group">
                                        <span class="input-group-text">+63</span>
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            value="<?= htmlspecialchars(preg_replace('/^\+63/', '', $user['phone'] ?? '')) ?>"
                                            placeholder="9123456789">
                                    </div>
                                    <div class="form-text">Enter your 10-digit Philippine mobile number without the leading zero.</div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-2"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Password Management -->
                <div class="col-lg-6 mb-4">
                    <div class="card account-card">
                        <div class="card-header">
                            <h2><i class="bi bi-shield-lock"></i> Password Management</h2>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="post">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password"
                                            minlength="8" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Password must be at least 8 characters long.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                            minlength="8" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="bi bi-key me-2"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Address Management -->
                <div class="col-12 mb-4">
                    <div class="card account-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h2><i class="bi bi-geo-alt"></i> Address Management</h2>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#address-modal">
                                <i class="bi bi-plus-lg me-1"></i> Add New Address
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if (isset($addressData) && $addressData): ?>
                                <div class="address-card active">
                                    <div class="address-card-header">
                                        <h5 class="address-card-title">Default Address</h5>
                                        <div class="address-card-actions">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#address-modal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="address-card-body">
                                        <p class="mb-1"><?= htmlspecialchars($addressData['address']) ?></p>
                                        <p class="mb-1">
                                            <?= htmlspecialchars($addressData['city']) ?>,
                                            <?= !empty($addressData['province']) ? htmlspecialchars($addressData['province']) . ', ' : '' ?>
                                            <?= htmlspecialchars($addressData['postal_code']) ?>
                                        </p>
                                        <p class="mb-1"><?= htmlspecialchars($addressData['country']) ?></p>
                                        <?php if (!empty($addressData['phone'])): ?>
                                            <p class="mb-0"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($addressData['phone']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-geo-alt display-4 text-muted"></i>
                                    <p class="mt-3 mb-4">You haven't added any addresses yet.</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#address-modal">
                                        <i class="bi bi-plus-lg me-2"></i> Add New Address
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Address Modal -->
            <div class="modal fade" id="address-modal" tabindex="-1" aria-labelledby="address-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="address-modal-label">Manage Address</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="address-form" action="profile.php" method="post">
                                <input type="hidden" name="address_id" id="address_id" value="<?= $addressData['id'] ?? '' ?>">
                                <input type="hidden" name="latitude" id="latitude" value="<?= $addressData['latitude'] ?? '9.994295' ?>">
                                <input type="hidden" name="longitude" id="longitude" value="<?= $addressData['longitude'] ?? '118.918419' ?>">

                                <div class="mb-3 d-flex justify-content-end">
                                    <button class="btn btn-outline-secondary" type="button" id="get-current-location">
                                        <i class="bi bi-geo"></i> Use My Current Location
                                    </button>
                                </div>

                                <div id="modal-map-container" class="modal-map-container"></div>
                                <div class="map-hint">
                                    <i class="bi bi-info-circle me-1"></i> Search for a location using the search box in the map, or click anywhere on the map to set your address
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2" required><?= htmlspecialchars($addressData['address'] ?? '') ?></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" value="<?= htmlspecialchars($addressData['city'] ?? 'Manila') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="province" class="form-label">Province</label>
                                        <input type="text" class="form-control" id="province" name="province" value="<?= htmlspecialchars($addressData['province'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="postal_code" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?= htmlspecialchars($addressData['postal_code'] ?? '1000') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="country" class="form-label">Country</label>
                                        <input type="text" class="form-control" id="country" name="country" value="<?= htmlspecialchars($addressData['country'] ?? 'Philippines') ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">+63</span>
                                        <input type="tel" class="form-control" id="modal_phone" name="modal_phone"
                                            value="<?= htmlspecialchars(preg_replace('/^\+63/', '', $addressData['phone'] ?? '')) ?>"
                                            placeholder="9123456789">
                                    </div>
                                    <div class="form-text">Enter your 10-digit Philippine mobile number without the leading zero.</div>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" <?= (!isset($addressData) || $addressData['is_default'] == 1) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_default">
                                        Set as default address
                                    </label>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" form="address-form" name="save_address" class="btn btn-primary">Save Address</button>
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
                            <li><a href="#">Our Story</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Social Impact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Customer Service</h4>
                        <ul>
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Store Locator</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
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
    <script src="../../assets/js/user-menu.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
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

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Password visibility toggle
            const togglePasswordButtons = document.querySelectorAll('.toggle-password');
            togglePasswordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const passwordInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');

                    // Toggle password visibility
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                });
            });

            // Password validation
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');

            if (confirmPasswordInput && newPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    if (newPasswordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                });

                newPasswordInput.addEventListener('input', function() {
                    if (newPasswordInput.value !== confirmPasswordInput.value && confirmPasswordInput.value !== '') {
                        confirmPasswordInput.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                });
            }

            // Leaflet Map Integration
            let map = null;
            let marker = null;
            let geocoder = null;

            // Initialize map when the modal is shown
            const addressModal = document.getElementById('address-modal');
            if (addressModal) {
                addressModal.addEventListener('shown.bs.modal', function() {
                    initMap();
                });
            }

            function initMap() {
                // Check if map is already initialized
                if (map) {
                    map.remove();
                    map = null;
                }

                // Get coordinates from hidden inputs
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const lat = parseFloat(latInput.value) || 9.994295; // Default to Brew & Bake location
                const lng = parseFloat(lngInput.value) || 118.918419;

                // Initialize map
                map = L.map('modal-map-container').setView([lat, lng], 15);

                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                // Add marker
                marker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(map);

                // Update coordinates when marker is dragged
                marker.on('dragend', function(e) {
                    const position = marker.getLatLng();
                    latInput.value = position.lat.toFixed(6);
                    lngInput.value = position.lng.toFixed(6);
                    reverseGeocode(position.lat, position.lng);
                });

                // Update marker when map is clicked
                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    latInput.value = e.latlng.lat.toFixed(6);
                    lngInput.value = e.latlng.lng.toFixed(6);
                    reverseGeocode(e.latlng.lat, e.latlng.lng);
                });

                // Initialize geocoder with enhanced options
                geocoder = L.Control.geocoder({
                    defaultMarkGeocode: false,
                    position: 'topleft',
                    placeholder: 'Search for a location...',
                    errorMessage: 'No results found',
                    suggestMinLength: 3,
                    suggestTimeout: 250,
                    queryMinLength: 3,
                    geocoder: L.Control.Geocoder.nominatim({
                        geocodingQueryParams: {
                            countrycodes: 'ph', // Focus on Philippines
                            limit: 8,
                            addressdetails: 1,
                            namedetails: 1
                        }
                    }),
                    expand: 'click'
                }).addTo(map);

                // Automatically expand the geocoder control when the map loads
                setTimeout(function() {
                    const geocoderButton = document.querySelector('.leaflet-control-geocoder-icon');
                    if (geocoderButton) {
                        geocoderButton.click();
                    }
                }, 500);

                // Variable to store the current search polygon
                let searchPolygon = null;

                // Handle geocoding results
                geocoder.on('markgeocode', function(e) {
                    // Remove previous search polygon if exists
                    if (searchPolygon && map.hasLayer(searchPolygon)) {
                        map.removeLayer(searchPolygon);
                    }

                    // Create a new polygon for the search result
                    if (e.geocode.bbox) {
                        searchPolygon = L.polygon([
                            e.geocode.bbox.getSouthEast(),
                            e.geocode.bbox.getNorthEast(),
                            e.geocode.bbox.getNorthWest(),
                            e.geocode.bbox.getSouthWest()
                        ], {
                            color: '#3388ff',
                            weight: 2,
                            opacity: 0.5,
                            fillOpacity: 0.1
                        }).addTo(map);

                        // Fit map to bounds with some padding
                        map.fitBounds(searchPolygon.getBounds(), {
                            padding: [50, 50],
                            maxZoom: 16
                        });
                    }

                    // Update marker position
                    marker.setLatLng(e.geocode.center);

                    // Update coordinate inputs
                    latInput.value = e.geocode.center.lat.toFixed(6);
                    lngInput.value = e.geocode.center.lng.toFixed(6);

                    // Update address fields
                    updateAddressFields(e.geocode);

                    // Also perform reverse geocoding to get more detailed address information
                    reverseGeocode(e.geocode.center.lat, e.geocode.center.lng);
                });

                // No need for custom search input as we're using the Leaflet geocoder control

                // Get current location with improved feedback
                const getCurrentLocationBtn = document.getElementById('get-current-location');
                getCurrentLocationBtn.addEventListener('click', function() {
                    // Show loading state
                    const originalBtnText = getCurrentLocationBtn.innerHTML;
                    getCurrentLocationBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Getting location...';
                    getCurrentLocationBtn.disabled = true;

                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;

                                // Remove previous search polygon if exists
                                if (searchPolygon && map.hasLayer(searchPolygon)) {
                                    map.removeLayer(searchPolygon);
                                }

                                // Update marker position
                                marker.setLatLng([lat, lng]);

                                // Zoom to location with animation
                                map.flyTo([lat, lng], 16, {
                                    duration: 1.5
                                });

                                // Update coordinate inputs
                                latInput.value = lat.toFixed(6);
                                lngInput.value = lng.toFixed(6);

                                // Get address details
                                reverseGeocode(lat, lng);

                                // Add a small pulse animation to the marker
                                marker.bindPopup('Your current location').openPopup();

                                // Reset button
                                getCurrentLocationBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Location found';
                                setTimeout(() => {
                                    getCurrentLocationBtn.innerHTML = originalBtnText;
                                    getCurrentLocationBtn.disabled = false;
                                }, 2000);
                            },
                            function(error) {
                                let errorMessage = 'Unable to get your location.';
                                switch (error.code) {
                                    case error.PERMISSION_DENIED:
                                        errorMessage = 'Location access denied. Please enable location services in your browser settings.';
                                        break;
                                    case error.POSITION_UNAVAILABLE:
                                        errorMessage = 'Location information is unavailable. Please try again.';
                                        break;
                                    case error.TIMEOUT:
                                        errorMessage = 'Location request timed out. Please try again.';
                                        break;
                                }

                                // Show error in a more user-friendly way
                                const mapContainer = document.getElementById('modal-map-container');
                                const errorDiv = document.createElement('div');
                                errorDiv.className = 'alert alert-warning alert-dismissible fade show position-absolute';
                                errorDiv.style.top = '10px';
                                errorDiv.style.left = '10px';
                                errorDiv.style.right = '10px';
                                errorDiv.style.zIndex = '1000';
                                errorDiv.innerHTML = `
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i> ${errorMessage}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                `;
                                mapContainer.appendChild(errorDiv);

                                // Reset button
                                getCurrentLocationBtn.innerHTML = originalBtnText;
                                getCurrentLocationBtn.disabled = false;

                                // Auto-dismiss the alert after 5 seconds
                                setTimeout(() => {
                                    if (errorDiv.parentNode) {
                                        errorDiv.parentNode.removeChild(errorDiv);
                                    }
                                }, 5000);
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 10000,
                                maximumAge: 0
                            }
                        );
                    } else {
                        // Reset button
                        getCurrentLocationBtn.innerHTML = originalBtnText;
                        getCurrentLocationBtn.disabled = false;

                        // Show error
                        alert('Geolocation is not supported by your browser. Please search for your location manually.');
                    }
                });

                // Fix map display issues
                setTimeout(function() {
                    map.invalidateSize();
                }, 100);
            }

            function reverseGeocode(lat, lng) {
                // Show loading indicator if available
                const loadingIndicator = document.getElementById('search-loading');
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'block';
                }

                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&zoom=18`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Hide loading indicator
                        if (loadingIndicator) {
                            loadingIndicator.style.display = 'none';
                        }

                        if (data && data.address) {
                            const address = data.address;
                            let fullAddress = [];

                            // Build address string with more details
                            if (address.house_number) fullAddress.push(address.house_number);
                            if (address.road) fullAddress.push(address.road);
                            if (address.neighbourhood) fullAddress.push(address.neighbourhood);
                            if (address.suburb) fullAddress.push(address.suburb);
                            if (address.village) fullAddress.push(address.village);

                            // If no components were found, use the display name
                            if (fullAddress.length === 0 && data.display_name) {
                                const displayParts = data.display_name.split(',');
                                // Use the first 2-3 parts of the display name for the address
                                fullAddress = displayParts.slice(0, Math.min(3, displayParts.length));
                            }

                            // Set form fields
                            document.getElementById('address').value = fullAddress.join(', ');
                            document.getElementById('city').value = address.city || address.town || address.village || address.municipality || 'Manila';
                            document.getElementById('province').value = address.state || address.province || '';
                            document.getElementById('postal_code').value = address.postcode || '1000';
                            document.getElementById('country').value = address.country || 'Philippines';

                            // Determine address type based on the location data
                            let addressType = 'Home'; // Default

                            // Check for commercial/work locations
                            if (
                                address.amenity === 'office' ||
                                address.office ||
                                address.building === 'commercial' ||
                                address.shop ||
                                address.amenity === 'school' ||
                                address.amenity === 'university' ||
                                address.amenity === 'college' ||
                                address.amenity === 'restaurant' ||
                                address.amenity === 'cafe'
                            ) {
                                addressType = 'Work';
                            }

                            // Set the appropriate radio button
                            const radioHome = document.getElementById('address_type_home');
                            const radioWork = document.getElementById('address_type_work');
                            const radioOther = document.getElementById('address_type_other');

                            if (radioHome && radioWork && radioOther) {
                                if (addressType === 'Work') {
                                    radioWork.checked = true;
                                } else {
                                    radioHome.checked = true;
                                }
                            }
                        }
                    })
                    .catch(error => {
                        // Hide loading indicator
                        if (loadingIndicator) {
                            loadingIndicator.style.display = 'none';
                        }
                        console.error('Error during reverse geocoding:', error);
                    });
            }

            function updateAddressFields(geocodeResult) {
                if (geocodeResult && geocodeResult.properties) {
                    const props = geocodeResult.properties;

                    // Update address fields
                    document.getElementById('address').value = props.address || '';

                    // Try to extract city, state, postal code from the address
                    if (props.address) {
                        const addressParts = props.address.split(',');
                        if (addressParts.length >= 3) {
                            document.getElementById('city').value = addressParts[addressParts.length - 3].trim();
                            document.getElementById('province').value = addressParts[addressParts.length - 2].trim();

                            // Try to extract postal code
                            const postalMatch = props.address.match(/\b\d{4,5}\b/);
                            if (postalMatch) {
                                document.getElementById('postal_code').value = postalMatch[0];
                            }
                        }
                    }

                    // Set country
                    if (props.country) {
                        document.getElementById('country').value = props.country;
                    }

                    // Also perform reverse geocoding to get more detailed address information
                    if (geocodeResult.center) {
                        reverseGeocode(geocodeResult.center.lat, geocodeResult.center.lng);
                    }
                } else if (geocodeResult && geocodeResult.name) {
                    // Handle simple name-based results
                    document.getElementById('address').value = geocodeResult.name;

                    // Try to extract components from the name
                    const nameParts = geocodeResult.name.split(',');
                    if (nameParts.length >= 2) {
                        // Use the first part as the address
                        document.getElementById('address').value = nameParts[0].trim();

                        // Try to identify city and province
                        if (nameParts.length >= 3) {
                            document.getElementById('city').value = nameParts[1].trim();
                            document.getElementById('province').value = nameParts[2].trim();
                        }
                    }

                    // Also perform reverse geocoding to get more detailed address information
                    if (geocodeResult.center) {
                        reverseGeocode(geocodeResult.center.lat, geocodeResult.center.lng);
                    }
                }
            }
        });
    </script>
</body>
</html>