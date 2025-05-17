<?php
// Script to ensure the fullname column exists in client_addresses table
// and update all existing records with the user's name from the users table

// Include the database connection
require_once "templates/includes/db.php";

echo "<h1>Update Client Addresses Table</h1>";

try {
    // Check if client_addresses table exists
    $query = "
        SELECT COUNT(*)
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        AND table_name = 'client_addresses'
    ";
    $result = executeQuery($query);
    
    if ($connection_type === 'pdo') {
        $tableExists = $result->fetchColumn() > 0;
    } else {
        $result->bind_result($count);
        $result->fetch();
        $tableExists = $count > 0;
    }
    
    if (!$tableExists) {
        echo "<p>Client_addresses table does not exist. Creating it now...</p>";
        
        // Create the client_addresses table
        $query = "
            CREATE TABLE IF NOT EXISTS client_addresses (
                id INT NOT NULL AUTO_INCREMENT,
                client_id INT NOT NULL,
                fullname VARCHAR(100) NULL,
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
                PRIMARY KEY (id),
                FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        executeQuery($query);
        
        echo "<p>Client_addresses table created successfully!</p>";
    } else {
        echo "<p>Client_addresses table exists.</p>";
        
        // Check if fullname column exists
        $query = "
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = 'client_addresses'
            AND column_name = 'fullname'
        ";
        $result = executeQuery($query);
        
        if ($connection_type === 'pdo') {
            $fullnameExists = $result->fetchColumn() > 0;
        } else {
            $result->bind_result($count);
            $result->fetch();
            $fullnameExists = $count > 0;
        }
        
        if (!$fullnameExists) {
            echo "<p>Adding fullname column to client_addresses table...</p>";
            
            // Add fullname column
            $query = "ALTER TABLE client_addresses ADD COLUMN fullname VARCHAR(100) NULL AFTER client_id";
            executeQuery($query);
            
            echo "<p>Fullname column added successfully!</p>";
        } else {
            echo "<p>Fullname column already exists in client_addresses table.</p>";
        }
    }
    
    // Update fullname values from users table
    echo "<p>Updating fullname values from users table...</p>";
    
    $query = "
        UPDATE client_addresses ca
        JOIN users u ON ca.client_id = u.id
        SET ca.fullname = u.name
        WHERE ca.fullname IS NULL OR ca.fullname = ''
    ";
    executeQuery($query);
    
    echo "<p>Fullname values updated successfully!</p>";
    
    // Show sample data
    echo "<h2>Sample Data from Client_Addresses Table</h2>";
    
    $query = "
        SELECT ca.id, ca.client_id, ca.fullname, u.name as user_name, ca.address, ca.city, ca.phone
        FROM client_addresses ca
        JOIN users u ON ca.client_id = u.id
        LIMIT 10
    ";
    $result = executeQuery($query);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Client ID</th><th>Fullname</th><th>User Name</th><th>Address</th><th>City</th><th>Phone</th></tr>";
    
    if ($connection_type === 'pdo') {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['client_id'] . "</td>";
            echo "<td>" . $row['fullname'] . "</td>";
            echo "<td>" . $row['user_name'] . "</td>";
            echo "<td>" . $row['address'] . "</td>";
            echo "<td>" . $row['city'] . "</td>";
            echo "<td>" . $row['phone'] . "</td>";
            echo "</tr>";
        }
    } else {
        $result->bind_result($id, $clientId, $fullname, $userName, $address, $city, $phone);
        while ($result->fetch()) {
            echo "<tr>";
            echo "<td>" . $id . "</td>";
            echo "<td>" . $clientId . "</td>";
            echo "<td>" . $fullname . "</td>";
            echo "<td>" . $userName . "</td>";
            echo "<td>" . $address . "</td>";
            echo "<td>" . $city . "</td>";
            echo "<td>" . $phone . "</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    echo "<p>Database update completed successfully!</p>";
    echo "<p><a href='templates/client/profile.php'>Go to Profile Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
