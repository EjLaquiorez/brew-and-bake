<?php
// This script fixes the client_addresses table issue using mysqli instead of PDO

// Database connection parameters
$host = "localhost";
$db = "brew_and_bake";
$user = "root";
$pass = "admin";

// Connect to the database
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to the database.<br>";

try {
    // Check if client_addresses table exists
    $result = $conn->query("SHOW TABLES LIKE 'client_addresses'");
    $tableExists = $result->num_rows > 0;

    // Create client_addresses table if it doesn't exist
    if (!$tableExists) {
        $sql = "CREATE TABLE client_addresses (
            id INT NOT NULL AUTO_INCREMENT,
            client_id INT NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NULL,
            postal_code VARCHAR(20) NOT NULL,
            country VARCHAR(100) NOT NULL DEFAULT 'Philippines',
            phone VARCHAR(20) NULL,
            is_default BOOLEAN NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        )";

        if ($conn->query($sql) === TRUE) {
            echo "Client addresses table created successfully!<br>";
        } else {
            echo "Error creating table: " . $conn->error . "<br>";
        }
    } else {
        echo "Client addresses table already exists.<br>";
        
        // Check if phone column exists
        $result = $conn->query("SHOW COLUMNS FROM client_addresses LIKE 'phone'");
        $phoneColumnExists = $result->num_rows > 0;

        if (!$phoneColumnExists) {
            // Add phone column if it doesn't exist
            $sql = "ALTER TABLE client_addresses ADD COLUMN phone VARCHAR(20) NULL AFTER country";
            if ($conn->query($sql) === TRUE) {
                echo "Phone column added to client_addresses table successfully!<br>";
            } else {
                echo "Error adding phone column: " . $conn->error . "<br>";
            }
        } else {
            echo "Phone column already exists in client_addresses table.<br>";
        }
        
        // Check if is_default column exists
        $result = $conn->query("SHOW COLUMNS FROM client_addresses LIKE 'is_default'");
        $isDefaultColumnExists = $result->num_rows > 0;

        if (!$isDefaultColumnExists) {
            // Add is_default column if it doesn't exist
            $sql = "ALTER TABLE client_addresses ADD COLUMN is_default BOOLEAN NOT NULL DEFAULT 1 AFTER phone";
            if ($conn->query($sql) === TRUE) {
                echo "is_default column added to client_addresses table successfully!<br>";
            } else {
                echo "Error adding is_default column: " . $conn->error . "<br>";
            }
        } else {
            echo "is_default column already exists in client_addresses table.<br>";
        }
    }

    echo "<p>Database setup completed successfully!</p>";
    echo "<p><a href='templates/client/profile.php'>Go to Profile Page</a></p>";

} catch (Exception $e) {
    echo "Error setting up database: " . $e->getMessage() . "<br>";
} finally {
    // Close the connection
    $conn->close();
}
?>
