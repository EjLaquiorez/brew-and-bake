<?php
// Script to update the client_addresses table by replacing state with province

// Include the database connection
require_once "templates/includes/db.php";

echo "<h1>Update Client Addresses Table: State to Province</h1>";

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
        echo "<p>Client_addresses table does not exist. Creating it with the correct structure...</p>";
        
        // Create the client_addresses table with province instead of state
        $query = "
            CREATE TABLE client_addresses (
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
        ";
        executeQuery($query);
        
        echo "<p>Client_addresses table created successfully with province field!</p>";
    } else {
        echo "<p>Client_addresses table exists. Checking for state column...</p>";
        
        // Check if state column exists
        $query = "
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = 'client_addresses'
            AND column_name = 'state'
        ";
        $result = executeQuery($query);
        
        if ($connection_type === 'pdo') {
            $stateExists = $result->fetchColumn() > 0;
        } else {
            $result->bind_result($count);
            $result->fetch();
            $stateExists = $count > 0;
        }
        
        // Check if province column already exists
        $query = "
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = 'client_addresses'
            AND column_name = 'province'
        ";
        $result = executeQuery($query);
        
        if ($connection_type === 'pdo') {
            $provinceExists = $result->fetchColumn() > 0;
        } else {
            $result->bind_result($count);
            $result->fetch();
            $provinceExists = $count > 0;
        }
        
        if ($stateExists && !$provinceExists) {
            echo "<p>Renaming 'state' column to 'province'...</p>";
            
            // Rename state column to province
            $query = "ALTER TABLE client_addresses CHANGE state province VARCHAR(100) NULL";
            executeQuery($query);
            
            echo "<p>Column renamed successfully!</p>";
        } elseif ($stateExists && $provinceExists) {
            echo "<p>Both 'state' and 'province' columns exist. Migrating data and removing 'state'...</p>";
            
            // Copy data from state to province where province is NULL
            $query = "UPDATE client_addresses SET province = state WHERE province IS NULL";
            executeQuery($query);
            
            // Drop state column
            $query = "ALTER TABLE client_addresses DROP COLUMN state";
            executeQuery($query);
            
            echo "<p>Data migrated and 'state' column removed successfully!</p>";
        } elseif (!$stateExists && !$provinceExists) {
            echo "<p>Neither 'state' nor 'province' column exists. Adding 'province' column...</p>";
            
            // Add province column
            $query = "ALTER TABLE client_addresses ADD COLUMN province VARCHAR(100) NULL AFTER city";
            executeQuery($query);
            
            echo "<p>'Province' column added successfully!</p>";
        } else {
            echo "<p>'Province' column already exists. No changes needed.</p>";
        }
        
        // Check if address_type column exists
        $query = "
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = 'client_addresses'
            AND column_name = 'address_type'
        ";
        $result = executeQuery($query);
        
        if ($connection_type === 'pdo') {
            $addressTypeExists = $result->fetchColumn() > 0;
        } else {
            $result->bind_result($count);
            $result->fetch();
            $addressTypeExists = $count > 0;
        }
        
        if (!$addressTypeExists) {
            echo "<p>Adding 'address_type' column...</p>";
            
            // Add address_type column
            $query = "ALTER TABLE client_addresses ADD COLUMN address_type VARCHAR(20) NOT NULL DEFAULT 'Home' AFTER longitude";
            executeQuery($query);
            
            echo "<p>'address_type' column added successfully!</p>";
        }
    }
    
    // Show the updated table structure
    echo "<h2>Updated Client_Addresses Table Structure</h2>";
    $query = "DESCRIBE client_addresses";
    $result = executeQuery($query);
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    if ($connection_type === 'pdo') {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
    } else {
        $result->bind_result($field, $type, $null, $key, $default, $extra);
        while ($result->fetch()) {
            echo "<tr>";
            echo "<td>" . $field . "</td>";
            echo "<td>" . $type . "</td>";
            echo "<td>" . $null . "</td>";
            echo "<td>" . $key . "</td>";
            echo "<td>" . $default . "</td>";
            echo "<td>" . $extra . "</td>";
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
