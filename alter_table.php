<?php
// Include the database connection
require_once "templates/includes/db.php";

echo "<h1>Alter Table Structure</h1>";

try {
    // Check if fullname column exists in client_addresses table
    $query = "SHOW COLUMNS FROM client_addresses LIKE 'fullname'";
    $result = executeQuery($query);
    
    if ($connection_type === 'pdo') {
        $fullnameExists = $result->rowCount() > 0;
    } else {
        $fullnameExists = $result->num_rows > 0;
    }
    
    if (!$fullnameExists) {
        echo "<p>Adding fullname column to client_addresses table...</p>";
        
        // Add fullname column to client_addresses table
        $query = "ALTER TABLE client_addresses ADD COLUMN fullname VARCHAR(100) NULL AFTER client_id";
        executeQuery($query);
        
        echo "<p>Fullname column added successfully!</p>";
        
        // Update existing records to copy name from users table
        echo "<p>Updating existing records with names from users table...</p>";
        
        $query = "UPDATE client_addresses ca 
                  JOIN users u ON ca.client_id = u.id 
                  SET ca.fullname = u.name 
                  WHERE ca.fullname IS NULL";
        executeQuery($query);
        
        echo "<p>Existing records updated successfully!</p>";
    } else {
        echo "<p>Fullname column already exists in client_addresses table.</p>";
        
        // Update any NULL fullname values with names from users table
        echo "<p>Updating any NULL fullname values with names from users table...</p>";
        
        $query = "UPDATE client_addresses ca 
                  JOIN users u ON ca.client_id = u.id 
                  SET ca.fullname = u.name 
                  WHERE ca.fullname IS NULL";
        executeQuery($query);
        
        echo "<p>NULL fullname values updated successfully!</p>";
    }
    
    // Show updated client_addresses table structure
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
    
    // Show sample data from client_addresses table
    echo "<h2>Sample Data from Client_Addresses Table</h2>";
    $query = "SELECT ca.*, u.name as user_name 
              FROM client_addresses ca 
              JOIN users u ON ca.client_id = u.id 
              LIMIT 5";
    $result = executeQuery($query);
    
    if ($connection_type === 'pdo') {
        $addresses = $result->fetchAll(PDO::FETCH_ASSOC);
        if (count($addresses) > 0) {
            echo "<table border='1'>";
            echo "<tr>";
            foreach (array_keys($addresses[0]) as $key) {
                echo "<th>" . $key . "</th>";
            }
            echo "</tr>";
            
            foreach ($addresses as $address) {
                echo "<tr>";
                foreach ($address as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No address records found.</p>";
        }
    } else {
        $result->store_result();
        $meta = $result->result_metadata();
        $fields = [];
        while ($field = $meta->fetch_field()) {
            $fields[] = $field->name;
        }
        
        echo "<table border='1'>";
        echo "<tr>";
        foreach ($fields as $field) {
            echo "<th>" . $field . "</th>";
        }
        echo "</tr>";
        
        $values = array_fill(0, count($fields), null);
        $bindParams = [];
        foreach ($fields as $i => $field) {
            $bindParams[] = &$values[$i];
        }
        call_user_func_array([$result, 'bind_result'], $bindParams);
        
        while ($result->fetch()) {
            echo "<tr>";
            foreach ($values as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
