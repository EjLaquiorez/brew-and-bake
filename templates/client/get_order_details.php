<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Set content type to JSON
header('Content-Type: application/json');

// Security check
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get user ID
$userId = $_SESSION['user_id'];

// Get order ID from request
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    echo json_encode(['error' => 'Invalid order ID']);
    exit;
}

try {
    // Verify the order belongs to the current user
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND client_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['error' => 'Order not found or access denied']);
        exit;
    }

    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name 
        FROM orders_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate subtotal
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['total_price'];
    }

    // Prepare response data
    $responseData = [
        'id' => $order['id'],
        'order_date' => $order['order_date'] ?? $order['created_at'],
        'status' => $order['status'],
        'total_price' => $order['total_price'],
        'payment_status' => $order['payment_status'],
        'items' => $items,
        'subtotal' => $subtotal,
        'shipping' => isset($order['shipping_fee']) ? $order['shipping_fee'] : 50.00, // Default shipping fee if not in database
    ];

    // Send response
    echo json_encode($responseData);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
