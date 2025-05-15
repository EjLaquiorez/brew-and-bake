<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'client') {
    $_SESSION['error'] = "Access denied. Client privileges required.";
    header("Location: ../../views/login.php");
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

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
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

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quantity
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $productId => $quantity) {
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = (int)$quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        }
        $successMessage = "Cart updated successfully!";
    }
    
    // Remove item
    if (isset($_POST['remove_item'])) {
        $productId = $_POST['remove_item'];
        if (isset($_SESSION['cart'][$productId])) {
            unset($_SESSION['cart'][$productId]);
            $successMessage = "Item removed from cart!";
        }
    }
    
    // Clear cart
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = [];
        $successMessage = "Cart cleared successfully!";
    }
    
    // Checkout
    if (isset($_POST['checkout'])) {
        if (empty($_SESSION['cart'])) {
            $errorMessage = "Your cart is empty. Add some products before checkout.";
        } else {
            // Redirect to checkout page
            header("Location: checkout.php");
            exit;
        }
    }
}

// Get cart items
$cartItems = [];
$totalAmount = 0;

if (!empty($_SESSION['cart'])) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Shopping Cart - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/client.css?v=<?= time() ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="client.php">
                <i class="bi bi-cup-hot"></i> Brew & Bake
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="client.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../views/products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="cart.php">
                            <i class="bi bi-cart"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../includes/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="mb-4">
            <h1 class="mb-1">Shopping Cart</h1>
            <p class="text-muted mb-0">Review and update your items before checkout</p>
        </div>

        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-cart-x display-4 text-muted"></i>
                    <h3 class="mt-3">Your Cart is Empty</h3>
                    <p class="text-muted mb-4">Looks like you haven't added any products to your cart yet.</p>
                    <a href="../../views/products.php" class="btn btn-primary">
                        <i class="bi bi-bag-plus"></i> Browse Products
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="mb-0"><i class="bi bi-cart"></i> Cart Items</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
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
                                                        <button type="submit" name="remove_item" value="<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="submit" name="update_cart" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-repeat"></i> Update Cart
                                    </button>
                                    <button type="submit" name="clear_cart" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to clear your cart?')">
                                        <i class="bi bi-trash"></i> Clear Cart
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h2>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>₱<?= number_format($totalAmount, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee:</span>
                                <span>₱50.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total:</strong>
                                <strong>₱<?= number_format($totalAmount + 50, 2) ?></strong>
                            </div>
                            <form method="POST" action="">
                                <button type="submit" name="checkout" class="btn btn-primary w-100">
                                    <i class="bi bi-credit-card"></i> Proceed to Checkout
                                </button>
                            </form>
                            <div class="mt-3">
                                <a href="../../views/products.php" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-bag-plus"></i> Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h3><i class="bi bi-cup-hot"></i> Brew & Bake</h3>
                    <p>Experience the perfect blend of coffee and bakery in a warm, welcoming atmosphere.</p>
                </div>
                <div class="col-lg-4">
                    <h4>Quick Links</h4>
                    <ul class="list-unstyled">
                        <li><a href="client.php">Dashboard</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                        <li><a href="../../views/products.php">Products</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h4>Contact Us</h4>
                    <p><i class="bi bi-geo-alt"></i> 123 Coffee Street, Manila, Philippines</p>
                    <p><i class="bi bi-telephone"></i> +63 912 345 6789</p>
                    <p><i class="bi bi-envelope"></i> info@brewandbake.com</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Brew & Bake. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quantity control buttons
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
        });
    </script>
</body>
</html>
