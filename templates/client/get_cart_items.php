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

// Initialize response
$response = [
    'items' => [],
    'total_items' => 0,
    'total_amount' => 0
];

// Check if cart exists
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode($response);
    exit;
}

// Get cart items
$productIds = array_keys($_SESSION['cart']);
$placeholders = str_repeat('?,', count($productIds) - 1) . '?';

try {
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id IN ($placeholders) AND p.status = 'active'
    ");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process products
    foreach ($products as $product) {
        $quantity = $_SESSION['cart'][$product['id']];
        $subtotal = $product['price'] * $quantity;
        
        $response['items'][] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'category' => $product['category_name'],
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
        
        $response['total_amount'] += $subtotal;
    }
    
    // Sort items by most recently added (assuming the cart is ordered by addition time)
    $response['items'] = array_reverse($response['items']);
    
    // Limit to 5 items for the dropdown
    $response['total_items'] = count($response['items']);
    if (count($response['items']) > 5) {
        $response['items'] = array_slice($response['items'], 0, 5);
    }
    
    echo json_encode($response);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
