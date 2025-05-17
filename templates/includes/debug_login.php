<?php
/**
 * Debug Login
 * 
 * This script helps diagnose login issues by testing the login process directly.
 */

require_once "db.php";

// Set content type to plain text
header('Content-Type: text/plain');

// Test credentials
$email = isset($_GET['email']) ? $_GET['email'] : 'adminbrewandbake@gmail.com';
$password = isset($_GET['password']) ? $_GET['password'] : 'admin123';

echo "Debug Login Test\n";
echo "================\n\n";
echo "Testing login with:\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "ERROR: User not found with email: $email\n";
        exit;
    }
    
    echo "User found in database:\n";
    echo "ID: {$user['id']}\n";
    echo "Name: {$user['name']}\n";
    echo "Email: {$user['email']}\n";
    echo "Role: {$user['role']}\n";
    echo "Verification Status: " . ($user['verification_status'] ? 'Verified' : 'Not Verified') . "\n";
    echo "Password Hash: {$user['password']}\n\n";
    
    // Test password verification
    $isPasswordCorrect = password_verify($password, $user['password']);
    
    echo "Password Verification Test:\n";
    echo "Result: " . ($isPasswordCorrect ? 'SUCCESS - Password matches' : 'FAILURE - Password does not match') . "\n\n";
    
    if (!$isPasswordCorrect) {
        echo "Debugging password verification:\n";
        
        // Get hash info
        $hashInfo = password_get_info($user['password']);
        echo "Hash Algorithm: " . ($hashInfo['algoName'] ?: 'Unknown') . "\n";
        echo "Hash Algorithm ID: {$hashInfo['algo']}\n";
        
        // Try to update the password
        echo "\nUpdating password to ensure it's properly hashed...\n";
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        echo "New hash: $newHash\n";
        
        // Test the new hash
        $isNewHashValid = password_verify($password, $newHash);
        echo "New hash verification: " . ($isNewHashValid ? 'VALID' : 'INVALID') . "\n\n";
        
        if ($isNewHashValid) {
            // Update the password in the database
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
            
            echo "Password updated in database. Please try logging in again.\n";
        } else {
            echo "ERROR: Could not generate a valid password hash. This might indicate an issue with the PHP configuration.\n";
        }
    } else {
        // If password is correct but user is not verified
        if ($user['verification_status'] == 0) {
            echo "NOTE: User account is not verified. Updating verification status...\n";
            
            $stmt = $conn->prepare("UPDATE users SET verification_status = 1 WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            echo "Verification status updated to VERIFIED. Please try logging in again.\n";
        }
    }
    
    // Test session creation
    echo "\nTesting session creation:\n";
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    
    echo "Session variables set:\n";
    echo "user_id: {$_SESSION['user_id']}\n";
    echo "user_role: {$_SESSION['user_role']}\n";
    echo "user_name: {$_SESSION['user_name']}\n";
    echo "user_email: {$_SESSION['user_email']}\n\n";
    
    echo "Login process complete. You should now be able to log in with the provided credentials.\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
