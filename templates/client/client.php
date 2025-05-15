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

// Get recent orders
try {
    $stmt = $conn->prepare("
        SELECT o.*, COUNT(oi.id) as item_count, SUM(oi.quantity) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching orders: " . $e->getMessage();
    $recentOrders = [];
}

// Get favorite products (most ordered)
try {
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, COUNT(oi.id) as order_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.user_id = ? AND p.status = 'active'
        GROUP BY p.id
        ORDER BY order_count DESC
        LIMIT 4
    ");
    $stmt->execute([$userId]);
    $favoriteProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching favorite products: " . $e->getMessage();
    $favoriteProducts = [];
}

// Get recommended products (based on categories user has ordered from)
try {
    $stmt = $conn->prepare("
        SELECT DISTINCT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id IN (
            SELECT DISTINCT p2.category_id
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p2 ON oi.product_id = p2.id
            WHERE o.user_id = ?
        )
        AND p.id NOT IN (
            SELECT oi.product_id
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ?
        )
        AND p.status = 'active'
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt->execute([$userId, $userId]);
    $recommendedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching recommended products: " . $e->getMessage();
    $recommendedProducts = [];
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
    <title>Client Dashboard - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/menu.css?v=<?= time() ?>">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="client.php">
                        <i class="bi bi-cup-hot"></i> Brew & Bake
                    </a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="menu.php">MENU</a></li>
                        <li><a href="rewards.php">REWARDS</a></li>
                        <li><a href="gift-cards.php">GIFT CARDS</a></li>
                    </ul>
                </nav>
                <div class="header-actions">
                    <a href="cart.php" class="cart-icon">
                        <i class="bi bi-cart"></i>
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="user-menu">
                        <a href="profile.php" class="user-icon">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <div class="user-dropdown">
                            <ul>
                                <li><a href="client.php">Dashboard</a></li>
                                <li><a href="orders.php">My Orders</a></li>
                                <li><a href="profile.php">Profile</a></li>
                                <li><a href="../includes/logout.php">Sign Out</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

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

        <!-- Welcome Section -->
        <div class="welcome-section mb-5">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>Welcome, <?= htmlspecialchars($user['name'] ?? 'Client') ?>!</h1>
                    <p class="lead">Explore your dashboard to manage orders, view your favorite products, and discover new items.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="../../views/products.php" class="btn btn-primary">
                        <i class="bi bi-bag-plus"></i> Shop Now
                    </a>
                    <a href="orders.php" class="btn btn-outline-primary ms-2">
                        <i class="bi bi-receipt"></i> My Orders
                    </a>
                </div>
            </div>
        </div>

        <!-- Dashboard Widgets -->
        <div class="row mb-5">
            <div class="col-md-4 mb-4">
                <div class="dashboard-widget">
                    <div class="widget-icon">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div class="widget-content">
                        <h3><?= count($recentOrders) ?></h3>
                        <p>Recent Orders</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="dashboard-widget">
                    <div class="widget-icon">
                        <i class="bi bi-heart"></i>
                    </div>
                    <div class="widget-content">
                        <h3><?= count($favoriteProducts) ?></h3>
                        <p>Favorite Products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="dashboard-widget">
                    <div class="widget-icon">
                        <i class="bi bi-star"></i>
                    </div>
                    <div class="widget-content">
                        <h3><?= count($recommendedProducts) ?></h3>
                        <p>Recommendations</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card mb-5">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="mb-0"><i class="bi bi-receipt"></i> Recent Orders</h2>
                <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentOrders)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-receipt-cutoff display-4 text-muted"></i>
                        <p class="mt-3">You haven't placed any orders yet.</p>
                        <a href="../../views/products.php" class="btn btn-primary">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($order['id']) ?></td>
                                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($order['total_items']) ?> items</td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Favorite Products -->
        <h2 class="mb-4"><i class="bi bi-heart"></i> Your Favorite Products</h2>
        <div class="row mb-5">
            <?php if (empty($favoriteProducts)): ?>
                <div class="col-12 text-center py-4">
                    <i class="bi bi-heart display-4 text-muted"></i>
                    <p class="mt-3">You don't have any favorite products yet.</p>
                    <a href="../../views/products.php" class="btn btn-primary">Explore Products</a>
                </div>
            <?php else: ?>
                <?php foreach ($favoriteProducts as $product): ?>
                    <div class="col-md-3 mb-4">
                        <div class="product-card">
                            <?php if (!empty($product['image'])): ?>
                                <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     class="product-image">
                            <?php endif; ?>
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 60)) . (strlen($product['description']) > 60 ? '...' : '') ?></p>
                                <div class="product-meta">
                                    <span class="category"><?= htmlspecialchars(ucfirst($product['category_name'] ?? 'Uncategorized')) ?></span>
                                    <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                </div>
                                <button class="btn btn-primary w-100 mt-3" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Recommended Products -->
        <h2 class="mb-4"><i class="bi bi-star"></i> Recommended For You</h2>
        <div class="row">
            <?php if (empty($recommendedProducts)): ?>
                <div class="col-12 text-center py-4">
                    <i class="bi bi-star display-4 text-muted"></i>
                    <p class="mt-3">We don't have any recommendations for you yet.</p>
                    <a href="../../views/products.php" class="btn btn-primary">Explore Products</a>
                </div>
            <?php else: ?>
                <?php foreach ($recommendedProducts as $product): ?>
                    <div class="col-md-3 mb-4">
                        <div class="product-card">
                            <?php if (!empty($product['image'])): ?>
                                <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     class="product-image">
                            <?php endif; ?>
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="text-muted"><?= htmlspecialchars(substr($product['description'], 0, 60)) . (strlen($product['description']) > 60 ? '...' : '') ?></p>
                                <div class="product-meta">
                                    <span class="category"><?= htmlspecialchars(ucfirst($product['category_name'] ?? 'Uncategorized')) ?></span>
                                    <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                </div>
                                <button class="btn btn-primary w-100 mt-3" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
                            <li><a href="#">Our Story</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Social Impact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Customer Service</h4>
                        <ul>
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Store Locator</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="client.php">Dashboard</a></li>
                            <li><a href="orders.php">My Orders</a></li>
                            <li><a href="menu.php">Menu</a></li>
                            <li><a href="profile.php">Profile</a></li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToCart(productId) {
            // TODO: Implement add to cart functionality
            alert('Product added to cart!');
        }

        function getStatusColor(status) {
            switch(status) {
                case 'pending':
                    return 'warning';
                case 'processing':
                    return 'info';
                case 'completed':
                    return 'success';
                case 'cancelled':
                    return 'danger';
                default:
                    return 'secondary';
            }
        }

        // User dropdown toggle
        document.addEventListener('DOMContentLoaded', function() {
            const userIcon = document.querySelector('.user-icon');
            const userDropdown = document.querySelector('.user-dropdown');

            userIcon.addEventListener('click', function(e) {
                e.preventDefault();
                userDropdown.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.user-menu')) {
                    userDropdown.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>
