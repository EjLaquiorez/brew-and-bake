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
    $defaultImage = "coffee.png"; // Default to coffee.png if no match
    $categoryName = strtolower($categoryName);

    switch ($categoryName) {
        case 'coffee':
            return "coffee.png";
        case 'cake':
        case 'cakes':
            return "cake.png";
        case 'pastry':
        case 'pastries':
            return "pastries.png";
        case 'beverage':
        case 'beverages':
        case 'drink':
        case 'drinks':
            return "beverage.png";
        // For other categories, we'll use the available images as fallbacks
        case 'dessert':
        case 'bakery':
        case 'treats':
            return "cake.png";
        case 'sandwich':
        case 'sandwiches':
            return "sandwich.png";
        case 'breakfast':
        case 'hot tea':
        case 'cold tea':
        case 'refreshers':
        case 'frappuccino':
        case 'blended beverage':
        case 'iced energy':
        case 'hot chocolate':
        case 'bottled beverages':
            // For any other drink-related category, use beverage.png
            if (strpos($categoryName, 'tea') !== false ||
                strpos($categoryName, 'drink') !== false ||
                strpos($categoryName, 'beverage') !== false) {
                return "beverage.png";
            }
            // For any other food-related category, use pastries.png
            return "pastries.png";
        default:
            return $defaultImage;
    }
}

// Helper function to get the appropriate category image for a product
function getProductCategoryImage($categoryName) {
    // Simply use the same function as getCategoryImage for consistency
    return getCategoryImage($categoryName);
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
    <!-- Include Header -->
    <?php include_once "../includes/header.php"; ?>

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
                    <?php
                    // Define drink categories
                    $drinkCategories = ['coffee', 'drink', 'beverage', 'beverages', 'hot tea', 'cold tea', 'refreshers',
                                       'frappuccino', 'blended beverage', 'iced energy', 'hot chocolate',
                                       'bottled beverages'];

                    foreach ($categories as $category):
                        if (in_array(strtolower($category['name']), $drinkCategories)):
                    ?>
                        <li><a href="#category-<?= $category['id'] ?>"><?= htmlspecialchars(ucfirst($category['name'])) ?></a></li>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </ul>

                <h2>Food</h2>
                <ul class="category-nav">
                    <?php
                    // Define food categories
                    $foodCategories = ['cake', 'cakes', 'pastry', 'pastries', 'dessert', 'sandwich', 'sandwiches',
                                      'breakfast', 'bakery', 'treats'];

                    foreach ($categories as $category):
                        if (in_array(strtolower($category['name']), $foodCategories)):
                    ?>
                        <li><a href="#category-<?= $category['id'] ?>"><?= htmlspecialchars(ucfirst($category['name'])) ?></a></li>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </ul>
            </aside>

            <!-- Menu Content -->
            <div class="menu-content">
                <h1 class="menu-title">Our Menu</h1>

                <!-- Drinks Section -->
                <section class="menu-section">
                    <h2 class="section-title">Drinks</h2>
                    <div class="menu-grid">
                        <?php foreach ($categories as $category): ?>
                            <?php if (in_array(strtolower($category['name']), $drinkCategories)): ?>
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

                <!-- Food Section -->
                <section class="menu-section">
                    <h2 class="section-title">Food</h2>
                    <div class="menu-grid">
                        <?php foreach ($categories as $category): ?>
                            <?php if (in_array(strtolower($category['name']), $foodCategories)): ?>
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
                                        <?php
                                        // Get category name for the product
                                        $categoryName = strtolower($product['category_name'] ?? '');
                                        $categoryImage = getProductCategoryImage($categoryName);

                                        if (!empty($categoryImage)):
                                        ?>
                                            <img src="../../assets/images/categories/<?= $categoryImage ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="generic-product-image">
                                        <?php else: ?>
                                            <div class="no-image">
                                                <i class="bi bi-cup-hot"></i>
                                            </div>
                                        <?php endif; ?>
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
    <script src="../../assets/js/user-menu.js"></script>
    <script>
        function addToCart(productId) {
            // TODO: Implement add to cart functionality
            alert('Product added to cart!');
        }
    </script>
</body>
</html>
