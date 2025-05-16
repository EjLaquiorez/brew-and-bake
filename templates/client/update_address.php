<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to update your address.'
    ]);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Get user ID
$userId = getCurrentUserId();
if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'User information not available. Please log in again.'
    ]);
    exit;
}

// Get form data
$fullName = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$location = trim($_POST['location'] ?? '');
$region = trim($_POST['selected_region'] ?? '');
$province = trim($_POST['selected_province'] ?? '');
$city = trim($_POST['selected_city'] ?? '');
$barangay = trim($_POST['selected_barangay'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$streetAddress = trim($_POST['street_address'] ?? '');
$latitude = trim($_POST['latitude'] ?? '');
$longitude = trim($_POST['longitude'] ?? '');
$addressType = trim($_POST['address_type'] ?? 'Home');
$isDefault = isset($_POST['is_default']) ? 1 : 0;

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
if (empty($fullName) || empty($phone) || empty($location) || empty($postalCode) || empty($streetAddress)) {
    echo json_encode([
        'success' => false,
        'message' => 'Full name, phone number, location, postal code, and street address are required fields.'
    ]);
    exit;
}

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

    if (!$tableExists) {
        // Create the client_addresses table if it doesn't exist
        $stmt = $conn->prepare("
            CREATE TABLE IF NOT EXISTS client_addresses (
                id INT NOT NULL AUTO_INCREMENT,
                client_id INT NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NULL,
                postal_code VARCHAR(20) NOT NULL,
                street_address TEXT NOT NULL,
                region VARCHAR(100) NOT NULL,
                province VARCHAR(100) NOT NULL,
                city VARCHAR(100) NOT NULL,
                barangay VARCHAR(100) NOT NULL,
                latitude DECIMAL(10,8) NULL,
                longitude DECIMAL(11,8) NULL,
                address_type VARCHAR(20) NOT NULL DEFAULT 'Home',
                is_default BOOLEAN NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $stmt->execute();
    } else {
        // Check if the table has the new columns
        $requiredColumns = ['full_name', 'postal_code', 'street_address', 'region', 'province', 'city', 'barangay', 'latitude', 'longitude', 'address_type'];
        foreach ($requiredColumns as $column) {
            $stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'client_addresses'
                AND column_name = ?
            ");
            $stmt->execute([$column]);
            $columnExists = $stmt->fetchColumn() > 0;

            if (!$columnExists) {
                // Add the missing column
                switch ($column) {
                    case 'full_name':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN full_name VARCHAR(100) NOT NULL AFTER client_id";
                        break;
                    case 'postal_code':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN postal_code VARCHAR(20) NOT NULL AFTER phone";
                        break;
                    case 'street_address':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN street_address TEXT NOT NULL AFTER postal_code";
                        break;
                    case 'region':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN region VARCHAR(100) NOT NULL AFTER street_address";
                        break;
                    case 'province':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN province VARCHAR(100) NOT NULL AFTER region";
                        break;
                    case 'city':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN city VARCHAR(100) NOT NULL AFTER province";
                        break;
                    case 'barangay':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN barangay VARCHAR(100) NOT NULL AFTER city";
                        break;
                    case 'latitude':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN latitude DECIMAL(10,8) NULL AFTER barangay";
                        break;
                    case 'longitude':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude";
                        break;
                    case 'address_type':
                        $sql = "ALTER TABLE client_addresses ADD COLUMN address_type VARCHAR(20) NOT NULL DEFAULT 'Home' AFTER longitude";
                        break;
                }
                $conn->exec($sql);
            }
        }
    }

    // Check if client has an address record
    $stmt = $conn->prepare("SELECT id FROM client_addresses WHERE client_id = ? AND is_default = 1");
    $stmt->execute([$userId]);
    $addressExists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($addressExists) {
        // Update existing address
        $stmt = $conn->prepare("
            UPDATE client_addresses
            SET full_name = ?, phone = ?, postal_code = ?, street_address = ?,
                region = ?, province = ?, city = ?, barangay = ?,
                latitude = ?, longitude = ?, address_type = ?, is_default = ?
            WHERE client_id = ? AND id = ?
        ");
        $stmt->execute([
            $fullName, $phone, $postalCode, $streetAddress,
            $region, $province, $city, $barangay,
            $latitude, $longitude, $addressType, $isDefault,
            $userId, $addressExists['id']
        ]);

        // If this is set as default, unset other addresses as default
        if ($isDefault) {
            $stmt = $conn->prepare("
                UPDATE client_addresses
                SET is_default = 0
                WHERE client_id = ? AND id != ?
            ");
            $stmt->execute([$userId, $addressExists['id']]);
        }
    } else {
        // Create new address record
        $stmt = $conn->prepare("
            INSERT INTO client_addresses
            (client_id, full_name, phone, postal_code, street_address, region, province, city, barangay, latitude, longitude, address_type, is_default)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, $fullName, $phone, $postalCode, $streetAddress,
            $region, $province, $city, $barangay, $latitude, $longitude, $addressType, $isDefault
        ]);

        // If this is set as default, unset other addresses as default
        if ($isDefault) {
            $lastId = $conn->lastInsertId();
            $stmt = $conn->prepare("
                UPDATE client_addresses
                SET is_default = 0
                WHERE client_id = ? AND id != ?
            ");
            $stmt->execute([$userId, $lastId]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Address updated successfully!'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating address: ' . $e->getMessage()
    ]);
}
?>
