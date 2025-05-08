<?php
require_once "includes/db.php";

try {
    $sql = "ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'";
    $conn->exec($sql);
    echo "Status column added successfully!";
} catch (PDOException $e) {
    echo "Error adding status column: " . $e->getMessage();
}
?> 