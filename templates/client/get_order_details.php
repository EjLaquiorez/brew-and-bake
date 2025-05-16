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
    // Check if orders table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
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
            FROM order_items oi
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

        // Get payment information
        $stmt = $conn->prepare("
            SELECT * FROM payments
            WHERE order_id = ?
            ORDER BY transaction_date DESC
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate shipping fee (difference between total and subtotal)
        $shippingFee = $order['total_price'] - $subtotal;

        // Prepare response data
        $responseData = [
            'id' => $order['id'],
            'order_date' => $order['order_date'] ?? $order['created_at'],
            'status' => $order['status'],
            'total_price' => $order['total_price'],
            'payment_status' => $order['payment_status'],
            'payment_method' => $payment ? $payment['payment_method'] : 'cash',
            'payment_transaction_date' => $payment ? $payment['transaction_date'] : null,
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shippingFee > 0 ? $shippingFee : 50.00, // Default shipping fee if not calculated
        ];

        // Send response
        echo json_encode($responseData);
    } else {
        // If orders table doesn't exist, return sample data
        $sampleData = [
            'id' => $orderId,
            'order_date' => '2025-06-15 14:30:45',
            'status' => 'completed',
            'total_price' => 625.00,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'payment_transaction_date' => '2025-06-15 14:35:22',
            'items' => [
                [
                    'name' => 'Kapeng Barako',
                    'price' => 150.00,
                    'quantity' => 2,
                    'total_price' => 300.00
                ],
                [
                    'name' => 'Ube Cheese Pandesal',
                    'price' => 35.00,
                    'quantity' => 3,
                    'total_price' => 105.00
                ],
                [
                    'name' => 'Ensaymada',
                    'price' => 85.00,
                    'quantity' => 2,
                    'total_price' => 170.00
                ]
            ],
            'subtotal' => 575.00,
            'shipping' => 50.00,
            'address' => '123 Sample Street, Barangay Sample, Manila, Philippines'
        ];

        echo json_encode($sampleData);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
