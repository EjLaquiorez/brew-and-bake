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
                    <a href="templates/views/login.php" class="cart-icon">
                        <i class="bi bi-cart"></i>
                    </a>
                    <div class="user-menu">
                        <a href="#" class="user-icon">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <div class="user-dropdown">
                            <ul>
                                <li><a href="templates/views/login.php">Login</a></li>
                                <li><a href="templates/views/register.php">Register</a></li>
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
                                    <p class="product-category"><?= htmlspecialchars(ucfirst($product['category_name'] ?? 'Uncategorized')) ?></p>
                                    <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                                    <a href="templates/views/login.php" class="add-to-cart-btn">Order Now</a>
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
                                            <p class="product-category"><?= htmlspecialchars(ucfirst($category['name'])) ?></p>
                                            <div class="product-price">₱<?= number_format($product['price'], 2) ?></div>
                                            <a href="templates/views/login.php" class="add-to-cart-btn">Order Now</a>
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
                            <li><a href="templates/views/login.php">Login</a></li>
                            <li><a href="templates/views/register.php">Register</a></li>
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

                    <div id="loginAlert"></div>

                    <form id="loginForm" method="POST">
                        <div class="mb-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" name="email" class="form-control" placeholder="Email address" required autofocus>
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
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
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

                    <div id="registerAlert"></div>

                    <form id="registerForm" method="POST">
                        <div class="row mb-3">
                            <div class="col">
                                <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                            </div>
                            <div class="col">
                                <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                        </div>

                        <div class="mb-3">
                            <input type="password" name="password" id="reg_password" class="form-control" placeholder="Password" required>
                        </div>

                        <div class="mb-4">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
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

            // Toggle password visibility
            if (togglePasswordBtn && passwordField) {
                togglePasswordBtn.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);

                    // Toggle the eye icon
                    this.querySelector('i').classList.toggle('bi-eye');
                    this.querySelector('i').classList.toggle('bi-eye-slash');
                });
            }

            // Handle login form submission
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    // Send AJAX request to login handler
                    fetch('templates/includes/login_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect based on user role
                            window.location.href = data.redirect;
                        } else {
                            // Show error message
                            document.getElementById('loginAlert').innerHTML =
                                `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }

            // Handle register form submission
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    // Send AJAX request to register handler
                    fetch('templates/includes/register_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show success message and switch to login modal
                            document.getElementById('registerAlert').innerHTML =
                                `<div class="alert alert-success">${data.message}</div>`;

                            setTimeout(() => {
                                registerModal.hide();
                                document.getElementById('loginAlert').innerHTML =
                                    `<div class="alert alert-success">${data.message}</div>`;
                                loginModal.show();
                            }, 2000);
                        } else {
                            // Show error message
                            document.getElementById('registerAlert').innerHTML =
                                `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }
        });
    </script>
</body>
</html>
