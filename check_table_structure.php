<?php
// Include the database connection
require_once "templates/includes/db.php";

echo "<h1>Table Structure Check</h1>";

// Check users table structure
try {
    echo "<h2>Users Table Structure</h2>";
    $query = "DESCRIBE users";
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
    
    // Check client_addresses table structure
    echo "<h2>Client_Addresses Table Structure</h2>";
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
    
    // Check sample data from both tables
    echo "<h2>Sample Data</h2>";
    
    // Users sample
    echo "<h3>Users Sample (5 records)</h3>";
    $query = "SELECT * FROM users LIMIT 5";
    $result = executeQuery($query);
    
    if ($connection_type === 'pdo') {
        $users = $result->fetchAll(PDO::FETCH_ASSOC);
        if (count($users) > 0) {
            echo "<table border='1'>";
            echo "<tr>";
            foreach (array_keys($users[0]) as $key) {
                echo "<th>" . $key . "</th>";
            }
            echo "</tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                foreach ($user as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No user records found.</p>";
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
    
    // Client_addresses sample
    echo "<h3>Client_Addresses Sample (5 records)</h3>";
    $query = "SELECT * FROM client_addresses LIMIT 5";
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
