<?php
// Include the updated db.php file
require_once "templates/includes/db.php";

echo "<h1>Database Connection Test</h1>";

// Display connection type
echo "<p>Connection Type: " . (isset($connection_type) ? $connection_type : 'unknown') . "</p>";

// Test the executeQuery function
try {
    // Check if users table exists
    $query = "SHOW TABLES LIKE 'users'";
    $result = executeQuery($query);
    
    if ($connection_type === 'pdo') {
        $tableExists = $result->rowCount() > 0;
    } else {
        $tableExists = $result->num_rows > 0;
    }
    
    if ($tableExists) {
        echo "<p>Users table exists!</p>";
        
        // Get user count
        $query = "SELECT COUNT(*) as count FROM users";
        $result = executeQuery($query);
        
        if ($connection_type === 'pdo') {
            $userCount = $result->fetchColumn();
        } else {
            $result->bind_result($userCount);
            $result->fetch();
        }
        
        echo "<p>Number of users in the database: " . $userCount . "</p>";
    } else {
        echo "<p>Users table does not exist!</p>";
        
        // Create users table if it doesn't exist
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'staff', 'client') NOT NULL DEFAULT 'client',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        executeQuery($query);
        echo "<p>Users table created successfully!</p>";
    }
    
    // Check if client_addresses table exists
    $query = "SHOW TABLES LIKE 'client_addresses'";
    $result = executeQuery($query);
    
    if ($connection_type === 'pdo') {
        $tableExists = $result->rowCount() > 0;
    } else {
        $tableExists = $result->num_rows > 0;
    }
    
    if ($tableExists) {
        echo "<p>Client_addresses table exists!</p>";
        
        // Get address count
        $query = "SELECT COUNT(*) as count FROM client_addresses";
        $result = executeQuery($query);
        
        if ($connection_type === 'pdo') {
            $addressCount = $result->fetchColumn();
        } else {
            $result->bind_result($addressCount);
            $result->fetch();
        }
        
        echo "<p>Number of addresses in the database: " . $addressCount . "</p>";
    } else {
        echo "<p>Client_addresses table does not exist!</p>";
        
        // Create client_addresses table if it doesn't exist
        $query = "CREATE TABLE IF NOT EXISTS client_addresses (
            id INT NOT NULL AUTO_INCREMENT,
            client_id INT NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL DEFAULT 'Manila',
            state VARCHAR(100) NULL,
            postal_code VARCHAR(20) NOT NULL DEFAULT '1000',
            country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
            phone VARCHAR(20) NULL,
            latitude VARCHAR(20) NULL DEFAULT '9.994295',
            longitude VARCHAR(20) NULL DEFAULT '118.918419',
            is_default BOOLEAN NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )";
        
        executeQuery($query);
        echo "<p>Client_addresses table created successfully!</p>";
        
        // Add foreign key constraint if users table exists
        if (isset($userCount)) {
            $query = "ALTER TABLE client_addresses
                ADD CONSTRAINT fk_client_addresses_client_id
                FOREIGN KEY (client_id) REFERENCES users(id)
                ON DELETE CASCADE";
            
            try {
                executeQuery($query);
                echo "<p>Foreign key constraint added successfully!</p>";
            } catch (Exception $e) {
                echo "<p>Error adding foreign key constraint: " . $e->getMessage() . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
