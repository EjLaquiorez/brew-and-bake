<?php
/**
 * Create Authentication Tables
 * 
 * This script creates the necessary tables for authentication functionality.
 */

require_once "db.php";

// Check if remember_tokens table exists
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = 'remember_tokens'
    ");
    $stmt->execute();
    $rememberTokensTableExists = $stmt->fetchColumn() > 0;
    
    if (!$rememberTokensTableExists) {
        // Create remember_tokens table
        $sql = "CREATE TABLE remember_tokens (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY (token),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $conn->exec($sql);
        echo "Remember tokens table created successfully!<br>";
    } else {
        echo "Remember tokens table already exists.<br>";
    }
    
    // Check if users table has verification_status column
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
        AND table_name = 'users'
        AND column_name = 'verification_status'
    ");
    $stmt->execute();
    $verificationStatusExists = $stmt->fetchColumn() > 0;
    
    if (!$verificationStatusExists) {
        // Add verification_status column to users table
        $sql = "ALTER TABLE users 
                ADD COLUMN verification_status TINYINT(1) NOT NULL DEFAULT 1,
                ADD COLUMN verification_token VARCHAR(255) NULL";
        
        $conn->exec($sql);
        echo "Added verification columns to users table.<br>";
    } else {
        echo "Verification columns already exist in users table.<br>";
    }
    
    echo "Authentication tables setup completed successfully!";
    
} catch (PDOException $e) {
    echo "Error setting up authentication tables: " . $e->getMessage() . "<br>";
}
?>
