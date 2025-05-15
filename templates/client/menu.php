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

// Get categories
try {
    $stmt = $conn->query("
        SELECT * FROM categories 
        ORDER BY name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching categories: " . $e->getMessage();
    $categories = [];
}

// Get products by category
$productsByCategory = [];
foreach ($categories as $category) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM products 
            WHERE category_id = ? AND status = 'active'
            ORDER BY name ASC
        ");
        $stmt->execute([$category['id']]);
        $productsByCategory[$category['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMessage = "Error fetching products: " . $e->getMessage();
        $productsByCategory[$category['id']] = [];
    }
}

// Get featured products
try {
    $stmt = $conn->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active'
        ORDER BY RAND()
        LIMIT 6
    ");
    $featuredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching featured products: " . $e->getMessage();
    $featuredProducts = [];
}

// Helper function to get category image
function getCategoryImage($categoryName) {
    $defaultImage = "category-default.jpg";
    $categoryName = strtolower($categoryName);
    
    switch ($categoryName) {
        case 'coffee':
            return "hot-coffee.jpg";
        case 'cake':
            return "cake.jpg";
        case 'pastry':
            return "pastry.jpg";
        case 'drink':
            return "cold-coffee.jpg";
        case 'dessert':
            return "dessert.jpg";
        default:
            return $defaultImage;
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
    <title>Menu - Brew & Bake</title>
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
                        <li><a href="menu.php" class="active">MENU</a></li>
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

    <!-- Menu Navigation -->
    <div class="menu-nav">
        <div class="container">
            <ul class="menu-tabs">
                <li><a href="#" class="active">Menu</a></li>
                <li><a href="#">Featured</a></li>
                <li><a href="#">Previous</a></li>
                <li><a href="#">Favorites</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="menu-container">
            <!-- Sidebar -->
            <aside class="menu-sidebar">
                <h2>Drinks</h2>
                <ul class="category-nav">
                    <?php foreach ($categories as $category): ?>
                        <?php if (in_array(strtolower($category['name']), ['coffee', 'drink'])): ?>
                            <li><a href="#category-<?= $category['id'] ?>"><?= htmlspecialchars(ucfirst($category['name'])) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li><a href="#hot-coffee">Hot Coffee</a></li>
                    <li><a href="#cold-coffee">Cold Coffee</a></li>
                    <li><a href="#hot-tea">Hot Tea</a></li>
                    <li><a href="#cold-tea">Cold Tea</a></li>
                    <li><a href="#refreshers">Refreshers</a></li>
                    <li><a href="#frappuccino">Frappuccino</a></li>
                </ul>

                <h2>Food</h2>
                <ul class="category-nav">
                    <?php foreach ($categories as $category): ?>
                        <?php if (in_array(strtolower($category['name']), ['cake', 'pastry', 'dessert'])): ?>
                            <li><a href="#category-<?= $category['id'] ?>"><?= htmlspecialchars(ucfirst($category['name'])) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li><a href="#breakfast">Breakfast</a></li>
                    <li><a href="#bakery">Bakery</a></li>
                    <li><a href="#treats">Treats</a></li>
                </ul>
            </aside>

            <!-- Menu Content -->
            <div class="menu-content">
                <h1 class="menu-title">Menu</h1>

                <!-- Drinks Section -->
                <section class="menu-section">
                    <h2 class="section-title">Drinks</h2>
                    <div class="menu-grid">
                        <a href="#hot-coffee" class="menu-item">
                            <div class="menu-item-image">
                                <img src="../../assets/images/categories/hot-coffee.jpg" alt="Hot Coffee">
                            </div>
                            <h3 class="menu-item-title">Hot Coffee</h3>
                        </a>
                        <a href="#cold-coffee" class="menu-item">
                            <div class="menu-item-image">
                                <img src="../../assets/images/categories/cold-coffee.jpg" alt="Cold Coffee">
                            </div>
                            <h3 class="menu-item-title">Cold Coffee</h3>
                        </a>
                        <a href="#hot-tea" class="menu-item">
                            <div class="menu-item-image">
                                <img src="../../assets/images/categories/hot-tea.jpg" alt="Hot Tea">
                            </div>
                            <h3 class="menu-item-title">Hot Tea</h3>
                        </a>
                        <a href="#cold-tea" class="menu-item">
                            <div class="menu-item-image">
                                <img src="../../assets/images/categories/cold-tea.jpg" alt="Cold Tea">
                            </div>
                            <h3 class="menu-item-title">Cold Tea</h3>
                        </a>
                        <a href="#refreshers" class="menu-item">
                            <div class="menu-item-image">
                                <img src="../../assets/images/categories/refreshers.jpg" alt="Refreshers">
                            </div>
                            <h3 class="menu-item-title">Refreshers</h3>
                        </a>
                        <a href="#frappuccino" class="menu-item">
                            <div class="menu-item-image">
                                <img src="../../assets/images/categories/frappuccino.jpg" alt="Frappuccino">
                            </div>
                            <h3 class="menu-item-title">Frappuccino</h3>
                        </a>
                    </div>
                </section>

                <!-- Food Section -->
                <section class="menu-section">
                    <h2 class="section-title">Food</h2>
                    <div class="menu-grid">
                        <?php foreach ($categories as $category): ?>
                            <?php if (in_array(strtolower($category['name']), ['cake', 'pastry', 'dessert'])): ?>
                                <a href="#category-<?= $category['id'] ?>" class="menu-item">
                                    <div class="menu-item-image">
                                        <img src="../../assets/images/categories/<?= getCategoryImage($category['name']) ?>" alt="<?= htmlspecialchars(ucfirst($category['name'])) ?>">
                                    </div>
                                    <h3 class="menu-item-title"><?= htmlspecialchars(ucfirst($category['name'])) ?></h3>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Featured Products -->
                <section class="menu-section">
                    <h2 class="section-title">Featured Products</h2>
                    <div class="product-grid">
                        <?php foreach ($featuredProducts as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="bi bi-cup-hot"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="product-category"><?= htmlspecialchars(ucfirst($product['category_name'] ?? 'Uncategorized')) ?></p>
                                    <p class="product-price">₱<?= number_format($product['price'], 2) ?></p>
                                    <button class="add-to-cart-btn" onclick="addToCart(<?= $product['id'] ?>)">Add to Cart</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
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
