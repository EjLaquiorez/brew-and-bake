<?php
// Script to normalize the client_addresses table by removing redundant fields
// and ensuring proper database normalization

// Include the database connection
require_once "templates/includes/db.php";

echo "<h1>Normalize Client Addresses Table</h1>";

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
        echo "<p>Client_addresses table does not exist. Creating a normalized version...</p>";
        
        // Create a normalized client_addresses table
        $query = "
            CREATE TABLE client_addresses (
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
                PRIMARY KEY (id),
                FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        executeQuery($query);
        
        echo "<p>Normalized client_addresses table created successfully!</p>";
    } else {
        echo "<p>Client_addresses table exists. Checking for redundant columns...</p>";
        
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
        
        // Check if full_name column exists
        $query = "
            SELECT COUNT(*)
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
            AND table_name = 'client_addresses'
            AND column_name = 'full_name'
        ";
        $result = executeQuery($query);
        
        if ($connection_type === 'pdo') {
            $fullNameExists = $result->fetchColumn() > 0;
        } else {
            $result->bind_result($count);
            $result->fetch();
            $fullNameExists = $count > 0;
        }
        
        // Remove redundant columns
        if ($fullnameExists) {
            echo "<p>Removing redundant 'fullname' column...</p>";
            
            $query = "ALTER TABLE client_addresses DROP COLUMN fullname";
            executeQuery($query);
            
            echo "<p>'fullname' column removed successfully!</p>";
        }
        
        if ($fullNameExists) {
            echo "<p>Removing redundant 'full_name' column...</p>";
            
            $query = "ALTER TABLE client_addresses DROP COLUMN full_name";
            executeQuery($query);
            
            echo "<p>'full_name' column removed successfully!</p>";
        }
        
        // Check for other redundant or inconsistent columns
        $columnsToCheck = [
            'street_address' => 'address',
            'region' => 'state',
            'province' => 'state',
            'barangay' => 'address'
        ];
        
        foreach ($columnsToCheck as $oldColumn => $newColumn) {
            $query = "
                SELECT COUNT(*)
                FROM information_schema.columns
                WHERE table_schema = DATABASE()
                AND table_name = 'client_addresses'
                AND column_name = ?
            ";
            $result = executeQuery($query, [$oldColumn]);
            
            if ($connection_type === 'pdo') {
                $columnExists = $result->fetchColumn() > 0;
            } else {
                $result->bind_result($count);
                $result->fetch();
                $columnExists = $count > 0;
            }
            
            if ($columnExists) {
                echo "<p>Found redundant column '$oldColumn'. Migrating data to '$newColumn'...</p>";
                
                // Check if the target column exists
                $query = "
                    SELECT COUNT(*)
                    FROM information_schema.columns
                    WHERE table_schema = DATABASE()
                    AND table_name = 'client_addresses'
                    AND column_name = ?
                ";
                $result = executeQuery($query, [$newColumn]);
                
                if ($connection_type === 'pdo') {
                    $targetColumnExists = $result->fetchColumn() > 0;
                } else {
                    $result->bind_result($count);
                    $result->fetch();
                    $targetColumnExists = $count > 0;
                }
                
                if (!$targetColumnExists) {
                    // Create the target column if it doesn't exist
                    if ($newColumn === 'address') {
                        $query = "ALTER TABLE client_addresses ADD COLUMN address TEXT NOT NULL AFTER client_id";
                    } elseif ($newColumn === 'state') {
                        $query = "ALTER TABLE client_addresses ADD COLUMN state VARCHAR(100) NULL AFTER city";
                    }
                    executeQuery($query);
                    echo "<p>Created target column '$newColumn'</p>";
                }
                
                // Migrate data
                if ($oldColumn === 'street_address' && $newColumn === 'address') {
                    $query = "UPDATE client_addresses SET address = street_address WHERE address IS NULL OR address = ''";
                    executeQuery($query);
                } elseif (($oldColumn === 'region' || $oldColumn === 'province') && $newColumn === 'state') {
                    $query = "UPDATE client_addresses SET state = $oldColumn WHERE state IS NULL OR state = ''";
                    executeQuery($query);
                } elseif ($oldColumn === 'barangay' && $newColumn === 'address') {
                    $query = "UPDATE client_addresses SET address = CONCAT(address, ', Barangay ', barangay) WHERE barangay IS NOT NULL AND barangay != ''";
                    executeQuery($query);
                }
                
                echo "<p>Data migrated successfully!</p>";
                
                // Drop the old column
                $query = "ALTER TABLE client_addresses DROP COLUMN $oldColumn";
                executeQuery($query);
                
                echo "<p>Removed redundant column '$oldColumn'</p>";
            }
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
    
    echo "<p>Database normalization completed successfully!</p>";
    echo "<p><a href='templates/client/profile.php'>Go to Profile Page</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
