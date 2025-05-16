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

// Get user information
$userId = $_SESSION['user_id'];
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching user data: " . $e->getMessage();
    $user = [];
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle adding products to cart
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $productId = (int)$_GET['add'];

    // Check if product exists and is active
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Add to cart or increment quantity
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]++;
            } else {
                $_SESSION['cart'][$productId] = 1;
            }

            $successMessage = "Product added to your cart!";

            // Redirect to remove the GET parameter
            header("Location: orders.php");
            exit;
        } else {
            $errorMessage = "Product not found or unavailable.";
        }
    } catch (PDOException $e) {
        $errorMessage = "Error adding product to cart: " . $e->getMessage();
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

// Get user orders
try {
    // Check if orders table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
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

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
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
            max-width: 120px;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border-radius: 0;
            border-left: 0;
            border-right: 0;
        }

        .quantity-btn {
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .quantity-btn:first-child {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .quantity-btn:last-child {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
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

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

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
                                                                <div class="quantity-control">
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" data-action="decrease" data-id="<?= $item['id'] ?>">-</button>
                                                                    <input type="number" name="quantity[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="1" max="99" class="form-control quantity-input">
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" data-action="increase" data-id="<?= $item['id'] ?>">+</button>
                                                                </div>
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
                                        <div class="d-flex justify-content-between mt-3">
                                            <button type="submit" name="update_cart" class="btn btn-outline-primary" formaction="orders.php">
                                                <i class="bi bi-arrow-repeat"></i> Update Cart
                                            </button>
                                            <a href="client.php" class="btn btn-outline-secondary">
                                                <i class="bi bi-plus-circle"></i> Add More Items
                                            </a>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-4">
                                    <div class="order-summary">
                                        <h3 class="order-summary-title">Order Summary</h3>
                                        <div class="summary-item">
                                            <span>Subtotal</span>
                                            <span>₱<?= number_format($totalAmount, 2) ?></span>
                                        </div>
                                        <div class="summary-item">
                                            <span>Delivery Fee</span>
                                            <span>₱50.00</span>
                                        </div>
                                        <div class="summary-total">
                                            <span>Total</span>
                                            <span>₱<?= number_format($totalAmount + 50, 2) ?></span>
                                        </div>
                                        <div class="mt-4">
                                            <form method="POST" action="orders.php">
                                                <button type="submit" name="checkout" class="btn btn-primary w-100">
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
                                                    $itemStmt = $conn->prepare("SELECT COUNT(*) FROM orders_items WHERE order_id = ?");
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

            // Handle quantity buttons in shopping cart
            const quantityBtns = document.querySelectorAll('.quantity-btn');
            quantityBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const action = this.dataset.action;
                    const productId = this.dataset.id;
                    const input = document.querySelector(`input[name="quantity[${productId}]"]`);
                    let value = parseInt(input.value);

                    if (action === 'increase') {
                        value = Math.min(value + 1, 99);
                    } else if (action === 'decrease') {
                        value = Math.max(value - 1, 1);
                    }

                    input.value = value;
                });
            });

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
                        })
                        .catch(error => {
                            console.error('Error fetching order details:', error);

                            // If there's an error, fall back to sample data
                            const orderDetails = generateOrderDetails(orderId);

                            // Update modal content
                            orderDetailsContent.innerHTML = orderDetails;
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
                                <p class="mb-1"><strong>Address:</strong></p>
                                <p class="mb-3">${orderData.address}</p>
                            ` : ''}
                            <p class="mb-1"><strong>Payment Status:</strong></p>
                            <p>${orderData.payment_status || 'Not specified'}</p>
                            ${orderData.payment_method ? `
                                <p class="mb-1"><strong>Payment Method:</strong></p>
                                <p>${orderData.payment_method}</p>
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
        });
    </script>
</body>
</html>
