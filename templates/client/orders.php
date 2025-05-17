<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please log in to access your orders.";
    header("Location: ../../index.php");
    exit;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Handle messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle remove item from cart
    if (isset($_POST['remove_item']) && is_numeric($_POST['remove_item'])) {
        $productId = (int)$_POST['remove_item'];
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $successMessage = "Item removed from cart successfully.";
        }
    }

    // Handle update cart quantities
    if (isset($_POST['update_cart']) && isset($_POST['quantity'])) {
        $stockErrors = [];

        // First, check stock availability for all products
        foreach ($_POST['quantity'] as $productId => $quantity) {
            $productId = (int)$productId;
            $quantity = (int)$quantity;

            if ($quantity > 0 && $quantity <= 99) {
                // Check product stock
                try {
                    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
                    $stmt->execute([$productId]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($product && $quantity > $product['stock']) {
                        // If requested quantity exceeds available stock
                        $stockErrors[$productId] = [
                            'requested' => $quantity,
                            'available' => $product['stock'],
                            'message' => "Only {$product['stock']} units available for this product."
                        ];
                    }
                } catch (PDOException $e) {
                    // Skip this product on error
                    continue;
                }
            }
        }

        // If there are stock errors and this is an AJAX request, return them
        if (!empty($stockErrors) && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Some items exceed available stock.',
                'errors' => $stockErrors
            ]);
            exit;
        }

        // Update quantities that are valid
        foreach ($_POST['quantity'] as $productId => $quantity) {
            $productId = (int)$productId;
            $quantity = (int)$quantity;

            if ($quantity > 0 && $quantity <= 99) {
                if (isset($_SESSION['cart'][$productId])) {
                    // If we have a stock error for this product, set to max available
                    if (isset($stockErrors[$productId])) {
                        $_SESSION['cart'][$productId] = $stockErrors[$productId]['available'];
                    } else {
                        $_SESSION['cart'][$productId] = $quantity;
                    }
                }
            }
        }

        // Set appropriate message
        if (!empty($stockErrors)) {
            $errorMessage = "Some items were adjusted due to stock limitations.";
        } else {
            $successMessage = "Cart updated successfully.";
        }

        // Only show success message if it's not an automatic update
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => empty($stockErrors) ? $successMessage : $errorMessage,
                'errors' => $stockErrors
            ]);
            exit;
        }
    }

    // Handle checkout - empty cart check
    if (isset($_POST['checkout']) && empty($_SESSION['cart'])) {
        $errorMessage = "Your cart is empty. Please add items before checkout.";
    }
}

// Get user information
$userId = getCurrentUserId();
if ($userId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMessage = "Error fetching user data: " . $e->getMessage();
        $user = [];
    }
} else {
    // Handle case where user ID is not available
    $errorMessage = "User information not available. Please log in again.";
    $user = [];
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding products to cart
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $productId = (int)$_GET['add'];

    // Get quantity from URL parameter or default to 1
    $quantity = 1;
    if (isset($_GET['quantity']) && is_numeric($_GET['quantity'])) {
        $quantity = (int)$_GET['quantity'];
        // Validate quantity
        if ($quantity < 1) {
            $quantity = 1;
        } else if ($quantity > 99) {
            $quantity = 99;
        }
    }

    // Check if product exists, is active, and has sufficient stock
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Calculate total quantity (current cart + new quantity)
            $totalQuantity = $quantity;
            if (isset($_SESSION['cart'][$productId])) {
                $totalQuantity += $_SESSION['cart'][$productId];
            }

            // Check if we have enough stock
            if ($product['stock'] <= 0) {
                $errorMessage = "Sorry, this product is out of stock.";

                // Return error for AJAX requests
                if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage,
                        'error_type' => 'out_of_stock',
                        'product_id' => $productId
                    ]);
                    exit;
                }
            }
            else if ($totalQuantity > $product['stock']) {
                // If requested quantity exceeds available stock
                $availableStock = $product['stock'];
                if (isset($_SESSION['cart'][$productId])) {
                    $availableStock -= $_SESSION['cart'][$productId];
                }

                if ($availableStock <= 0) {
                    $errorMessage = "You already have all available items of this product in your cart.";
                } else {
                    $errorMessage = "Only {$availableStock} more units available for this product.";
                }

                // Return error for AJAX requests
                if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => $errorMessage,
                        'error_type' => 'insufficient_stock',
                        'available_stock' => $availableStock,
                        'product_id' => $productId
                    ]);
                    exit;
                }
            }
            else {
                // We have enough stock, proceed with adding to cart
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId] += $quantity;
                    // Ensure quantity doesn't exceed maximum or available stock
                    $_SESSION['cart'][$productId] = min($_SESSION['cart'][$productId], 99, $product['stock']);
                } else {
                    $_SESSION['cart'][$productId] = $quantity;
                }

                $successMessage = "Product added to your cart!";

                // Check if this is an AJAX request
                if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
                    // Return JSON response for AJAX requests
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => $successMessage,
                        'quantity' => $quantity,
                        'product_id' => $productId,
                        'cart_count' => count($_SESSION['cart']),
                        'stock' => $product['stock'],
                        'remaining_stock' => $product['stock'] - $_SESSION['cart'][$productId]
                    ]);
                    exit;
                } else {
                    // Redirect for normal requests
                    header("Location: orders.php");
                    exit;
                }
            }
        } else {
            $errorMessage = "Product not found or unavailable.";
        }
    } catch (PDOException $e) {
        $errorMessage = "Error adding product to cart: " . $e->getMessage();
    }

    // If we reach here with an error and it's not an AJAX request, redirect
    if (!isset($_GET['ajax']) || $_GET['ajax'] != 1) {
        $_SESSION['error'] = $errorMessage;
        header("Location: orders.php");
        exit;
    }
}

// Get cart items
$cartItems = [];
$totalAmount = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
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

        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $subtotal = $product['price'] * $quantity;

            $cartItems[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'category' => $product['category_name'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];

            $totalAmount += $subtotal;
        }
    } catch (PDOException $e) {
        $errorMessage = "Error fetching cart items: " . $e->getMessage();
    }
}

// Process checkout after cart items and user ID are available
if (isset($_POST['checkout']) && !empty($cartItems) && $userId) {
    try {
        // Get selected items from the form
        $selectedItems = [];
        $deliveryOption = 'pickup'; // Default to pickup (no fee)
        $paymentMethod = 'cod'; // Default to Cash on Delivery

        if (isset($_POST['selected_items_json']) && !empty($_POST['selected_items_json'])) {
            $selectedItems = json_decode($_POST['selected_items_json'], true);

            // If no items selected, show error
            if (empty($selectedItems)) {
                $errorMessage = "Please select at least one item to checkout.";
                // Don't proceed with checkout
                throw new Exception($errorMessage);
            }
        }

        // Get delivery option
        if (isset($_POST['delivery_option'])) {
            $deliveryOption = $_POST['delivery_option'];
        }

        // Get payment method
        if (isset($_POST['payment_method'])) {
            $paymentMethod = $_POST['payment_method'];

            // Validate payment method
            $validPaymentMethods = ['cod', 'gcash', 'maya'];
            if (!in_array($paymentMethod, $validPaymentMethods)) {
                $paymentMethod = 'cod'; // Default to COD if invalid
            }
        }

        // Filter cart items to only include selected items
        $selectedCartItems = [];
        $selectedTotalAmount = 0;

        foreach ($cartItems as $item) {
            if (in_array($item['id'], $selectedItems)) {
                $selectedCartItems[] = $item;
                $selectedTotalAmount += $item['subtotal'];
            }
        }

        // First, verify stock availability for all selected items
        $stockErrors = [];
        $productUpdates = [];

        foreach ($selectedCartItems as $item) {
            // Check current stock
            $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$item['id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $stockErrors[] = "Product '{$item['name']}' is no longer available.";
                continue;
            }

            if ($product['stock'] < $item['quantity']) {
                if ($product['stock'] <= 0) {
                    $stockErrors[] = "'{$item['name']}' is out of stock.";
                } else {
                    $stockErrors[] = "Only {$product['stock']} units of '{$item['name']}' available.";
                    // Update cart with available quantity
                    $_SESSION['cart'][$item['id']] = $product['stock'];
                    $productUpdates[$item['id']] = $product['stock'];
                }
            }
        }

        // If there are stock errors, don't proceed with checkout
        if (!empty($stockErrors)) {
            $errorMessage = "Cannot complete checkout due to stock limitations:<br>" . implode("<br>", $stockErrors);

            // If we updated any quantities, we need to refresh the page
            if (!empty($productUpdates)) {
                $_SESSION['error'] = $errorMessage;
                header("Location: orders.php");
                exit;
            }
        } else {
            // Start transaction
            $conn->beginTransaction();

            // Calculate final total amount
            $finalTotalAmount = $selectedTotalAmount;
            $deliveryFee = 0;

            // Add delivery fee only if delivery option is selected
            if ($deliveryOption === 'delivery') {
                $deliveryFee = 50;
                $finalTotalAmount += $deliveryFee;
            }

            // Create order record
            $stmt = $conn->prepare("INSERT INTO orders (client_id, order_date, total_price, status, payment_status, created_at)
                                    VALUES (?, NOW(), ?, 'pending', 'unpaid', NOW())");
            $stmt->execute([$userId, $finalTotalAmount]);

            $orderId = $conn->lastInsertId();

            // Add order items and update stock
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, created_at)
                                    VALUES (?, ?, ?, ?, NOW())");

            $updateStockStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($selectedCartItems as $item) {
                $stmt->execute([
                    $orderId,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);

                // Update product stock
                $updateStockStmt->execute([$item['quantity'], $item['id']]);

                // Remove the item from the cart
                unset($_SESSION['cart'][$item['id']]);
            }

            // Map payment method to database value
            $paymentMethodMap = [
                'cod' => 'cash',
                'gcash' => 'gcash',
                'maya' => 'credit_card'  // Map Maya to credit_card since it's not in the allowed values
            ];

            // Get payment status based on method
            // For digital payments, we'll use 'pending' as well, but we'll handle them differently in the UI
            $paymentStatus = 'pending';

            // Create a payment record
            $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method, payment_status, transaction_date)
                                    VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $orderId,
                $finalTotalAmount,
                $paymentMethodMap[$paymentMethod],
                $paymentStatus
            ]);

            // Commit transaction
            $conn->commit();

            // If cart is now empty, initialize it as an empty array
            if (empty($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Set success message
            $itemCount = count($selectedCartItems);
            $deliveryMessage = ($deliveryOption === 'delivery') ? " with home delivery" : " for in-store pickup";

            // Get payment method display name
            $paymentMethodDisplay = [
                'cod' => 'Cash on Delivery',
                'gcash' => 'GCash',
                'maya' => 'Maya'
            ];

            $paymentMethodText = isset($paymentMethodDisplay[$paymentMethod]) ? $paymentMethodDisplay[$paymentMethod] : 'Cash on Delivery';

            // Create success message
            $_SESSION['success'] = "Your order with $itemCount item" . ($itemCount > 1 ? "s" : "") .
                                  $deliveryMessage . " has been placed successfully! " .
                                  "Payment method: $paymentMethodText. Order #" . $orderId;

            // Add additional instructions for digital payment methods
            if ($paymentMethod !== 'cod') {
                $_SESSION['info'] = "Please complete your payment using $paymentMethodText. " .
                                   "Your order will be processed once payment is confirmed.";
            }

            // Redirect to prevent form resubmission
            header("Location: orders.php");
            exit;
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $errorMessage = "Error processing your order: " . $e->getMessage();
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// Get user orders
try {
    // Check if orders table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists && $userId) {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE client_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Placeholder data for demonstration
        $orders = [
            [
                'id' => 1001,
                'total' => 850.00,
                'status' => 'completed',
                'created_at' => '2025-06-15 14:30:45',
                'items' => 3
            ],
            [
                'id' => 1002,
                'total' => 1250.75,
                'status' => 'processing',
                'created_at' => '2025-06-16 09:15:22',
                'items' => 5
            ],
            [
                'id' => 1003,
                'total' => 450.50,
                'status' => 'pending',
                'created_at' => '2025-06-16 16:45:10',
                'items' => 2
            ]
        ];
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>My Orders - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/menu.css?v=<?= time() ?>">
    <style>
        /* Orders page specific styles */
        .orders-section {
            padding: 2rem 0;
        }

        .orders-header {
            margin-bottom: 2rem;
        }

        .orders-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .orders-subtitle {
            color: var(--color-gray-600);
            font-size: 1rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--color-white);
            border-bottom: 1px solid var(--color-gray-200);
            padding: 1.25rem 1.5rem;
        }

        .card-header h2 {
            font-size: 1.25rem;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            color: var(--color-primary);
        }

        .card-header h2 i {
            margin-right: 0.75rem;
            color: var(--color-secondary);
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn-primary {
            background-color: #f59e0b;
            border-color: #f59e0b;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            border-radius: 8px;
            color: #111827;
        }

        .btn-primary:hover {
            background-color: #d97706;
            border-color: #d97706;
            color: #111827;
        }

        .cart-dropdown .btn-primary {
            background-color: #f59e0b;
            border-color: #f59e0b;
            color: #111827;
        }

        .cart-dropdown .btn-primary:hover {
            background-color: #d97706;
            border-color: #d97706;
        }

        .cart-dropdown .text-muted {
            color: #94a3b8 !important;
        }

        /* Custom Alert Styling */
        .alert {
            position: relative;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            animation: slideInDown 0.3s ease-out;
            display: flex;
            align-items: center;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
            color: #1e7e34;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            color: #bd2130;
        }

        .alert-warning {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
            color: #d39e00;
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.1);
            border-left: 4px solid #17a2b8;
            color: #117a8b;
        }

        .alert i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-container {
            max-width: 100%;
            margin-bottom: 1.5rem;
        }

        .alert-dismissible {
            padding-right: 3rem;
        }

        .alert-dismissible .btn-close {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            padding: 0.25rem;
            background: transparent;
            border: 0;
            font-size: 1.25rem;
            color: currentColor;
            opacity: 0.5;
            cursor: pointer;
        }

        .alert-dismissible .btn-close:hover {
            opacity: 0.75;
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Cart updating indicator */
        #cart-updating-indicator {
            display: flex;
            align-items: center;
            animation: fadeInOut 1.5s ease-in-out;
            position: fixed;
            bottom: 20px;
            right: 20px;
            max-width: 200px;
            padding: 8px 12px;
            border-radius: 6px;
            background-color: rgba(17, 24, 39, 0.8);
            color: #fff;
            font-size: 0.85rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            border: none;
        }

        #cart-updating-indicator i {
            animation: spin 1s linear infinite;
            margin-right: 8px;
            font-size: 1rem;
        }

        #cart-updating-indicator.success {
            background-color: rgba(40, 167, 69, 0.8);
        }

        #cart-updating-indicator.error {
            background-color: rgba(220, 53, 69, 0.8);
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes fadeInOut {
            0% { opacity: 0; transform: translateY(10px); }
            20% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(10px); }
        }

        .badge {
            padding: 0.35em 0.65em;
            font-weight: 600;
            border-radius: 6px;
        }

        /* Order status badges */
        .badge.bg-success {
            background-color: #28a745 !important;
        }

        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }

        .badge.bg-info {
            background-color: #17a2b8 !important;
        }

        .badge.bg-danger {
            background-color: #dc3545 !important;
        }

        /* Order table styles */
        .table {
            margin-bottom: 0;
        }

        .table th {
            font-weight: 600;
            color: var(--color-gray-700);
            border-top: none;
            border-bottom-width: 1px;
        }

        .table td {
            vertical-align: middle;
            color: var(--color-gray-800);
        }

        .order-id {
            font-weight: 700;
            color: var(--color-primary);
        }

        .order-date {
            color: var(--color-gray-600);
            font-size: 0.875rem;
        }

        .order-total {
            font-weight: 700;
            color: var(--color-gray-800);
        }

        .order-items {
            color: var(--color-gray-600);
            font-size: 0.875rem;
        }

        /* Shopping Cart Styles */
        .cart-section {
            margin-bottom: 2rem;
        }

        .cart-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            background-color: var(--color-gray-100);
        }

        .cart-item-placeholder {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--color-gray-100);
            border-radius: 8px;
            color: var(--color-gray-400);
            font-size: 1.5rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            max-width: 140px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            background-color: #ffffff;
        }

        .quantity-input {
            width: 60px;
            height: 38px;
            text-align: center;
            border-radius: 0;
            border: none;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            font-weight: 600;
            color: #111827;
            font-size: 1rem;
            padding: 0.375rem 0.5rem;
            background-color: #ffffff;
        }

        .quantity-input:focus {
            outline: none;
            box-shadow: none;
            background-color: #f9fafb;
        }

        .quantity-btn {
            border: none;
            background-color: #f9fafb;
            color: #4b5563;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            height: 38px;
            width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .quantity-btn:hover {
            background-color: #f3f4f6;
            color: #111827;
        }

        .quantity-btn:active,
        .quantity-btn.active {
            background-color: #e5e7eb;
            transform: scale(0.95);
        }

        .quantity-btn:focus {
            outline: none;
            box-shadow: none;
        }

        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Add red styling for quantity controls when at stock limit */
        .quantity-control.at-limit .quantity-btn,
        .quantity-control.at-limit .quantity-input {
            color: #dc2626;
            border-color: #fee2e2;
        }

        .quantity-control.at-limit .quantity-btn:disabled {
            background-color: #fee2e2;
        }

        /* Animation for quantity changes */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .quantity-input.updated {
            animation: pulse 0.3s ease-in-out;
            background-color: #f0fdf4;
            transition: background-color 0.5s ease;
        }

        .order-summary {
            background-color: var(--color-gray-50);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .order-summary-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--color-gray-800);
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--color-gray-700);
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--color-gray-200);
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--color-gray-900);
        }

        /* Order details modal */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid var(--color-gray-200);
            padding: 1.25rem 1.5rem;
        }

        .modal-title {
            font-weight: 700;
            color: var(--color-primary);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .order-details-header {
            margin-bottom: 1.5rem;
        }

        .order-details-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .order-details-subtitle {
            color: var(--color-gray-600);
            font-size: 0.875rem;
        }

        .order-details-table th {
            font-weight: 600;
            color: var(--color-gray-700);
        }

        .order-details-table td {
            vertical-align: middle;
        }

        .order-summary {
            background-color: var(--color-gray-100);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .order-summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .order-summary-item:last-child {
            margin-bottom: 0;
            padding-top: 0.5rem;
            border-top: 1px solid var(--color-gray-300);
            font-weight: 700;
        }

        /* Empty orders state */
        .empty-orders {
            text-align: center;
            padding: 3rem 1.5rem;
        }

        .empty-orders i {
            font-size: 3rem;
            color: var(--color-gray-400);
            margin-bottom: 1rem;
        }

        .empty-orders h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-gray-700);
            margin-bottom: 0.5rem;
        }

        .empty-orders p {
            color: var(--color-gray-600);
            margin-bottom: 1.5rem;
        }

        /* Header and menu navigation styling */
        .site-header {
            background-color: #111827;
            position: relative;
            z-index: 49; /* Lower than menu-nav */
            padding: 0.75rem 0;
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo a {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
            text-decoration: none;
        }

        .logo a i {
            margin-right: 0.5rem;
            font-size: 1.75rem;
        }

        /* Main navigation styles removed */

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .user-menu {
            position: relative;
        }

        .user-icon {
            color: #ffffff;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .menu-nav {
            position: sticky;
            top: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            z-index: 40; /* Lower than dropdown menus but higher than regular content */
        }

        .menu-tabs {
            display: flex;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .menu-tabs li {
            margin: 0;
        }

        .menu-tabs a {
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
            padding: 1rem 1.5rem;
            display: block;
            position: relative;
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .menu-tabs a:hover {
            color: #111827;
        }

        .menu-tabs a.active {
            color: #111827;
            font-weight: 600;
        }

        .menu-tabs a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #f59e0b;
            border-radius: 2px 2px 0 0;
        }

        /* User dropdown styling */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: #1e293b;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            width: 200px;
            padding: 0.5rem 0;
            display: none;
            z-index: 100; /* Higher than menu-nav but lower than cart-dropdown */
        }

        .user-dropdown.show {
            display: block;
        }

        .user-dropdown ul {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .user-dropdown li a {
            display: block;
            padding: 0.75rem 1rem;
            transition: background-color 0.3s ease;
            color: #f8fafc;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            z-index: 1000;
        }

        .user-dropdown li a:hover,
        .user-dropdown li a.active {
            background-color: #334155;
            color: #ffffff;
        }

        /* Cart styling */
        .cart-menu {
            position: relative;
        }

        .cart-icon {
            position: relative;
            font-size: 1.25rem;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #f59e0b;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            background-color: #1e293b;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 0;
            z-index: 150; /* Higher than menu-nav and user-dropdown */
            display: none;
            overflow: hidden;
            margin-top: 10px;
        }

        .cart-dropdown.show {
            display: block;
        }

        .cart-dropdown-header {
            padding: 12px 15px;
            border-bottom: 1px solid #334155;
            background-color: #1e293b;
        }

        .cart-dropdown-header h6 {
            margin: 0;
            font-weight: 600;
            font-size: 0.9rem;
            color: #f8fafc;
        }

        .cart-dropdown-items {
            max-height: 300px;
            overflow-y: auto;
            padding: 0;
            background-color: #1e293b;
        }

        .cart-dropdown-loading {
            padding: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .cart-dropdown-item {
            padding: 12px 15px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-dropdown-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            background-color: #334155;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 2px;
            color: #f8fafc;
        }

        .cart-item-price {
            font-size: 0.85rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cart-item-quantity {
            background-color: #334155;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            color: #f8fafc;
        }

        .cart-dropdown-footer {
            padding: 12px 15px;
            border-top: 1px solid #334155;
            background-color: #1e293b;
        }

        .cart-dropdown-link {
            color: #f8fafc;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .cart-dropdown-link:hover {
            color: #f59e0b;
            text-decoration: underline;
        }

        .cart-empty {
            padding: 30px 15px;
            text-align: center;
            color: #94a3b8;
        }

        .cart-empty i {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #64748b;
        }

        .cart-empty p {
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) {
            .table-responsive {
                border: none;
            }

            .orders-title {
                font-size: 1.5rem;
            }

            .card-header h2 {
                font-size: 1.125rem;
            }

            .order-id {
                font-size: 0.875rem;
            }

            .order-date {
                font-size: 0.75rem;
            }

            .cart-dropdown {
                width: 300px;
                right: -50px;
            }
        }

        /* Address Modal Styles */
        #editAddressModal .modal-dialog {
            max-width: 500px;
        }

        #editAddressModal .modal-content {
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        #editAddressModal .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        #editAddressModal .form-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }

        #editAddressModal .form-control {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }

        #editAddressModal .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            color: #6c757d;
            font-size: 0.85rem;
        }

        #editAddressModal .nav-tabs .nav-link {
            color: #6c757d;
            font-size: 0.85rem;
            padding: 0.5rem;
        }

        #editAddressModal .nav-tabs .nav-link.active {
            color: #111827;
            font-weight: 500;
        }

        #editAddressModal .list-group-item {
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }

        #editAddressModal .list-group-item:hover {
            background-color: #f8f9fa;
        }

        #editAddressModal .list-group-item.active {
            background-color: #e9ecef;
            color: #111827;
            border-color: #dee2e6;
        }

        #editAddressModal .form-check-label {
            font-size: 0.9rem;
        }

        #editAddressModal .btn-primary {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }

        #editAddressModal .btn-primary:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }

        #editAddressModal .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include_once "../includes/header.php"; ?>

    <!-- Menu Navigation -->
    <div class="menu-nav">
        <div class="container">
            <ul class="menu-tabs">
                <li><a href="client.php">Menu</a></li>
                <li><a href="orders.php" class="active">My Orders</a></li>
                <li><a href="profile.php">Account Settings</a></li>
            </ul>
        </div>
    </div>



    <!-- Main Content -->
    <main class="orders-section">
        <div class="container">
            <!-- Page Header -->
            <div class="orders-header">
                <h1 class="orders-title">My Orders</h1>
                <p class="orders-subtitle">Manage your cart and view your order history</p>
            </div>

            <!-- Alert Container -->
            <div class="alert-container">
                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <i class="bi bi-check-circle-fill"></i>
                        <div class="alert-content">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div class="alert-content">
                            <?= htmlspecialchars($errorMessage) ?>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Shopping Cart Section -->
            <div class="cart-section">
                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-cart"></i> Shopping Cart</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($cartItems)): ?>
                            <!-- Empty Cart -->
                            <div class="text-center py-4">
                                <i class="bi bi-cart-x display-4 text-muted"></i>
                                <h3 class="mt-3">Your Cart is Empty</h3>
                                <p class="text-muted mb-4">Looks like you haven't added any products to your cart yet.</p>
                                <a href="client.php" class="btn btn-primary">
                                    <i class="bi bi-cup-hot me-2"></i> Browse Menu
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Cart Items -->
                            <div class="row">
                                <div class="col-lg-8">
                                    <form method="POST" action="orders.php">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="select-all-items">
                                                            </div>
                                                        </th>
                                                        <th>Product</th>
                                                        <th>Price</th>
                                                        <th>Quantity</th>
                                                        <th>Subtotal</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($cartItems as $item): ?>
                                                        <tr>
                                                            <td>
                                                                <div class="form-check">
                                                                    <input class="form-check-input item-checkbox" type="checkbox"
                                                                           name="selected_items[]"
                                                                           value="<?= $item['id'] ?>"
                                                                           id="item-<?= $item['id'] ?>"
                                                                           data-price="<?= $item['subtotal'] ?>">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($item['image'])): ?>
                                                                        <img src="../../assets/images/products/<?= htmlspecialchars($item['image']) ?>"
                                                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                                                            class="cart-item-image me-3">
                                                                    <?php else: ?>
                                                                        <div class="cart-item-placeholder me-3">
                                                                            <i class="bi bi-image"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                                                        <small class="text-muted"><?= htmlspecialchars(ucfirst($item['category'])) ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>₱<?= number_format($item['price'], 2) ?></td>
                                                            <td>
                                                                <?php
                                                                // Check if we're at the stock limit
                                                                $atStockLimit = isset($item['stock']) && $item['quantity'] >= $item['stock'];
                                                                $limitClass = $atStockLimit ? 'at-limit' : '';
                                                                ?>
                                                                <div class="quantity-control <?= $limitClass ?>">
                                                                    <button type="button"
                                                                            class="quantity-btn"
                                                                            data-action="decrease"
                                                                            data-id="<?= $item['id'] ?>"
                                                                            aria-label="Decrease quantity">
                                                                        <i class="bi bi-dash"></i>
                                                                    </button>
                                                                    <input type="number"
                                                                           name="quantity[<?= $item['id'] ?>]"
                                                                           value="<?= $item['quantity'] ?>"
                                                                           min="1"
                                                                           max="<?= isset($item['stock']) ? $item['stock'] : 99 ?>"
                                                                           class="form-control quantity-input"
                                                                           aria-label="Product quantity">
                                                                    <button type="button"
                                                                            class="quantity-btn"
                                                                            data-action="increase"
                                                                            data-id="<?= $item['id'] ?>"
                                                                            <?= $atStockLimit ? 'disabled' : '' ?>
                                                                            aria-label="Increase quantity">
                                                                        <i class="bi bi-plus"></i>
                                                                    </button>
                                                                </div>
                                                                <?php if (isset($item['stock'])): ?>
                                                                <div class="mt-1">
                                                                    <small class="text-muted">Stock: <?= $item['stock'] ?></small>
                                                                </div>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>₱<?= number_format($item['subtotal'], 2) ?></td>
                                                            <td>
                                                                <button type="submit" name="remove_item" value="<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" formaction="orders.php">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex justify-content-end mt-3">
                                            <a href="client.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-plus-circle"></i> Add More Items
                                            </a>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-4">
                                    <div class="order-summary">
                                        <div class="card mb-3">
                                            <div class="card-body p-3">
                                                <h6 class="card-title mb-3">Order Summary</h6>

                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">Subtotal</span>
                                                    <span id="summary-subtotal">₱<?= number_format($totalAmount, 2) ?></span>
                                                </div>

                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">Delivery Fee</span>
                                                    <span id="delivery-fee">₱0.00</span>
                                                </div>

                                                <hr class="my-2">

                                                <div class="d-flex justify-content-between mb-3 fw-bold">
                                                    <span>Total</span>
                                                    <span id="summary-total">₱<?= number_format($totalAmount, 2) ?></span>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="delivery-option-select" class="form-label">Delivery Option</label>
                                                    <select class="form-select form-select-sm" id="delivery-option-select">
                                                        <option value="pickup" selected>In-store Pickup (Free)</option>
                                                        <option value="delivery">Home Delivery (₱50.00)</option>
                                                    </select>
                                                </div>

                                                <div class="mb-2">
                                                    <label for="payment-method-select" class="form-label">Payment Method</label>
                                                    <select class="form-select form-select-sm" id="payment-method-select">
                                                        <option value="cod" selected>Cash on Delivery (COD)</option>
                                                        <option value="gcash">GCash</option>
                                                        <option value="maya">Maya</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info py-2 mb-3" role="alert" style="font-size: 0.85rem;">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Use checkboxes to select items for your order
                                        </div>

                                        <div class="mt-4">
                                            <form method="POST" action="orders.php" id="checkout-form">
                                                <!-- Hidden fields to store selected items -->
                                                <input type="hidden" name="selected_items_json" id="selected-items-json" value="">
                                                <input type="hidden" name="delivery_option" id="delivery-option-input" value="pickup">
                                                <input type="hidden" name="payment_method" id="payment-method-input" value="cod">

                                                <button type="submit" name="checkout" id="checkout-button" class="btn btn-primary w-100">
                                                    <i class="bi bi-credit-card me-2"></i> Proceed to Checkout
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order History Section -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="bi bi-clock-history"></i> Order History</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <!-- Empty Orders State -->
                        <div class="empty-orders">
                            <i class="bi bi-bag-x"></i>
                            <h3>No Orders Yet</h3>
                            <p>You haven't placed any orders yet. Browse our menu to place your first order!</p>
                            <a href="client.php" class="btn btn-primary">
                                <i class="bi bi-cup-hot me-2"></i> Browse Menu
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Orders Table -->
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Items</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>
                                                <span class="order-id">#<?= htmlspecialchars($order['id']) ?></span>
                                            </td>
                                            <td>
                                                <div class="order-date">
                                                    <?= date('M d, Y', strtotime($order['order_date'] ?? $order['created_at'])) ?><br>
                                                    <small><?= date('h:i A', strtotime($order['order_date'] ?? $order['created_at'])) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="order-total">₱<?= number_format($order['total_price'] ?? $order['total'], 2) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                switch (strtolower($order['status'])) {
                                                    case 'completed':
                                                        $statusClass = 'bg-success';
                                                        break;
                                                    case 'processing':
                                                        $statusClass = 'bg-info';
                                                        break;
                                                    case 'pending':
                                                        $statusClass = 'bg-warning';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'bg-danger';
                                                        break;
                                                    default:
                                                        $statusClass = 'bg-secondary';
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?>">
                                                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                // In a real implementation, you would count items from the orders_items table
                                                // For now, we'll use a placeholder or the 'items' field if it exists
                                                $itemCount = $order['items'] ?? '?';

                                                // Try to get the actual count from the database if possible
                                                try {
                                                    $itemStmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
                                                    $itemStmt->execute([$order['id']]);
                                                    $itemCount = $itemStmt->fetchColumn();
                                                } catch (PDOException $e) {
                                                    // Silently fail and use the default value
                                                }
                                                ?>
                                                <span class="order-items"><?= htmlspecialchars($itemCount) ?> items</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary view-order-btn" data-bs-toggle="modal" data-bs-target="#orderDetailsModal" data-order-id="<?= $order['id'] ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailsContent">
                        <!-- Order details will be loaded here -->
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Loading order details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- We're using the address modal from profile.php instead of duplicating it here -->
    <?php include_once('../includes/address-modal.php'); ?>


    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="bi bi-cup-hot"></i> Brew & Bake
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>About Us</h4>
                        <ul>
                            <li><a href="client.php#about">Our Story</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Social Impact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Customer Service</h4>
                        <ul>
                            <li><a href="client.php#contact">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Store Locator</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="../../index.php">Home</a></li>
                            <li><a href="client.php">Menu</a></li>
                            <li><a href="orders.php">My Orders</a></li>
                            <li><a href="profile.php">Account Settings</a></li>
                            <li><a href="../includes/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Brew & Bake. All rights reserved.</p>
                <div class="social-links">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <?php
    $root_path = '../../';
    include_once "../../templates/includes/footer-scripts.php";
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make menu-nav sticky on scroll
            const menuNav = document.querySelector('.menu-nav');
            const siteHeader = document.querySelector('.site-header');

            if (menuNav && siteHeader) {
                // Get the height of the site header
                const headerHeight = siteHeader.offsetHeight;

                // Initial check on page load
                if (window.scrollY > headerHeight) {
                    menuNav.classList.add('scrolled');
                }

                // Check on scroll
                window.addEventListener('scroll', function() {
                    if (window.scrollY > headerHeight) {
                        menuNav.classList.add('scrolled');
                    } else {
                        menuNav.classList.remove('scrolled');
                    }
                });
            }

            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }, 5000);
            });

            // Handle quantity buttons in shopping cart with automatic update
            const quantityBtns = document.querySelectorAll('.quantity-btn');
            const cartForm = document.querySelector('form[action="orders.php"]');

            // Function to show updating indicator
            function showUpdatingIndicator() {
                // Remove any existing indicator
                const existingIndicator = document.getElementById('cart-updating-indicator');
                if (existingIndicator && existingIndicator.parentNode) {
                    existingIndicator.parentNode.removeChild(existingIndicator);
                }

                // Create new indicator
                const indicator = document.createElement('div');
                indicator.id = 'cart-updating-indicator';
                indicator.innerHTML = `
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Updating...</span>
                `;

                // Add to body instead of alert container for less intrusive display
                document.body.appendChild(indicator);

                return indicator;
            }

            // Function to automatically update cart
            function updateCart(input) {
                if (cartForm) {
                    // Show updating indicator
                    const indicator = showUpdatingIndicator();

                    // Create a hidden input for update_cart
                    let updateInput = document.querySelector('input[name="auto_update_cart"]');
                    if (!updateInput) {
                        updateInput = document.createElement('input');
                        updateInput.type = 'hidden';
                        updateInput.name = 'update_cart';
                        updateInput.value = '1';
                        cartForm.appendChild(updateInput);
                    }

                    // Submit the form
                    const formData = new FormData(cartForm);

                    fetch('orders.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Parse the HTML to get the updated cart content
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        // Update the cart section
                        const newCartSection = doc.querySelector('.cart-section');
                        const currentCartSection = document.querySelector('.cart-section');
                        if (newCartSection && currentCartSection) {
                            currentCartSection.innerHTML = newCartSection.innerHTML;

                            // Reattach event listeners to the new buttons
                            attachQuantityButtonListeners();
                        }

                        // Update the order summary if it exists
                        const newOrderSummary = doc.querySelector('.order-summary');
                        const currentOrderSummary = document.querySelector('.order-summary');
                        if (newOrderSummary && currentOrderSummary) {
                            currentOrderSummary.innerHTML = newOrderSummary.innerHTML;
                        }

                        // Show success indicator
                        if (indicator && indicator.parentNode) {
                            indicator.className = indicator.className + ' success';
                            indicator.innerHTML = `
                                <i class="bi bi-check-circle"></i>
                                <span>Updated</span>
                            `;

                            // Remove the indicator after a short delay
                            setTimeout(() => {
                                if (indicator && indicator.parentNode) {
                                    indicator.parentNode.removeChild(indicator);
                                }
                            }, 1000);
                        }
                    })
                    .catch(error => {
                        console.error('Error updating cart:', error);
                        // Show error message
                        if (indicator && indicator.parentNode) {
                            indicator.className = indicator.className + ' error';
                            indicator.innerHTML = `
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Failed</span>
                            `;

                            // Remove the indicator after a delay
                            setTimeout(() => {
                                if (indicator && indicator.parentNode) {
                                    indicator.parentNode.removeChild(indicator);
                                }
                            }, 2000);
                        }
                    });
                }
            }

            // Function to attach event listeners to quantity buttons
            function attachQuantityButtonListeners() {
                const quantityBtns = document.querySelectorAll('.quantity-btn');
                const quantityInputs = document.querySelectorAll('.quantity-input');

                quantityBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        if (this.disabled) return;

                        const action = this.dataset.action;
                        const productId = this.dataset.id;
                        const input = document.querySelector(`input[name="quantity[${productId}]"]`);
                        let value = parseInt(input.value);
                        const max = parseInt(input.getAttribute('max')) || 99;

                        // Get the quantity control container
                        const quantityControl = this.closest('.quantity-control');

                        if (action === 'increase') {
                            if (value < max) {
                                value = Math.min(value + 1, max);

                                // Check if we've reached the max (stock limit)
                                if (value >= max) {
                                    // Add visual indicator
                                    if (quantityControl) {
                                        quantityControl.classList.add('at-limit');
                                    }

                                    // Disable the increase button
                                    this.disabled = true;
                                }
                            }
                        } else if (action === 'decrease') {
                            if (value > 1) {
                                value = Math.max(value - 1, 1);

                                // If we were at the limit but now we're not
                                if (value < max && quantityControl) {
                                    quantityControl.classList.remove('at-limit');

                                    // Re-enable the increase button
                                    const increaseBtn = quantityControl.querySelector('[data-action="increase"]');
                                    if (increaseBtn) {
                                        increaseBtn.disabled = false;
                                    }
                                }
                            }
                        }

                        input.value = value;

                        // Add visual feedback for the button press
                        this.classList.add('active');
                        setTimeout(() => {
                            this.classList.remove('active');
                        }, 200);

                        // Add animation to the input
                        input.classList.add('updated');
                        setTimeout(() => {
                            input.classList.remove('updated');
                        }, 500);

                        // Automatically update the cart
                        updateCart(input);
                    });
                });

                // Also update when input value changes directly
                quantityInputs.forEach(input => {
                    input.addEventListener('change', function() {
                        // Get the quantity control container
                        const quantityControl = this.closest('.quantity-control');

                        // Get max value (stock limit)
                        const max = parseInt(this.getAttribute('max')) || 99;

                        // Ensure value is within valid range
                        let value = parseInt(this.value);
                        if (isNaN(value) || value < 1) {
                            value = 1;
                        } else if (value > max) {
                            value = max;
                        }
                        this.value = value;

                        // Update visual indicators based on the new value
                        if (quantityControl) {
                            if (value >= max) {
                                quantityControl.classList.add('at-limit');
                                const increaseBtn = quantityControl.querySelector('[data-action="increase"]');
                                if (increaseBtn) {
                                    increaseBtn.disabled = true;
                                }
                            } else {
                                quantityControl.classList.remove('at-limit');
                                const increaseBtn = quantityControl.querySelector('[data-action="increase"]');
                                if (increaseBtn) {
                                    increaseBtn.disabled = false;
                                }
                            }
                        }

                        // Add animation to the input
                        this.classList.add('updated');
                        setTimeout(() => {
                            this.classList.remove('updated');
                        }, 500);

                        // Automatically update the cart
                        updateCart(this);
                    });

                    // Prevent manual typing of invalid values
                    input.addEventListener('keydown', function(e) {
                        // Allow: backspace, delete, tab, escape, enter, and numbers
                        if ([46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
                            // Allow: Ctrl+A, Ctrl+C, Ctrl+V
                            (e.keyCode === 65 && e.ctrlKey === true) ||
                            (e.keyCode === 67 && e.ctrlKey === true) ||
                            (e.keyCode === 86 && e.ctrlKey === true) ||
                            // Allow: home, end, left, right
                            (e.keyCode >= 35 && e.keyCode <= 39) ||
                            // Allow numbers
                            (e.keyCode >= 48 && e.keyCode <= 57) ||
                            (e.keyCode >= 96 && e.keyCode <= 105)) {
                            return;
                        }
                        e.preventDefault();
                    });
                });
            }

            // Initial attachment of event listeners
            attachQuantityButtonListeners();

            // Handle item selection checkboxes for order summary
            const selectAllCheckbox = document.getElementById('select-all-items');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            const deliveryOptionSelect = document.getElementById('delivery-option-select');
            const paymentMethodSelect = document.getElementById('payment-method-select');
            const summarySubtotalElement = document.getElementById('summary-subtotal');
            const summaryTotalElement = document.getElementById('summary-total');
            const deliveryFeeElement = document.getElementById('delivery-fee');
            const selectedItemsJsonInput = document.getElementById('selected-items-json');
            const deliveryOptionInput = document.getElementById('delivery-option-input');
            const paymentMethodInput = document.getElementById('payment-method-input');
            const checkoutForm = document.getElementById('checkout-form');

            // Function to update the order summary based on selected items
            function updateOrderSummary() {
                // Calculate subtotal based on selected items
                let subtotal = 0;
                const selectedItems = [];

                itemCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        subtotal += parseFloat(checkbox.dataset.price);
                        selectedItems.push(checkbox.value);
                    }
                });

                // Format subtotal
                const formattedSubtotal = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP',
                    minimumFractionDigits: 2
                }).format(subtotal).replace('PHP', '₱');

                // Update subtotal display
                if (summarySubtotalElement) {
                    summarySubtotalElement.textContent = formattedSubtotal;
                }

                // Get selected delivery option
                let deliveryOption = deliveryOptionSelect ? deliveryOptionSelect.value : 'pickup';

                // Calculate total (with or without delivery fee)
                let total = subtotal;
                let deliveryFee = 0;

                if (deliveryOption === 'delivery') {
                    deliveryFee = 50; // Add delivery fee only for home delivery
                    total += deliveryFee;
                }

                // Update delivery fee display
                if (deliveryFeeElement) {
                    deliveryFeeElement.textContent = '₱' + deliveryFee.toFixed(2);
                }

                // Format total
                const formattedTotal = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP',
                    minimumFractionDigits: 2
                }).format(total).replace('PHP', '₱');

                // Update total display
                if (summaryTotalElement) {
                    summaryTotalElement.textContent = formattedTotal;
                }

                // Update hidden input with selected items
                if (selectedItemsJsonInput) {
                    selectedItemsJsonInput.value = JSON.stringify(selectedItems);
                }

                // Update delivery option input
                if (deliveryOptionInput) {
                    deliveryOptionInput.value = deliveryOption;
                }

                // Get selected payment method
                let paymentMethod = paymentMethodSelect ? paymentMethodSelect.value : 'cod';

                // Update payment method input
                if (paymentMethodInput) {
                    paymentMethodInput.value = paymentMethod;
                }

                // Update checkout button text based on payment method
                const checkoutButton = document.getElementById('checkout-button');
                if (checkoutButton) {
                    let buttonIcon = '<i class="bi bi-credit-card me-2"></i>';
                    let buttonText = 'Proceed to Checkout';

                    switch (paymentMethod) {
                        case 'gcash':
                            buttonIcon = '<i class="bi bi-wallet2 me-2"></i>';
                            buttonText = 'Pay with GCash';
                            break;
                        case 'maya':
                            buttonIcon = '<i class="bi bi-credit-card me-2"></i>';
                            buttonText = 'Pay with Maya';
                            break;
                        case 'cod':
                        default:
                            buttonIcon = '<i class="bi bi-cash-coin me-2"></i>';
                            buttonText = 'Place Order (COD)';
                            break;
                    }

                    checkoutButton.innerHTML = buttonIcon + buttonText;
                }

                // Disable checkout button if no items selected
                if (checkoutForm) {
                    const checkoutButton = checkoutForm.querySelector('button[name="checkout"]');
                    if (checkoutButton) {
                        checkoutButton.disabled = selectedItems.length === 0;

                        if (selectedItems.length === 0) {
                            checkoutButton.classList.add('btn-secondary');
                            checkoutButton.classList.remove('btn-primary');
                        } else {
                            checkoutButton.classList.add('btn-primary');
                            checkoutButton.classList.remove('btn-secondary');
                        }
                    }
                }
            }

            // Add event listeners to checkboxes
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;

                    // Update all item checkboxes
                    itemCheckboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });

                    // Update order summary
                    updateOrderSummary();
                });
            }

            // Add event listeners to individual item checkboxes
            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);

                    // Update select all checkbox
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                    }

                    // Update order summary
                    updateOrderSummary();
                });
            });

            // Add event listener to delivery option select
            if (deliveryOptionSelect) {
                deliveryOptionSelect.addEventListener('change', function() {
                    updateOrderSummary();
                });
            }

            // Add event listener to payment method select
            if (paymentMethodSelect) {
                paymentMethodSelect.addEventListener('change', function() {
                    updateOrderSummary();
                });
            }

            // Initialize order summary
            updateOrderSummary();

            // Handle order details modal
            const orderDetailsModal = document.getElementById('orderDetailsModal');
            const orderDetailsContent = document.getElementById('orderDetailsContent');
            const viewOrderButtons = document.querySelectorAll('.view-order-btn');

            if (orderDetailsModal) {
                orderDetailsModal.addEventListener('show.bs.modal', function(event) {
                    // Get the button that triggered the modal
                    const button = event.relatedTarget;

                    // Extract order ID from data attribute
                    const orderId = button.getAttribute('data-order-id');

                    // Show loading spinner
                    orderDetailsContent.innerHTML = `
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Loading order details...</p>
                        </div>
                    `;

                    // Fetch order details using AJAX
                    fetch(`get_order_details.php?id=${orderId}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Generate order details HTML
                            const orderDetails = generateOrderDetails(orderId, data);

                            // Update modal content
                            orderDetailsContent.innerHTML = orderDetails;

                            // Initialize edit address button
                            initEditAddressButton(data);
                        })
                        .catch(error => {
                            console.error('Error fetching order details:', error);

                            // If there's an error, fall back to sample data
                            const orderDetails = generateOrderDetails(orderId);

                            // Update modal content
                            orderDetailsContent.innerHTML = orderDetails;

                            // Initialize edit address button with sample data
                            initEditAddressButton();
                        });
                });
            }

            // Function to generate order details HTML
            function generateOrderDetails(orderId, data = null) {
                // If no data is provided, use sample data
                const orderData = data || {
                    id: orderId,
                    order_date: 'June 15, 2025 14:30:00',
                    status: 'Completed',
                    total_price: 625.00,
                    payment_status: 'Paid',
                    items: [
                        { name: 'Kapeng Barako', price: 150.00, quantity: 2, total_price: 300.00 },
                        { name: 'Ube Cheese Pandesal', price: 35.00, quantity: 3, total_price: 105.00 },
                        { name: 'Ensaymada', price: 85.00, quantity: 2, total_price: 170.00 }
                    ],
                    subtotal: 575.00,
                    shipping: 50.00,
                    address: '123 Sample Street, Barangay Sample, Manila, Philippines',
                    payment_method: 'Cash on Delivery'
                };

                // Format date and time
                const orderDate = new Date(orderData.order_date);
                const formattedDate = orderDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                const formattedTime = orderDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });

                // Format payment date if available
                let formattedPaymentDate = '';
                let formattedPaymentTime = '';
                if (orderData.payment_transaction_date) {
                    const paymentDate = new Date(orderData.payment_transaction_date);
                    formattedPaymentDate = paymentDate.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                    formattedPaymentTime = paymentDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
                }

                // Determine status badge class
                let statusClass = 'bg-secondary';
                switch (String(orderData.status).toLowerCase()) {
                    case 'completed':
                        statusClass = 'bg-success';
                        break;
                    case 'processing':
                        statusClass = 'bg-info';
                        break;
                    case 'pending':
                        statusClass = 'bg-warning';
                        break;
                    case 'cancelled':
                        statusClass = 'bg-danger';
                        break;
                }

                // Determine payment status badge class
                let paymentStatusClass = 'bg-secondary';
                switch (String(orderData.payment_status).toLowerCase()) {
                    case 'paid':
                        paymentStatusClass = 'bg-success';
                        break;
                    case 'unpaid':
                        paymentStatusClass = 'bg-warning';
                        break;
                }

                // Format payment method for display
                const paymentMethodDisplay = {
                    'cash': 'Cash',
                    'credit_card': 'Credit Card',
                    'gcash': 'GCash',
                    'bank_transfer': 'Bank Transfer'
                };

                // Generate HTML for order details
                return `
                    <div class="order-details-header">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h4 class="order-details-title">Order #${orderData.id}</h4>
                            <span class="badge ${statusClass}">${orderData.status}</span>
                        </div>
                        <p class="order-details-subtitle">Placed on ${formattedDate} at ${formattedTime}</p>
                    </div>

                    <h6 class="text-muted mb-3">Order Items</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm order-details-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${orderData.items.map(item => `
                                    <tr>
                                        <td>${item.name}</td>
                                        <td>₱${parseFloat(item.price).toFixed(2)}</td>
                                        <td>${item.quantity}</td>
                                        <td class="text-end">₱${parseFloat(item.total_price || (item.price * item.quantity)).toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6 class="text-muted mb-3">Order Information</h6>
                            ${orderData.address ? `
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <p class="mb-0"><strong>Delivery Address:</strong></p>
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-address-btn" data-bs-toggle="modal" data-bs-target="#editAddressModal">
                                        <i class="bi bi-pencil-square me-1"></i>Edit
                                    </button>
                                </div>
                                <p class="mb-3">${orderData.address}</p>
                            ` : ''}
                            <p class="mb-1"><strong>Payment Status:</strong></p>
                            <p><span class="badge ${paymentStatusClass}">${orderData.payment_status ? orderData.payment_status.charAt(0).toUpperCase() + orderData.payment_status.slice(1) : 'Not specified'}</span></p>

                            <p class="mb-1"><strong>Payment Method:</strong></p>
                            <p>${orderData.payment_method ? paymentMethodDisplay[orderData.payment_method] || orderData.payment_method : 'Not specified'}</p>

                            ${orderData.payment_transaction_date ? `
                                <p class="mb-1"><strong>Payment Date:</strong></p>
                                <p>${formattedPaymentDate} at ${formattedPaymentTime}</p>
                            ` : ''}
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-3">Order Summary</h6>
                            <div class="order-summary">
                                <div class="order-summary-item">
                                    <span>Subtotal:</span>
                                    <span>₱${parseFloat(orderData.subtotal || (orderData.total_price - (orderData.shipping || 0))).toFixed(2)}</span>
                                </div>
                                ${orderData.shipping ? `
                                    <div class="order-summary-item">
                                        <span>Shipping:</span>
                                        <span>₱${parseFloat(orderData.shipping).toFixed(2)}</span>
                                    </div>
                                ` : ''}
                                <div class="order-summary-item">
                                    <span>Total:</span>
                                    <span>₱${parseFloat(orderData.total_price).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            /**
             * Initialize the edit address button functionality
             * @param {Object} orderData - The order data containing address information
             */
            function initEditAddressButton(orderData = null) {
                // Get the edit address button
                const editAddressBtn = document.querySelector('.edit-address-btn');
                if (!editAddressBtn) return;

                // Default sample data if no order data is provided
                const defaultData = {
                    full_name: 'Juan Dela Cruz',
                    phone: '9051234567',
                    address: '123 Sample Street, Barangay Sample, Manila, Philippines',
                    address_type: 'Home',
                    latitude: 14.5995,
                    longitude: 120.9842
                };

                // Use provided order data or default data
                const data = orderData || defaultData;

                // Add click event listener to the edit address button
                editAddressBtn.addEventListener('click', function() {
                    // We'll use the address modal from profile.php
                    // This will be handled by including the address-modal.js file

                    // Store the order data in sessionStorage to be used by the address modal
                    sessionStorage.setItem('addressData', JSON.stringify(data));

                    // The modal will be shown via data-bs-toggle="modal" data-bs-target="#address-modal"
                });
            }
        });
    </script>
</body>
</html>
