<?php
require_once "templates/includes/auth.php";
require_once "templates/includes/db.php";

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$userRole = getCurrentUserRole();

// Get categories
try {
    $stmt = $conn->query("
        SELECT * FROM categories
        ORDER BY name ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
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
    <title>Brew & Bake - Premium Coffee House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/menu.css?v=<?= time() ?>">
    <style>
        /* Login Modal Styling */
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 1rem 1rem 0;
        }

        .brand-logo {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .input-group-text {
            background-color: transparent;
            border-right: none;
        }

        .form-control {
            border-left: none;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--color-secondary);
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--color-secondary);
        }

        .remember-me {
            color: var(--color-gray-600);
        }

        .form-check-input:checked {
            background-color: var(--color-secondary);
            border-color: var(--color-secondary);
        }

        /* Custom Alert Styling - Minimalist */
        .custom-alert {
            border-radius: 4px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            letter-spacing: 0.01em;
            animation: fadeIn 0.2s ease-out;
            border: none;
            text-align: center;
        }

        .custom-alert.alert-success {
            background-color: #f8f9fa;
            color: var(--color-secondary);
            border-bottom: 1px solid var(--color-secondary);
        }

        .custom-alert.alert-danger {
            background-color: #f8f9fa;
            color: #dc3545;
            border-bottom: 1px solid #dc3545;
        }

        .custom-alert.alert-warning {
            background-color: #f8f9fa;
            color: #ffc107;
            border-bottom: 1px solid #ffc107;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 0.5rem;
        }

        .password-strength .progress {
            height: 4px;
            margin-bottom: 0.25rem;
            background-color: #f0f0f0;
        }

        .password-strength .form-text {
            font-size: 0.75rem;
            color: #6c757d;
        }

        /* Form validation */
        .is-valid {
            border-color: #28a745 !important;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
        }

        /* Header styling */
        .site-header {
            background-color: var(--color-primary);
            position: relative;
            z-index: 101; /* Higher than menu-nav */
        }

        /* Make menu-nav sticky */
        .menu-nav {
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            background-color: var(--color-white);
            border-bottom: 1px solid var(--color-gray-200);
        }

        /* Add padding to body to prevent content jump */
        body {
            padding-top: 0;
        }

        /* Header inner layout */
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
        }

        /* Logo styling */
        .logo a {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-white);
        }

        .logo i {
            margin-right: 0.5rem;
            color: var(--color-secondary);
        }

        /* Main navigation */
        .main-nav ul {
            display: flex;
            gap: 2rem;
            margin: 0;
            padding: 0;
        }

        .main-nav a {
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            padding: 0.5rem 0;
            position: relative;
            color: var(--color-gray-300);
            transition: color 0.3s ease;
        }

        .main-nav a:hover,
        .main-nav a.active {
            color: var(--color-white);
        }

        .main-nav a.active::after,
        .main-nav a:hover::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: var(--color-secondary);
        }

        /* Header actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .cart-icon {
            position: relative;
            font-size: 1.25rem;
            color: var(--color-white);
        }

        .user-menu {
            position: relative;
        }

        .user-icon {
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--color-white);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--color-primary-light);
            box-shadow: var(--shadow-md);
            border-radius: var(--radius-md);
            width: 200px;
            padding: 0.5rem 0;
            display: none;
            z-index: 10;
        }

        .user-dropdown.show {
            display: block;
        }

        .user-dropdown ul {
            padding: 0;
            margin: 0;
        }

        .user-dropdown li a {
            display: block;
            padding: 0.75rem 1rem;
            transition: background-color 0.3s ease;
            color: var(--color-gray-300);
        }

        .user-dropdown li a:hover {
            background-color: var(--color-primary-dark);
            color: var(--color-white);
        }

        /* Starbucks-style menu tabs */
        .menu-tabs {
            display: flex;
            padding: 0;
            margin: 0;
            max-width: 1200px;
            margin: 0 auto;
        }

        .menu-tabs li {
            margin: 0;
        }

        .menu-tabs a {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--color-gray-700);
            padding: 1rem 1.5rem;
            display: block;
            position: relative;
            transition: color 0.3s ease;
            text-transform: capitalize;
        }

        .menu-tabs a:hover {
            color: var(--color-primary);
        }

        .menu-tabs a.active {
            color: var(--color-primary);
            font-weight: 700;
        }

        .menu-tabs a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--color-secondary);
            border-radius: 4px 4px 0 0;
        }

        /* Add scroll class for menu-nav */
        .menu-nav.scrolled {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Container for menu content */
        .menu-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (max-width: 992px) {
            .header-inner {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .logo {
                flex: 1;
            }

            .main-nav {
                order: 3;
                width: 100%;
                margin-top: 0.5rem;
            }

            .main-nav ul {
                justify-content: space-between;
            }
        }

        @media (max-width: 768px) {
            .menu-tabs {
                justify-content: space-between;
            }

            .menu-tabs a {
                padding: 0.75rem 0.75rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="index.php">
                        <i class="bi bi-cup-hot"></i> Brew & Bake
                    </a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="#" class="active">MENU</a></li>
                        <li><a href="#about">ABOUT</a></li>
                        <li><a href="#contact">CONTACT</a></li>
                    </ul>
                </nav>
                <div class="header-actions">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $userRole === 'client' ? 'templates/client/cart.php' : '#' ?>" class="cart-icon">
                            <i class="bi bi-cart"></i>
                        </a>
                        <div class="user-menu">
                            <a href="#" class="user-icon">
                                <i class="bi bi-person-circle"></i>
                            </a>
                            <div class="user-dropdown">
                                <ul>
                                    <?php if ($userRole === 'admin'): ?>
                                        <li><a href="templates/admin/dashboard.php">Admin Dashboard</a></li>
                                    <?php elseif ($userRole === 'staff'): ?>
                                        <li><a href="templates/staff/staff.php">Staff Dashboard</a></li>
                                    <?php else: ?>
                                        <li><a href="templates/client/client.php">My Account</a></li>
                                        <li><a href="templates/client/orders.php">My Orders</a></li>
                                    <?php endif; ?>
                                    <li><a href="templates/includes/logout.php">Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="#" class="cart-icon" id="loginCartLink">
                            <i class="bi bi-cart"></i>
                        </a>
                        <div class="user-menu">
                            <a href="#" class="user-icon">
                                <i class="bi bi-person-circle"></i>
                            </a>
                            <div class="user-dropdown">
                                <ul>
                                    <li><a href="#" class="login-link">Login</a></li>
                                    <li><a href="#" class="register-link">Register</a></li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Menu Navigation -->
    <div class="menu-nav">
        <div class="container">
            <ul class="menu-tabs">
                <li><a href="#menu-section" class="active">Menu</a></li>
                <li><a href="#featured">Featured</a></li>
                <li><a href="#about">About Us</a></li>
                <li><a href="#contact">Contact</a></li>
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
                </ul>

                <h2>Food</h2>
                <ul class="category-nav">
                    <?php foreach ($categories as $category): ?>
                        <?php if (in_array(strtolower($category['name']), ['cake', 'pastry', 'dessert'])): ?>
                            <li><a href="#category-<?= $category['id'] ?>"><?= htmlspecialchars(ucfirst($category['name'])) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <li><a href="#bakery">Bakery</a></li>
                    <li><a href="#treats">Treats</a></li>
                </ul>
            </aside>

            <!-- Menu Content -->
            <div class="menu-content">
                <h1 class="menu-title">Our Menu</h1>

                <!-- Drinks Section -->
                <section id="menu-section" class="menu-section">
                    <h2 class="section-title">Drinks</h2>
                    <div class="menu-grid">
                        <?php foreach ($categories as $category): ?>
                            <?php if (in_array(strtolower($category['name']), ['coffee', 'drink'])): ?>
                                <a href="#category-<?= $category['id'] ?>" class="menu-item">
                                    <div class="menu-item-image">
                                        <img src="assets/images/categories/<?= getCategoryImage($category['name']) ?>" alt="<?= htmlspecialchars(ucfirst($category['name'])) ?>">
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
                            <?php if (in_array(strtolower($category['name']), ['cake', 'pastry', 'dessert'])): ?>
                                <a href="#category-<?= $category['id'] ?>" class="menu-item">
                                    <div class="menu-item-image">
                                        <img src="assets/images/categories/<?= getCategoryImage($category['name']) ?>" alt="<?= htmlspecialchars(ucfirst($category['name'])) ?>">
                                    </div>
                                    <h3 class="menu-item-title"><?= htmlspecialchars(ucfirst($category['name'])) ?></h3>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Featured Products -->
                <section id="featured" class="menu-section">
                    <h2 class="section-title">Featured Products</h2>
                    <div class="product-grid">
                        <?php foreach ($featuredProducts as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="bi bi-cup-hot"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="text-muted"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 60)) . (isset($product['description']) && strlen($product['description']) > 60 ? '...' : '') ?></p>
                                    <div class="product-meta">
                                        <span class="category"><?= htmlspecialchars(ucfirst($product['category_name'] ?? 'Uncategorized')) ?></span>
                                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                    </div>
                                    <?php if ($isLoggedIn && $userRole === 'client'): ?>
                                        <a href="templates/client/cart.php?add=<?= $product['id'] ?>" class="btn btn-primary w-100">
                                            <i class="bi bi-cart-plus"></i> Order Now
                                        </a>
                                    <?php else: ?>
                                        <a href="#" class="btn btn-primary w-100 login-required">
                                            <i class="bi bi-cart-plus"></i> Order Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Category Products -->
                <?php foreach ($categories as $category): ?>
                    <?php if (!empty($productsByCategory[$category['id']])): ?>
                        <section id="category-<?= $category['id'] ?>" class="menu-section">
                            <h2 class="section-title"><?= htmlspecialchars(ucfirst($category['name'])) ?></h2>
                            <div class="product-grid">
                                <?php foreach ($productsByCategory[$category['id']] as $product): ?>
                                    <div class="product-card">
                                        <div class="product-image">
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="assets/images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="bi bi-cup-hot"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-info">
                                            <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                            <p class="text-muted"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 60)) . (isset($product['description']) && strlen($product['description']) > 60 ? '...' : '') ?></p>
                                            <div class="product-meta">
                                                <span class="category"><?= htmlspecialchars(ucfirst($category['name'])) ?></span>
                                                <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                            </div>
                                            <?php if ($isLoggedIn && $userRole === 'client'): ?>
                                                <a href="templates/client/cart.php?add=<?= $product['id'] ?>" class="btn btn-primary w-100">
                                                    <i class="bi bi-cart-plus"></i> Order Now
                                                </a>
                                            <?php else: ?>
                                                <a href="#" class="btn btn-primary w-100 login-required">
                                                    <i class="bi bi-cart-plus"></i> Order Now
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <section id="about" class="about py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="section-title mb-4">Our Story</h2>
                    <p class="lead">Welcome to Brew & Bake, where passion meets perfection in every cup and every bite.</p>
                    <p>We started with a simple dream: to create a space where people can enjoy exceptional coffee and delicious baked goods in a warm, welcoming atmosphere. Our journey began with a love for the art of coffee brewing and the joy of baking.</p>
                    <p>Today, we continue to serve our community with the same passion and dedication, using only the finest ingredients and maintaining the highest standards of quality.</p>
                </div>
                <div class="col-lg-6">
                    <img src="assets/images/about.jpg" alt="Our Coffee Shop" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Contact Us</h2>
            <div class="row">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <div class="info-item">
                            <i class="bi bi-geo-alt"></i>
                            <h3>Location</h3>
                            <p>123 Coffee Street, Manila, Philippines</p>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-clock"></i>
                            <h3>Hours</h3>
                            <p>Monday - Sunday: 7:00 AM - 9:00 PM</p>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-telephone"></i>
                            <h3>Phone</h3>
                            <p>+63 123 456 7890</p>
                        </div>
                        <div class="info-item">
                            <i class="bi bi-envelope"></i>
                            <h3>Email</h3>
                            <p>info@brewandbake.com</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <form class="contact-form">
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" rows="5" placeholder="Your Message" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-accent-custom">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">What Our Customers Say</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="stars mb-3">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <p class="mb-3">"The best coffee I've ever had! Their pastries are amazing too. This is my go-to spot every morning."</p>
                        <div class="d-flex align-items-center">
                            <div class="testimonial-avatar me-3">
                                <i class="bi bi-person-circle fs-1"></i>
                            </div>
                            <div>
                                <h5 class="testimonial-name mb-0">Maria Santos</h5>
                                <small class="testimonial-title">Regular Customer</small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add 2 more testimonials with similar structure -->
            </div>
        </div>
    </section>

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
                            <li><a href="#about">Our Story</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Social Impact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Customer Service</h4>
                        <ul>
                            <li><a href="#contact">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Store Locator</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="#featured">Featured</a></li>
                            <?php if ($isLoggedIn): ?>
                                <?php if ($userRole === 'admin'): ?>
                                    <li><a href="templates/admin/dashboard.php">Admin Dashboard</a></li>
                                <?php elseif ($userRole === 'staff'): ?>
                                    <li><a href="templates/staff/staff.php">Staff Dashboard</a></li>
                                <?php else: ?>
                                    <li><a href="templates/client/client.php">My Account</a></li>
                                <?php endif; ?>
                                <li><a href="templates/includes/logout.php">Logout</a></li>
                            <?php else: ?>
                                <li><a href="#" class="login-link">Login</a></li>
                                <li><a href="#" class="register-link">Register</a></li>
                            <?php endif; ?>
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

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-2">
                    <div class="text-center mb-4">
                        <div class="brand-logo">
                            <i class="bi bi-cup-hot" style="color: var(--color-secondary);"></i> Brew & Bake
                        </div>
                        <h2 class="text-muted">Welcome Back!</h2>
                        <p class="text-muted">Sign in to continue to your account</p>
                    </div>

                    <form id="loginForm" method="POST">
                        <div id="loginAlert" class="mt-2 mb-3">
                            <?php if (isset($_SESSION['verification_success']) && $_SESSION['verification_success']): ?>
                            <div class="custom-alert alert-success mb-3">
                                <i class="bi bi-check-circle me-2"></i> Your email has been verified successfully! You can now log in.
                            </div>
                            <?php unset($_SESSION['verification_success']); ?>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" name="email" class="form-control" placeholder="Email address"
                                    value="<?= isset($_SESSION['verification_email']) ? htmlspecialchars($_SESSION['verification_email']) : '' ?>"
                                    required autofocus>
                                <?php if (isset($_SESSION['verification_email'])) unset($_SESSION['verification_email']); ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                            <label class="form-check-label remember-me" for="remember">Remember me</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-4" style="background-color: var(--color-primary); border-color: var(--color-secondary);">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                        </button>

                        <div class="text-center mb-3">
                            <span class="text-muted">or continue with</span>
                        </div>

                        <div class="d-flex justify-content-center gap-3 mb-4">
                            <a href="#" class="btn btn-outline-secondary">
                                <i class="bi bi-google"></i>
                            </a>
                            <a href="#" class="btn btn-outline-secondary">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="#" class="btn btn-outline-secondary">
                                <i class="bi bi-twitter"></i>
                            </a>
                        </div>

                        <p class="text-center mb-0">
                            Don't have an account?
                            <a href="#" id="showRegisterModal" class="text-decoration-none" style="color: var(--color-secondary);">
                                Sign up
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-2">
                    <div class="text-center mb-4">
                        <div class="brand-logo">
                            <i class="bi bi-cup-hot" style="color: var(--color-secondary);"></i> Brew & Bake
                        </div>
                        <h2 class="text-muted">Create Account</h2>
                        <p class="text-muted">Join our community today</p>
                    </div>

                    <div id="registerAlert" class="mt-2 mb-3"></div>

                    <form id="registerForm" method="POST">
                        <div class="row mb-3">
                            <div class="col">
                                <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                            </div>
                            <div class="col">
                                <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                            </div>
                        </div>
                        <!-- Note: First and last names will be combined into a single 'name' field in the database -->

                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                        </div>

                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" name="password" id="reg_password" class="form-control" placeholder="Password (min. 8 characters)" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleRegPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2" id="password-strength">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="password-strength-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="form-text text-muted mt-1" id="password-strength-text">Password strength: Too weak</small>
                                <small class="form-text text-muted d-block mt-1">
                                    <i class="bi bi-info-circle-fill me-1"></i> For a strong password, include:
                                    <ul class="mb-0 ps-4 mt-1">
                                        <li>At least 8 characters</li>
                                        <li>Uppercase letters (A-Z)</li>
                                        <li>Lowercase letters (a-z)</li>
                                        <li>Numbers (0-9) or special characters (@#$!)</li>
                                    </ul>
                                </small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" name="confirm_password" id="reg_confirm_password" class="form-control" placeholder="Confirm Password" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" style="color: var(--color-secondary);">Terms of Service</a> and <a href="#" style="color: var(--color-secondary);">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-4" style="background-color: var(--color-primary); border-color: var(--color-secondary);">
                            <i class="bi bi-person-plus me-2"></i> Create Account
                        </button>

                        <p class="text-center mb-0">
                            Already have an account?
                            <a href="#" id="showLoginModal" class="text-decoration-none" style="color: var(--color-secondary);">
                                Sign in
                            </a>
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User dropdown toggle
        document.addEventListener('DOMContentLoaded', function() {
            const userIcon = document.querySelector('.user-icon');
            const userDropdown = document.querySelector('.user-dropdown');
            const menuNav = document.querySelector('.menu-nav');

            // User dropdown toggle
            if (userIcon) {
                userIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    userDropdown.classList.toggle('show');
                });

                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.user-menu')) {
                        userDropdown.classList.remove('show');
                    }
                });
            }

            // Scroll effect for sticky menu navigation
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    menuNav.classList.add('scrolled');
                } else {
                    menuNav.classList.remove('scrolled');
                }

                // Update active menu tab based on scroll position
                const sections = document.querySelectorAll('section[id]');
                const menuLinks = document.querySelectorAll('.menu-tabs a');

                let currentSection = '';

                sections.forEach(section => {
                    const sectionTop = section.offsetTop - menuNav.offsetHeight - 50;
                    const sectionHeight = section.offsetHeight;
                    const sectionId = section.getAttribute('id');

                    if (window.scrollY >= sectionTop && window.scrollY < sectionTop + sectionHeight) {
                        currentSection = sectionId;
                    }
                });

                menuLinks.forEach(link => {
                    link.classList.remove('active');
                    const href = link.getAttribute('href').substring(1); // Remove the # character

                    if (href === currentSection || (href === '' && currentSection === '')) {
                        link.classList.add('active');
                    }
                });
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Get the target element
                    const href = this.getAttribute('href');

                    // Handle the case when href is just "#"
                    if (href === "#") {
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                        return;
                    }

                    const target = document.querySelector(href);

                    if (target) {
                        // Get the height of the sticky menu nav
                        const menuNavHeight = menuNav.offsetHeight;
                        const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;

                        // Scroll to the target with an offset for the sticky menu
                        window.scrollTo({
                            top: targetPosition - menuNavHeight - 20, // 20px extra padding
                            behavior: 'smooth'
                        });

                        // Update active class in menu tabs
                        document.querySelectorAll('.menu-tabs a').forEach(link => {
                            link.classList.remove('active');
                        });
                        this.classList.add('active');
                    }
                });
            });

            // Add scroll reveal effect for product cards
            const animateElements = document.querySelectorAll('.product-card, .menu-item');

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                            observer.unobserve(entry.target);
                        }
                    });
                }, {threshold: 0.1});

                animateElements.forEach(el => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    observer.observe(el);
                });
            }

            // Login and Register Modal Functionality
            const loginLinks = document.querySelectorAll('a[href="templates/views/login.php"]');
            const registerLinks = document.querySelectorAll('a[href="templates/views/register.php"]');
            const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            const registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
            const showRegisterModalLink = document.getElementById('showRegisterModal');
            const showLoginModalLink = document.getElementById('showLoginModal');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');

            // Show login modal when login links are clicked
            loginLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    loginModal.show();
                });
            });

            // Show register modal when register links are clicked
            registerLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    registerModal.show();
                });
            });

            // Handle login-required links
            document.querySelectorAll('.login-required').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    loginModal.show();
                });
            });

            // Handle login link in dropdown
            document.querySelectorAll('.login-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    loginModal.show();
                });
            });

            // Handle register link in dropdown
            document.querySelectorAll('.register-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    registerModal.show();
                });
            });

            // Handle cart icon click when not logged in
            const loginCartLink = document.getElementById('loginCartLink');
            if (loginCartLink) {
                loginCartLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    loginModal.show();
                });
            }

            // Switch between login and register modals
            if (showRegisterModalLink) {
                showRegisterModalLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    loginModal.hide();
                    setTimeout(() => {
                        registerModal.show();
                    }, 400);
                });
            }

            if (showLoginModalLink) {
                showLoginModalLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    registerModal.hide();
                    setTimeout(() => {
                        loginModal.show();
                    }, 400);
                });
            }

            // Toggle password visibility for login form
            if (togglePasswordBtn && passwordField) {
                togglePasswordBtn.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);

                    // Toggle the eye icon
                    this.querySelector('i').classList.toggle('bi-eye');
                    this.querySelector('i').classList.toggle('bi-eye-slash');
                });
            }

            // Toggle password visibility for registration form
            const toggleRegPasswordBtn = document.getElementById('toggleRegPassword');
            const regPasswordField = document.getElementById('reg_password');
            if (toggleRegPasswordBtn && regPasswordField) {
                toggleRegPasswordBtn.addEventListener('click', function() {
                    const type = regPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    regPasswordField.setAttribute('type', type);

                    // Toggle the eye icon
                    this.querySelector('i').classList.toggle('bi-eye');
                    this.querySelector('i').classList.toggle('bi-eye-slash');
                });

                // Password strength indicator
                regPasswordField.addEventListener('input', function() {
                    const password = this.value;
                    const strengthBar = document.getElementById('password-strength-bar');
                    const strengthText = document.getElementById('password-strength-text');

                    // Calculate password strength
                    let strength = 0;

                    // Length check
                    if (password.length >= 8) {
                        strength += 25;
                    }

                    // Contains lowercase letters
                    if (password.match(/[a-z]+/)) {
                        strength += 25;
                    }

                    // Contains uppercase letters
                    if (password.match(/[A-Z]+/)) {
                        strength += 25;
                    }

                    // Contains numbers or special characters
                    if (password.match(/[0-9]+/) || password.match(/[$@#&!]+/)) {
                        strength += 25;
                    }

                    // Update the strength bar
                    strengthBar.style.width = strength + '%';
                    strengthBar.setAttribute('aria-valuenow', strength);

                    // Update color based on strength
                    if (strength < 25) {
                        strengthBar.className = 'progress-bar bg-danger';
                        strengthText.textContent = 'Password strength: Too weak';
                    } else if (strength < 50) {
                        strengthBar.className = 'progress-bar bg-warning';
                        strengthText.textContent = 'Password strength: Weak';
                    } else if (strength < 75) {
                        strengthBar.className = 'progress-bar bg-info';
                        strengthText.textContent = 'Password strength: Medium';
                    } else {
                        strengthBar.className = 'progress-bar bg-success';
                        strengthText.textContent = 'Password strength: Strong';
                    }
                });
            }

            // Toggle confirm password visibility for registration form
            const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
            const confirmPasswordField = document.getElementById('reg_confirm_password');
            if (toggleConfirmPasswordBtn && confirmPasswordField) {
                toggleConfirmPasswordBtn.addEventListener('click', function() {
                    const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    confirmPasswordField.setAttribute('type', type);

                    // Toggle the eye icon
                    this.querySelector('i').classList.toggle('bi-eye');
                    this.querySelector('i').classList.toggle('bi-eye-slash');
                });

                // Check if passwords match in real-time
                confirmPasswordField.addEventListener('input', function() {
                    const password = document.getElementById('reg_password').value;
                    const confirmPassword = this.value;

                    // Add feedback element if it doesn't exist
                    let feedbackElement = document.getElementById('password-match-feedback');
                    if (!feedbackElement) {
                        feedbackElement = document.createElement('div');
                        feedbackElement.id = 'password-match-feedback';
                        feedbackElement.className = 'form-text mt-1';
                        this.parentNode.parentNode.appendChild(feedbackElement);
                    }

                    // Check if passwords match
                    if (confirmPassword === '') {
                        feedbackElement.textContent = '';
                        feedbackElement.className = 'form-text mt-1';
                        this.classList.remove('is-valid', 'is-invalid');
                    } else if (password === confirmPassword) {
                        feedbackElement.textContent = 'Passwords match';
                        feedbackElement.className = 'form-text text-success mt-1';
                        this.classList.add('is-valid');
                        this.classList.remove('is-invalid');
                    } else {
                        feedbackElement.textContent = 'Passwords do not match';
                        feedbackElement.className = 'form-text text-danger mt-1';
                        this.classList.add('is-invalid');
                        this.classList.remove('is-valid');
                    }
                });
            }

            // Handle login form submission
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                // Function to handle test email button
                function setupTestEmailButton(button) {
                    if (!button) return;

                    button.addEventListener('click', function() {
                        const email = this.getAttribute('data-email');
                        const token = this.getAttribute('data-token');
                        const name = this.getAttribute('data-name');
                        const statusEl = this.nextElementSibling;

                        // Disable button and show loading
                        this.disabled = true;
                        this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Sending...';
                        statusEl.innerHTML = '<span class="text-muted">Sending verification email...</span>';

                        // Send AJAX request to test email
                        const formData = new FormData();
                        formData.append('email', email);
                        formData.append('token', token);
                        formData.append('name', name);

                        fetch('templates/includes/test_email.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                statusEl.innerHTML = `<span class="text-success"><i class="bi bi-check-circle me-1"></i> ${data.message}</span>`;
                                this.innerHTML = '<i class="bi bi-envelope-check me-1"></i> Email Sent';
                                this.classList.remove('btn-outline-secondary');
                                this.classList.add('btn-success');
                            } else {
                                statusEl.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i> ${data.message}</span>`;
                                this.innerHTML = '<i class="bi bi-envelope me-1"></i> Test Email Verification';
                                this.disabled = false;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            statusEl.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i> An error occurred. Please try again.</span>';
                            this.innerHTML = '<i class="bi bi-envelope me-1"></i> Test Email Verification';
                            this.disabled = false;
                        });
                    });
                }

                // Function to create verification UI
                function createVerificationUI(data, formId) {
                    const email = document.getElementById(formId).email.value;
                    let name = 'User';

                    if (formId === 'registerForm') {
                        name = `${document.getElementById(formId).first_name.value} ${document.getElementById(formId).last_name.value}`;
                    }

                    return `<div class="mt-3 p-3 border rounded verification-container" style="background-color: #f8f9fa; border-color: #dee2e6 !important;">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-envelope-check me-2" style="font-size: 1.25rem; color: var(--color-primary);"></i>
                            <h6 class="mb-0 fw-bold">Email Verification Required</h6>
                        </div>
                        <p class="mb-3 small">Please verify your email address to activate your account.</p>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <a href="${data.verification_link}" class="btn btn-primary w-100 mb-2" style="background-color: var(--color-primary); border-color: var(--color-primary);">
                                    <i class="bi bi-check-circle me-2"></i> Verify My Account
                                </a>

                                ${data.test_email ? `
                                <button type="button" class="btn btn-outline-secondary w-100 test-email-btn-${formId === 'loginForm' ? 'login' : 'register'}"
                                        data-email="${email}"
                                        data-token="${data.verification_link.split('token=')[1]}"
                                        data-name="${name}">
                                    <i class="bi bi-envelope me-2"></i> Send Verification Email
                                </button>
                                <div class="email-status-${formId === 'loginForm' ? 'login' : 'register'} mt-2 small"></div>
                                ` : ''}
                            </div>

                            ${data.qr_code ? `
                            <div class="col-md-4 d-flex flex-column align-items-center justify-content-center">
                                <p class="mb-2 small text-muted text-center">Or scan this QR code:</p>
                                <img src="${data.qr_code}" alt="Verification QR Code" class="img-fluid" style="max-width: 120px;">
                            </div>
                            ` : ''}
                        </div>
                    </div>`;
                }

                // Handle form submission
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Clear previous alerts
                    const alertEl = document.getElementById('loginAlert');
                    // Preserve any server-side messages
                    const serverMessages = alertEl.querySelectorAll('.custom-alert.alert-success');
                    if (serverMessages.length === 0) {
                        alertEl.innerHTML = '';
                    }

                    // Add loading indicator
                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'custom-alert alert-warning';
                    loadingDiv.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Logging in...';
                    alertEl.appendChild(loadingDiv);

                    const formData = new FormData(this);

                    // Send AJAX request to login handler
                    fetch('templates/includes/login_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Clear any existing alerts
                            alertEl.innerHTML = '';

                            // Show success message before redirect
                            const successDiv = document.createElement('div');
                            successDiv.className = 'custom-alert alert-success';
                            successDiv.innerHTML = `<i class="bi bi-check-circle me-2"></i> ${data.message}`;
                            alertEl.appendChild(successDiv);

                            // Redirect after a short delay
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        } else {
                            // Clear any existing alerts
                            alertEl.innerHTML = '';

                            // Show error message
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'custom-alert alert-danger';
                            errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i> ${data.message}`;
                            alertEl.appendChild(errorDiv);

                            // If verification link is provided, show verification UI
                            if (data.verification_link) {
                                // Create a more prominent verification UI
                                const verificationDiv = document.createElement('div');
                                verificationDiv.className = 'mt-3 p-3 border rounded verification-container';
                                verificationDiv.style.backgroundColor = '#f8f9fa';
                                verificationDiv.style.borderColor = '#dc3545 !important';
                                verificationDiv.style.borderWidth = '2px';

                                // Add content to the verification div
                                verificationDiv.innerHTML = `
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.25rem; color: #dc3545;"></i>
                                        <h6 class="mb-0 fw-bold">Please verify your email address</h6>
                                    </div>
                                    <p class="mb-3 small">Your account has been created but not yet verified. Please verify your email address to log in.</p>

                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <a href="${data.verification_link}" class="btn btn-primary w-100 mb-2" style="background-color: var(--color-primary); border-color: var(--color-primary);">
                                                <i class="bi bi-check-circle me-2"></i> Verify My Account
                                            </a>

                                            ${data.test_email ? `
                                            <button type="button" class="btn btn-outline-secondary w-100 test-email-btn-login"
                                                    data-email="${document.getElementById('loginForm').email.value}"
                                                    data-token="${data.verification_link.split('token=')[1]}"
                                                    data-name="User">
                                                <i class="bi bi-envelope me-2"></i> Send Verification Email
                                            </button>
                                            <div class="email-status-login mt-2 small"></div>
                                            ` : ''}
                                        </div>

                                        ${data.qr_code ? `
                                        <div class="col-md-4 d-flex flex-column align-items-center justify-content-center">
                                            <p class="mb-2 small text-muted text-center">Or scan this QR code:</p>
                                            <img src="${data.qr_code}" alt="Verification QR Code" class="img-fluid" style="max-width: 120px;">
                                        </div>
                                        ` : ''}
                                    </div>
                                `;

                                alertEl.appendChild(verificationDiv);

                                // Automatically open the verification link in a new tab
                                setTimeout(() => {
                                    window.open(data.verification_link, '_blank');
                                }, 1000);
                            }

                            // Setup test email button if present
                            setTimeout(() => {
                                setupTestEmailButton(document.querySelector('.test-email-btn-login'));
                            }, 100);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);

                        // Clear any existing alerts
                        alertEl.innerHTML = '';

                        // Show error message
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'custom-alert alert-danger';
                        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i> A network error occurred. Please check your connection and try again.';
                        alertEl.appendChild(errorDiv);
                    });
                });
            }

            // Handle register form submission
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Clear previous alerts
                    const alertEl = document.getElementById('registerAlert');

                    // Client-side validation
                    const firstName = this.first_name.value.trim();
                    const lastName = this.last_name.value.trim();
                    const email = this.email.value.trim();
                    const password = this.password.value;
                    const confirmPassword = this.confirm_password.value;
                    const terms = this.terms && this.terms.checked;

                    // Validation errors
                    let errors = [];

                    // Check required fields
                    if (!firstName || !lastName || !email || !password || !confirmPassword) {
                        errors.push('All fields are required.');
                    }

                    // Validate email
                    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        errors.push('Please enter a valid email address.');
                    }

                    // Validate password match
                    if (password && confirmPassword && password !== confirmPassword) {
                        errors.push('Passwords do not match.');
                    }

                    // Validate password strength
                    if (password) {
                        if (password.length < 8) {
                            errors.push('Password must be at least 8 characters long.');
                        } else {
                            // Calculate password strength
                            let strength = 0;
                            if (password.length >= 8) strength += 25;
                            if (/[a-z]/.test(password)) strength += 25;
                            if (/[A-Z]/.test(password)) strength += 25;
                            if (/[0-9]/.test(password) || /[^a-zA-Z0-9]/.test(password)) strength += 25;

                            if (strength < 50) {
                                errors.push('Please use a stronger password with uppercase letters, numbers, or special characters.');
                            }
                        }
                    }

                    // Check terms agreement
                    if (!terms) {
                        errors.push('You must agree to the Terms of Service and Privacy Policy.');
                    }

                    // Show first error if any
                    if (errors.length > 0) {
                        alertEl.innerHTML = `<div class="custom-alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i> ${errors[0]}
                        </div>`;
                        return;
                    }

                    // Clear previous alerts
                    alertEl.innerHTML = '';

                    // Show loading indicator
                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'custom-alert alert-warning';
                    loadingDiv.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Creating your account...';
                    alertEl.appendChild(loadingDiv);

                    // Send AJAX request to register handler
                    fetch('templates/includes/register_handler.php', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Clear any existing alerts
                            alertEl.innerHTML = '';

                            // Show success message
                            const successDiv = document.createElement('div');
                            successDiv.className = 'custom-alert alert-success';

                            // Customize the success message
                            let successMessage = 'Registration successful!';
                            if (data.verification_link && data.email_sent) {
                                successMessage = 'Registration successful! A verification email has been sent.';
                            }

                            successDiv.innerHTML = `<i class="bi bi-check-circle me-2"></i> ${successMessage}`;
                            alertEl.appendChild(successDiv);

                            // If verification link is provided and email was sent automatically
                            if (data.verification_link && data.email_sent) {
                                // Automatically open the verification link in a new tab after a delay
                                setTimeout(() => {
                                    window.open(data.verification_link, '_blank');
                                }, 1500);
                            }

                            // Reset the form
                            this.reset();

                            // Switch to login modal after delay
                            setTimeout(() => {
                                registerModal.hide();

                                const loginAlertEl = document.getElementById('loginAlert');
                                loginAlertEl.innerHTML = '';

                                // Create a single, clear message for the login modal
                                const successDiv = document.createElement('div');
                                successDiv.className = 'custom-alert alert-success';

                                if (data.verification_link && data.email_sent) {
                                    // If email was sent, show a message about verification
                                    successDiv.innerHTML = `
                                        <i class="bi bi-check-circle me-2"></i>
                                        Registration successful! A verification email has been sent to your address.
                                        <br><br>
                                        <small class="text-muted">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Please verify your email before logging in.
                                        </small>
                                    `;
                                } else {
                                    // Simple success message
                                    successDiv.innerHTML = '<i class="bi bi-check-circle me-2"></i> Registration successful!';
                                }

                                loginAlertEl.appendChild(successDiv);

                                loginModal.show();
                            }, 3000);
                        } else {
                            // Clear any existing alerts
                            alertEl.innerHTML = '';

                            // Show error message
                            const errorDiv = document.createElement('div');
                            errorDiv.className = 'custom-alert alert-danger';
                            errorDiv.innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i> ${data.message}`;
                            alertEl.appendChild(errorDiv);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);

                        // Clear any existing alerts
                        alertEl.innerHTML = '';

                        // Show error message
                        const errorDiv = document.createElement('div');
                        errorDiv.className = 'custom-alert alert-danger';
                        errorDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i> A network error occurred. Please check your connection and try again.';
                        alertEl.appendChild(errorDiv);
                    });
                });
            }
        });
    </script>
</body>
</html>
