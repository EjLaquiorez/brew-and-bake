<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../views/login.php");
    exit;
}
require_once "../includes/db.php";

try {
    // Read the SQL file
    $sqlFile = file_get_contents("../includes/update_categories_first.sql");
    
    // Execute the SQL
    $conn->exec($sqlFile);
    
    $_SESSION['success'] = "✅ Categories updated successfully!";
} catch (PDOException $e) {
    $_SESSION['error'] = "❌ Error updating categories: " . $e->getMessage();
}

// Redirect back to categories page
header("Location: categories.php");
exit;
?>
