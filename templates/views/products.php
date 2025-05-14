<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$query = "SELECT * FROM products WHERE status = 'active'";
$params = [];

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY price DESC";
        break;
    case 'name_desc':
        $query .= " ORDER BY name DESC";
        break;
    default: // name_asc
        $query .= " ORDER BY name ASC";
}

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique categories for filter
    $catStmt = $conn->query("SELECT DISTINCT category FROM products WHERE status = 'active' ORDER BY category");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $products = [];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/products.css">
    <link rel="stylesheet" href="../../assets/css/navigation.css">
</head>
<body>
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Products Header -->
    <header class="products-header">
        <div class="container">
            <h1>Our Products</h1>
            <p>Discover our selection of premium coffee and freshly baked goods</p>
        </div>
    </header>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <!-- Filters and Search -->
            <div class="filters-container">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="search-box">
                            <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-search">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(ucfirst($cat)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                        </select>
                    </div>
                    <?php if (!empty($category) || !empty($search)): ?>
                        <div class="col-md-2">
                            <a href="products.php" class="btn btn-outline-secondary w-100">Clear Filters</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Products Grid -->
            <div class="row g-4">
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-emoji-frown display-1 text-muted"></i>
                        <h3 class="mt-3">No products found</h3>
                        <p class="text-muted">Try adjusting your search or filter criteria</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="product-card">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                         alt="<?= htmlspecialchars($product['name']) ?>"
                                         class="product-image">
                                <?php endif; ?>
                                <div class="product-info">
                                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                                    <div class="product-meta">
                                        <span class="category"><?= htmlspecialchars(ucfirst($product['category'])) ?></span>
                                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                    </div>
                                    <?php if (isLoggedIn()): ?>
                                        <button class="btn btn-primary w-100 mt-3" onclick="addToCart(<?= $product['id'] ?>)">
                                            <i class="bi bi-cart-plus"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-primary w-100 mt-3">
                                            Login to Order
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

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
                        <li><a href="../../index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="../../index.php#about">About</a></li>
                        <li><a href="../../index.php#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Brew & Bake. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        function addToCart(productId) {
            // TODO: Implement add to cart functionality
            alert('Product added to cart!');
        }
    </script>
</body>
</html>