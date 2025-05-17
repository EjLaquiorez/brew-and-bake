<?php
/**
 * Test Password Verification
 * 
 * This script tests the password_verify function with the admin user.
 */

require_once "db.php";

// Set content type to plain text
header('Content-Type: text/plain');

// Test credentials
$email = 'adminbrewandbake@gmail.com';
$password = 'admin123';

try {
    // Get user from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "User not found with email: $email\n";
        exit;
    }
    
    echo "User found:\n";
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Role: {$user['role']}\n";
    echo "Verification Status: " . ($user['verification_status'] ? 'Verified' : 'Not Verified') . "\n";
    echo "Password Hash: {$user['password']}\n\n";
    
    // Test password verification
    $isPasswordCorrect = password_verify($password, $user['password']);
    
    echo "Password Verification Test:\n";
    echo "Password: $password\n";
    echo "Result: " . ($isPasswordCorrect ? 'MATCH' : 'NO MATCH') . "\n\n";
    
    // Get hash info
    $hashInfo = password_get_info($user['password']);
    
    echo "Hash Information:\n";
    echo "Algorithm: " . ($hashInfo['algoName'] ?: 'Unknown') . "\n";
    echo "Algorithm ID: {$hashInfo['algo']}\n";
    echo "Options: " . json_encode($hashInfo['options']) . "\n\n";
    
    // If password doesn't match, try to update it
    if (!$isPasswordCorrect) {
        echo "Password doesn't match. Updating password...\n";
        
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
        
        echo "Password updated with new hash: $newHash\n";
        echo "Testing new hash...\n";
        
        $isNewPasswordCorrect = password_verify($password, $newHash);
        echo "New hash verification result: " . ($isNewPasswordCorrect ? 'MATCH' : 'NO MATCH') . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
