<?php
/**
 * Create Test Admin User
 * 
 * This script creates a test admin user for the Brew & Bake application.
 */

require_once "db.php";

// Set content type to plain text
header('Content-Type: text/plain');

// Admin user details
$email = 'adminbrewandbake@gmail.com';
$password = 'admin123';
$name = 'Admin User';
$role = 'admin';

try {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "Admin user already exists with ID: {$existingUser['id']}\n";
        echo "Email: {$existingUser['email']}\n";
        echo "Role: {$existingUser['role']}\n";
        echo "Verification Status: " . ($existingUser['verification_status'] ? 'Verified' : 'Not Verified') . "\n";
        
        // Update the password if needed
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, verification_status = 1 WHERE id = ?");
        $stmt->execute([$hashedPassword, $existingUser['id']]);
        
        echo "\nPassword updated for existing admin user.\n";
        echo "You can now login with:\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    } else {
        // Create new admin user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $verificationToken = bin2hex(random_bytes(32));
        
        $stmt = $conn->prepare("
            INSERT INTO users (name, email, password, role, verification_status, verification_token, created_at) 
            VALUES (?, ?, ?, ?, 1, ?, NOW())
        ");
        $stmt->execute([$name, $email, $hashedPassword, $role, $verificationToken]);
        
        $userId = $conn->lastInsertId();
        
        echo "Admin user created successfully with ID: $userId\n";
        echo "You can now login with:\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    }
    
    // Display the hashed password for debugging
    echo "\nHashed password: $hashedPassword\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Check if users table exists
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'users'");
        $tableExists = $stmt->rowCount() > 0;
        
        if (!$tableExists) {
            echo "\nThe 'users' table does not exist. Please run the database setup script first.\n";
        } else {
            echo "\nThe 'users' table exists. Checking its structure:\n";
            $stmt = $conn->query("DESCRIBE users");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
            }
        }
    } catch (PDOException $e2) {
        echo "Error checking table structure: " . $e2->getMessage() . "\n";
    }
}
?>
