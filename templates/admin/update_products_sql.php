<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../views/login.php");
    exit;
}
require_once "../includes/db.php";

try {
    // Read the SQL file
    $sqlFile = file_get_contents("../includes/update_products.sql");
    
    // Execute the SQL
    $conn->exec($sqlFile);
    
    $_SESSION['success'] = "✅ Products updated successfully using SQL!";
} catch (PDOException $e) {
    $_SESSION['error'] = "❌ Error updating products: " . $e->getMessage();
}

// Redirect back to products page
header("Location: products.php");
exit;
?>
