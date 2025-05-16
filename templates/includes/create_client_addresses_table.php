<?php
require_once "db.php";

try {
    // Check if client_addresses table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'client_addresses'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

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

        $conn->exec($sql);
        echo "Client addresses table created successfully!<br>";
    } else {
        // Check if phone column exists
        $stmt = $conn->prepare("SHOW COLUMNS FROM client_addresses LIKE 'phone'");
        $stmt->execute();
        $phoneColumnExists = $stmt->rowCount() > 0;

        if (!$phoneColumnExists) {
            // Add phone column if it doesn't exist
            $sql = "ALTER TABLE client_addresses ADD COLUMN phone VARCHAR(20) NULL AFTER country";
            $conn->exec($sql);
            echo "Phone column added to client_addresses table successfully!<br>";
        } else {
            echo "Phone column already exists in client_addresses table.<br>";
        }

        // Check if is_default column exists
        $stmt = $conn->prepare("SHOW COLUMNS FROM client_addresses LIKE 'is_default'");
        $stmt->execute();
        $isDefaultColumnExists = $stmt->rowCount() > 0;

        if (!$isDefaultColumnExists) {
            // Add is_default column if it doesn't exist
            $sql = "ALTER TABLE client_addresses ADD COLUMN is_default BOOLEAN NOT NULL DEFAULT 1 AFTER phone";
            $conn->exec($sql);
            echo "is_default column added to client_addresses table successfully!<br>";
        } else {
            echo "is_default column already exists in client_addresses table.<br>";
        }
    }

    echo "<p>Database setup completed successfully!</p>";
    echo "<p><a href='../../templates/client/profile.php'>Go to Profile Page</a></p>";

} catch (PDOException $e) {
    echo "Error setting up database: " . $e->getMessage() . "<br>";
}
?>
