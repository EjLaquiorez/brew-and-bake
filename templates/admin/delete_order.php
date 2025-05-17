<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: ../../views/login.php");
    exit;
}

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$orderId) {
    $_SESSION['error'] = "Invalid order ID.";
    header("Location: orders.php");
    exit;
}

try {
    // Check if the order exists
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error'] = "Order #$orderId not found.";
        header("Location: orders.php");
        exit;
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Check if order_items table exists and delete related items
    $stmt = $conn->prepare("SHOW TABLES LIKE 'order_items'");
    $stmt->execute();
    $orderItemsTableExists = $stmt->rowCount() > 0;
    
    if ($orderItemsTableExists) {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
    }
    
    // Delete the order
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Order #$orderId has been deleted successfully.";
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $_SESSION['error'] = "Error deleting order: " . $e->getMessage();
}

// Redirect back to orders page
header("Location: orders.php");
exit;
?>
