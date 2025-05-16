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

// Helper function to get category image
function getCategoryImage($categoryName) {
    $defaultImage = "category-default.jpg";
    $categoryName = strtolower($categoryName);

    // Check for available PNG images in categories folder
    if ($categoryName == 'coffee') {
        return "coffee.png";
    } elseif ($categoryName == 'cake' || $categoryName == 'cakes') {
        return "cake.png";
    } elseif ($categoryName == 'pastry' || $categoryName == 'pastries') {
        return "pastries.png";
    } elseif ($categoryName == 'beverage' || $categoryName == 'beverages') {
        return "beverage.png";
    } elseif ($categoryName == 'sandwich' || $categoryName == 'sandwiches') {
        return "sandwich.png";
    }

    // Return default image if no match
    return $defaultImage;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brew & Bake - Premium Coffee House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/menu.css">
    <link rel="preload" href="assets/images/backgrounds/cafe-interior.jpg" as="image">
    <style>
        /* Simple Landing Page Styles */
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--color-gray-700);
            overflow-x: hidden;
        }

        .hero-section {
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('assets/images/backgrounds/pic-2.jfif');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .hero-content {
            max-width: 800px;
            padding: 2rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            font-weight: 300;
        }

        .btn-primary-custom {
            background-color: var(--color-secondary);
            border: 2px solid var(--color-secondary);
            color: var(--color-primary-dark);
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary-custom:hover {
            background-color: transparent;
            color: var(--color-secondary);
        }

        .btn-outline-light {
            border: 2px solid white;
            color: white;
            padding: 0.75rem 2rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-outline-light:hover {
            background-color: white;
            color: var(--color-primary-dark);
        }

        .category-section {
            padding: 5rem 0;
            background-color: var(--color-gray-50);
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            font-weight: 700;
            color: var(--color-primary);
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background-color: var(--color-secondary);
        }

        /* Menu Grid Layout */
        .menu-grid-container {
            margin-bottom: 2rem;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            grid-auto-flow: dense;
        }

        .menu-grid .category-item {
            transition: all 0.4s ease;
            position: relative;
        }

        .menu-grid .category-item:hover {
            transform: translateY(-5px);
        }

        .menu-grid .category-item:nth-child(5n+1) {
            grid-column: span 1;
        }

        .menu-grid .category-item:nth-child(5n+3) {
            grid-row: span 1;
        }

        .category-card {
            background-color: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .category-card:hover .category-img img {
            transform: scale(1.05);
        }

        .category-img {
            height: 200px;
            background: linear-gradient(145deg, var(--color-primary-dark) 0%, var(--color-primary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .category-img::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .category-card:hover .category-img::after {
            opacity: 1;
        }

        .category-img img {
            max-height: 80%;
            max-width: 80%;
            object-fit: contain;
            transition: transform 0.5s ease;
            filter: drop-shadow(0 5px 10px rgba(0, 0, 0, 0.2));
        }

        .category-info {
            padding: 1.5rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            justify-content: space-between;
            background: white;
            position: relative;
        }

        .category-info::before {
            content: '';
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 30px;
            background: white;
            border-radius: 4px;
            transform-origin: center;
            transform: translateX(-50%) rotate(45deg);
            z-index: 1;
        }

        .category-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--color-primary);
            font-size: 1.25rem;
            position: relative;
            z-index: 2;
        }

        .category-description {
            color: var(--color-gray-600);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            position: relative;
            z-index: 2;
        }

        .category-meta {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1rem;
            position: relative;
            z-index: 2;
        }

        .category-meta .meta-item {
            display: flex;
            align-items: center;
            margin: 0 0.5rem;
            color: var(--color-gray-600);
            font-size: 0.85rem;
        }

        .category-meta .meta-item i {
            margin-right: 0.25rem;
            color: var(--color-secondary);
        }

        .category-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--color-secondary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .footer {
            background-color: var(--color-primary-dark);
            color: var(--color-gray-300);
            padding: 3rem 0;
            text-align: center;
        }

        .footer-logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            display: inline-flex;
            align-items: center;
        }

        .footer-logo i {
            color: var(--color-secondary);
            margin-right: 0.5rem;
            font-size: 2rem;
        }

        .social-links {
            margin: 1.5rem 0;
        }

        .social-links a {
            color: var(--color-gray-300);
            font-size: 1.5rem;
            margin: 0 0.5rem;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--color-secondary);
        }

        .copyright {
            font-size: 0.9rem;
            margin-top: 2rem;
        }

        /* Header Styles */
        .site-header {
            background-color: var(--color-primary);
            padding: 1rem 0;
            position: absolute;
            width: 100%;
            z-index: 100;
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
            color: white;
        }

        .logo i {
            color: var(--color-secondary);
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }

        .header-actions a {
            color: white;
            font-size: 1.2rem;
            margin-left: 1.5rem;
            transition: color 0.3s ease;
        }

        .header-actions a:hover {
            color: var(--color-secondary);
        }

        /* About Section Styles */
        .about-section {
            background-color: var(--color-white);
        }

        .about-image img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            background-color: var(--color-gray-200);
        }

        .about-content {
            padding: 2rem;
        }

        .about-content .section-title::after {
            left: 0;
            transform: none;
        }

        /* Contact Section Styles */
        .contact-section {
            background-color: var(--color-gray-50);
        }

        .contact-info, .contact-form {
            height: 100%;
            transition: all 0.3s ease;
        }

        .contact-info:hover, .contact-form:hover {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-control {
            border: 1px solid var(--color-gray-200);
            padding: 0.75rem;
            border-radius: 5px;
        }

        .form-control:focus {
            border-color: var(--color-secondary);
            box-shadow: 0 0 0 0.2rem rgba(212, 163, 115, 0.25);
        }

        .form-label {
            color: var(--color-gray-700);
            font-weight: 500;
        }

        /* Improved Modal Styling */
        .modal-content {
            border: none;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .modal-header {
            background-color: transparent;
            border-bottom: none;
            padding-bottom: 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .brand-logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .brand-logo-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(145deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border: 3px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .brand-logo-circle::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: radial-gradient(circle at center, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            opacity: 0.7;
        }

        .brand-logo-circle i {
            font-size: 2.5rem;
            color: var(--color-secondary);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
            position: relative;
            z-index: 2;
        }

        .input-group-lg .input-group-text {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
            border: 1px solid var(--color-gray-200);
            border-right: none;
            color: var(--color-gray-600);
        }

        .input-group-lg .form-control {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            border: 1px solid var(--color-gray-200);
            border-left: none;
            padding: 0.75rem 1rem;
            font-size: 1rem;
        }

        .input-group-lg .form-control:focus {
            box-shadow: none;
            border-color: var(--color-secondary);
        }

        .input-group-lg .form-control:focus + .input-group-text {
            border-color: var(--color-secondary);
        }

        .input-group-lg .toggle-password {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            border: 1px solid var(--color-gray-200);
            border-left: none;
            background-color: transparent;
        }

        .input-group-lg .toggle-password:hover {
            background-color: var(--color-gray-100);
        }

        .form-check-input:checked {
            background-color: var(--color-secondary);
            border-color: var(--color-secondary);
        }

        .forgot-password {
            color: var(--color-secondary);
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--color-primary);
            text-decoration: underline !important;
        }

        /* Improved header login icon */
        .header-actions .login-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .header-actions .login-icon:hover {
            background-color: var(--color-secondary);
            transform: translateY(-3px);
        }

        .header-actions .login-icon i {
            font-size: 1.2rem;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Simple Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="index.php">
                        <i class="bi bi-cup-hot"></i>
                        <span>Brew & Bake</span>
                    </a>
                </div>
                <div class="header-actions">
                    <?php if ($isLoggedIn): ?>
                        <a href="<?= $userRole === 'client' ? 'templates/client/orders.php' : '#' ?>">
                            <i class="bi bi-cart"></i>
                        </a>
                        <a href="<?= $userRole === 'admin' ? 'templates/admin/dashboard.php' :
                                  ($userRole === 'staff' ? 'templates/staff/staff.php' :
                                  'templates/client/client.php') ?>">
                            <i class="bi bi-person-circle"></i>
                        </a>
                    <?php else: ?>
                        <a href="#" class="login-icon" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-person"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">PREMIUM COFFEE & FRESH BAKERY</h1>
                <p class="hero-subtitle">Experience the perfect blend of premium coffee and freshly baked goods, crafted with passion and the finest ingredients.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="templates/client/client.php" class="btn btn-primary-custom">EXPLORE MENU</a>
                    <a href="#categories" class="btn btn-outline-light">DISCOVER MORE</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="category-section">
        <div class="container">
            <h2 class="section-title">OUR MENU</h2>

            <div class="menu-grid-container">
                <div class="menu-grid">
                    <?php
                    // Define category descriptions and icons
                    $categoryDetails = [
                        'coffee' => [
                            'description' => 'Premium coffee beans, expertly brewed',
                            'icon' => 'bi-cup-hot',
                            'items' => '8+ varieties'
                        ],
                        'cake' => [
                            'description' => 'Delicious cakes for all occasions',
                            'icon' => 'bi-cake2',
                            'items' => '6+ varieties'
                        ],
                        'pastry' => [
                            'description' => 'Freshly baked pastries daily',
                            'icon' => 'bi-egg-fried',
                            'items' => '10+ varieties'
                        ],
                        'beverage' => [
                            'description' => 'Refreshing cold and hot drinks',
                            'icon' => 'bi-cup-straw',
                            'items' => '12+ varieties'
                        ],
                        'dessert' => [
                            'description' => 'Sweet treats to satisfy your cravings',
                            'icon' => 'bi-pie-chart',
                            'items' => '8+ varieties'
                        ],
                        'sandwiches' => [
                            'description' => 'Freshly made gourmet sandwiches',
                            'icon' => 'bi-layers',
                            'items' => '6+ varieties'
                        ]
                    ];

                    // Default values for categories without specific details
                    $defaultDetails = [
                        'description' => 'Explore our delicious selection',
                        'icon' => 'bi-basket',
                        'items' => 'Various options'
                    ];

                    // Get badges for categories
                    $badges = [
                        'coffee' => 'Popular',
                        'cake' => 'Bestseller',
                        'pastry' => 'Fresh Daily',
                        'sandwiches' => 'New'
                    ];

                    foreach ($categories as $index => $category):
                        $categoryName = strtolower($category['name']);
                        $details = $categoryDetails[$categoryName] ?? $defaultDetails;
                        $hasBadge = isset($badges[$categoryName]);
                    ?>
                    <div class="category-item">
                        <a href="templates/client/client.php#<?= $categoryName ?>" class="text-decoration-none">
                            <div class="category-card">
                                <?php if ($hasBadge): ?>
                                <span class="category-badge"><?= $badges[$categoryName] ?></span>
                                <?php endif; ?>
                                <div class="category-img">
                                    <img src="assets/images/categories/<?= getCategoryImage($category['name']) ?>" alt="<?= htmlspecialchars(ucfirst($category['name'])) ?>">
                                </div>
                                <div class="category-info">
                                    <div>
                                        <h3 class="category-title"><?= htmlspecialchars(ucfirst($category['name'])) ?></h3>
                                        <p class="category-description"><?= $details['description'] ?></p>
                                    </div>
                                    <div class="category-meta">
                                        <div class="meta-item">
                                            <i class="bi <?= $details['icon'] ?>"></i>
                                            <span><?= $details['items'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="templates/client/client.php" class="btn btn-primary-custom">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="about-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="about-image">
                        <img src="assets/images/backgrounds/cafe-interior.jpg" alt="Brew & Bake Interior" class="img-fluid rounded shadow" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1554118811-1e0d58224f24?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxzZWFyY2h8M3x8Y2FmZSUyMGludGVyaW9yfGVufDB8fDB8fHww&w=1000&q=80';">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-content">
                        <h2 class="section-title text-start">ABOUT US</h2>
                        <p class="lead mb-4">Crafting Premium Coffee & Delightful Pastries Since 2020</p>
                        <p class="mb-4">At Brew & Bake, we believe in the perfect harmony between exceptional coffee and freshly baked goods. Our journey began with a simple passion for quality and has evolved into a beloved destination for coffee enthusiasts and pastry lovers alike.</p>
                        <p class="mb-4">We source only the finest coffee beans from sustainable farms around the world, ensuring each cup tells a story of dedication and craftsmanship. Our bakers arrive at dawn each day to prepare fresh pastries, cakes, and bread using traditional recipes and premium ingredients.</p>
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h5 class="mb-0">Premium Ingredients</h5>
                                        <p class="text-muted mb-0">Locally sourced when possible</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h5 class="mb-0">Skilled Baristas</h5>
                                        <p class="text-muted mb-0">Trained coffee artisans</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h5 class="mb-0">Fresh Daily</h5>
                                        <p class="text-muted mb-0">Baked goods made each morning</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
                                    <div>
                                        <h5 class="mb-0">Cozy Atmosphere</h5>
                                        <p class="text-muted mb-0">Designed for comfort</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Us Section -->
    <section id="contact" class="contact-section py-5 bg-light">
        <div class="container">
            <h2 class="section-title">CONTACT US</h2>
            <p class="text-center text-muted mb-5">We'd love to hear from you! Reach out with any questions or feedback.</p>

            <div class="row">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="contact-info bg-white p-4 rounded shadow-sm h-100">
                        <h4 class="mb-4">Get In Touch</h4>

                        <div class="d-flex mb-4">
                            <div class="contact-icon me-3">
                                <i class="bi bi-geo-alt-fill text-secondary" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h5>Location</h5>
                                <p class="text-muted mb-0">123 Coffee Street, Manila, Philippines</p>
                            </div>
                        </div>

                        <div class="d-flex mb-4">
                            <div class="contact-icon me-3">
                                <i class="bi bi-clock-fill text-secondary" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h5>Opening Hours</h5>
                                <p class="text-muted mb-0">Monday - Friday: 7:00 AM - 8:00 PM</p>
                                <p class="text-muted mb-0">Saturday - Sunday: 8:00 AM - 9:00 PM</p>
                            </div>
                        </div>

                        <div class="d-flex mb-4">
                            <div class="contact-icon me-3">
                                <i class="bi bi-telephone-fill text-secondary" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h5>Call Us</h5>
                                <p class="text-muted mb-0">+63 (2) 8123 4567</p>
                            </div>
                        </div>

                        <div class="d-flex">
                            <div class="contact-icon me-3">
                                <i class="bi bi-envelope-fill text-secondary" style="font-size: 1.5rem;"></i>
                            </div>
                            <div>
                                <h5>Email Us</h5>
                                <p class="text-muted mb-0">info@brewandbake.com</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="contact-form bg-white p-4 rounded shadow-sm">
                        <h4 class="mb-4">Send a Message</h4>
                        <form id="contactForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="name" placeholder="Enter your name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Your Email</label>
                                    <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" placeholder="Enter subject">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="5" placeholder="Enter your message" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary-custom">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-logo">
                <i class="bi bi-cup-hot"></i>
                <span>Brew & Bake</span>
            </div>
            <p>Premium coffee and fresh bakery goods</p>
            <div class="social-links">
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-twitter"></i></a>
                <a href="#"><i class="bi bi-pinterest"></i></a>
            </div>
            <p class="copyright">© 2025 Brew & Bake. All rights reserved.</p>
        </div>
    </footer>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="text-center mb-4">
                        <div class="brand-logo-container mb-3">
                            <div class="brand-logo-circle">
                                <i class="bi bi-cup-hot"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold">Welcome Back</h4>
                        <p class="text-muted">Sign in to your Brew & Bake account</p>
                    </div>
                    <form id="loginForm" action="templates/includes/login_process.php" method="post">
                        <div class="mb-3">
                            <label for="login_email" class="form-label">Email Address</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="login_email" placeholder="Enter your email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="password" class="form-label">Password</label>
                                <a href="#" class="text-decoration-none small forgot-password">Forgot Password?</a>
                            </div>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                            <label class="form-check-label remember-me" for="rememberMe">Keep me signed in</label>
                        </div>
                        <div class="alert alert-danger login-error" style="display: none;"></div>
                        <button type="submit" class="btn btn-primary-custom btn-lg w-100 mb-3">Sign In</button>
                    </form>
                    <div class="text-center mt-4">
                        <p class="mb-0">Don't have an account? <a href="#" class="register-link fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#registerModal">Create Account</a></p>
                    </div>
                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="text-muted small mb-0">By signing in, you agree to our <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="text-center mb-4">
                        <div class="brand-logo-container mb-3">
                            <div class="brand-logo-circle">
                                <i class="bi bi-cup-hot"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold">Create Your Account</h4>
                        <p class="text-muted">Join the Brew & Bake community</p>
                    </div>
                    <form id="registerForm" action="templates/includes/register_handler.php" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" name="first_name" id="first_name" placeholder="Enter first name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0">
                                <label for="last_name" class="form-label">Last Name</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" name="last_name" id="last_name" placeholder="Enter last name" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="register_email" class="form-label">Email Address</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" name="email" id="register_email" placeholder="Enter your email" required>
                            </div>
                            <div class="form-text text-muted">We'll send a verification link to this email</div>
                        </div>
                        <div class="mb-3">
                            <label for="register_password" class="form-label">Password</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" name="password" id="register_password" placeholder="Create a password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength mt-2">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="form-text password-feedback text-muted">Password must be at least 8 characters long.</small>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="password-match-feedback mt-1"></div>
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label remember-me" for="terms">I agree to the <a href="#" class="text-decoration-none">Terms of Service</a> and <a href="#" class="text-decoration-none">Privacy Policy</a></label>
                        </div>
                        <div class="alert alert-danger registration-error" style="display: none;"></div>
                        <button type="submit" class="btn btn-primary-custom btn-lg w-100 mb-3">Create Account</button>
                    </form>
                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? <a href="#" class="login-link fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#loginModal">Sign In</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Success Modal -->
    <div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4 text-center">
                    <div class="verification-icon mb-4">
                        <div class="brand-logo-circle" style="background: linear-gradient(145deg, #28a745 0%, #20c997 100%);">
                            <i class="bi bi-envelope-check" style="color: white;"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-3">Verification Email Sent</h4>
                    <p class="text-muted mb-4">We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.</p>
                    <div class="verification-link-container mt-4" style="display: none;">
                        <div class="alert alert-info p-4 rounded-3 shadow-sm">
                            <p class="mb-3"><strong>Didn't receive the email?</strong></p>
                            <p class="mb-3">You can use the verification link below:</p>
                            <div class="p-3 bg-light rounded mb-3">
                                <a href="#" class="verification-link text-break" target="_blank"></a>
                            </div>
                        </div>
                        <div class="qr-code-container mt-4 mb-4 text-center">
                            <p class="mb-3">Or scan this QR code:</p>
                            <div class="d-flex justify-content-center">
                                <div class="p-3 bg-white rounded shadow-sm d-inline-block">
                                    <img src="" alt="Verification QR Code" class="qr-code-image img-fluid" style="max-width: 150px;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary-custom btn-lg mt-3" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            document.querySelectorAll('.toggle-password').forEach(button => {
                button.addEventListener('click', function() {
                    const input = this.closest('.input-group').querySelector('input');
                    const icon = this.querySelector('i');

                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                });
            });

            // Password strength meter
            const passwordInput = document.getElementById('register_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const progressBar = document.querySelector('.progress-bar');
            const passwordFeedback = document.querySelector('.password-feedback');
            const passwordMatchFeedback = document.querySelector('.password-match-feedback');

            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    let feedback = '';

                    // Length check
                    if (password.length >= 8) {
                        strength += 25;
                    } else {
                        feedback = 'Password must be at least 8 characters long.';
                    }

                    // Lowercase letters check
                    if (password.match(/[a-z]/)) {
                        strength += 25;
                    }

                    // Uppercase letters check
                    if (password.match(/[A-Z]/)) {
                        strength += 25;
                    }

                    // Numbers or special characters check
                    if (password.match(/[0-9]/) || password.match(/[^a-zA-Z0-9]/)) {
                        strength += 25;
                    }

                    // Update progress bar
                    progressBar.style.width = strength + '%';
                    progressBar.setAttribute('aria-valuenow', strength);

                    // Update color based on strength
                    if (strength < 50) {
                        progressBar.classList.remove('bg-warning', 'bg-success');
                        progressBar.classList.add('bg-danger');
                        if (!feedback) feedback = 'Weak password';
                    } else if (strength < 75) {
                        progressBar.classList.remove('bg-danger', 'bg-success');
                        progressBar.classList.add('bg-warning');
                        if (!feedback) feedback = 'Medium strength password';
                    } else {
                        progressBar.classList.remove('bg-danger', 'bg-warning');
                        progressBar.classList.add('bg-success');
                        if (!feedback) feedback = 'Strong password';
                    }

                    passwordFeedback.textContent = feedback;

                    // Check password match if confirm password has value
                    if (confirmPasswordInput.value) {
                        checkPasswordMatch();
                    }
                });
            }

            // Password match check
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            }

            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (confirmPassword === '') {
                    passwordMatchFeedback.textContent = '';
                    passwordMatchFeedback.className = 'password-match-feedback mt-1';
                } else if (password === confirmPassword) {
                    passwordMatchFeedback.textContent = 'Passwords match';
                    passwordMatchFeedback.className = 'password-match-feedback mt-1 text-success';
                } else {
                    passwordMatchFeedback.textContent = 'Passwords do not match';
                    passwordMatchFeedback.className = 'password-match-feedback mt-1 text-danger';
                }
            }

            // Handle login form submission
            const loginForm = document.querySelector('form[action="templates/includes/login_process.php"]');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    formData.append('remember', document.getElementById('rememberMe').checked ? '1' : '0');

                    fetch('templates/includes/login_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect on successful login
                            window.location.href = data.redirect;
                        } else {
                            // Show error message
                            let errorContainer = document.querySelector('.login-error');
                            if (!errorContainer) {
                                errorContainer = document.createElement('div');
                                errorContainer.className = 'alert alert-danger login-error mt-3';
                                loginForm.insertBefore(errorContainer, loginForm.querySelector('button[type="submit"]'));
                            }
                            errorContainer.textContent = data.message;
                            errorContainer.style.display = 'block';

                            // If verification needed, show verification info
                            if (data.verification_link) {
                                const verificationModal = new bootstrap.Modal(document.getElementById('verificationModal'));
                                document.querySelector('.verification-link-container').style.display = 'block';
                                document.querySelector('.verification-link').href = data.verification_link;
                                document.querySelector('.verification-link').textContent = data.verification_link;
                                document.querySelector('.qr-code-image').src = data.qr_code;
                                verificationModal.show();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }

            // Handle registration form submission
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    formData.append('terms', document.getElementById('terms').checked ? '1' : '0');

                    fetch('templates/includes/register_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Hide registration modal
                            const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                            registerModal.hide();

                            // Show verification modal
                            const verificationModal = new bootstrap.Modal(document.getElementById('verificationModal'));

                            // If test email is enabled, show verification link
                            if (data.test_email) {
                                document.querySelector('.verification-link-container').style.display = 'block';
                                document.querySelector('.verification-link').href = data.verification_link;
                                document.querySelector('.verification-link').textContent = data.verification_link;
                                document.querySelector('.qr-code-image').src = data.qr_code;
                            } else {
                                document.querySelector('.verification-link-container').style.display = 'none';
                            }

                            verificationModal.show();

                            // Reset form
                            registerForm.reset();
                        } else {
                            // Show error message
                            const errorContainer = document.querySelector('.registration-error');
                            errorContainer.textContent = data.message;
                            errorContainer.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }

            // Modal switching
            const loginLink = document.querySelector('.login-link');
            const registerLink = document.querySelector('.register-link');

            if (loginLink) {
                loginLink.addEventListener('click', function(e) {
                    const registerModal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
                    if (registerModal) {
                        registerModal.hide();
                    }
                });
            }

            if (registerLink) {
                registerLink.addEventListener('click', function(e) {
                    const loginModal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
                    if (loginModal) {
                        loginModal.hide();
                    }
                });
            }

            // Handle contact form submission
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Get form values
                    const name = document.getElementById('name').value;
                    const email = document.getElementById('email').value;
                    const subject = document.getElementById('subject').value;
                    const message = document.getElementById('message').value;

                    // Simple validation
                    if (!name || !email || !message) {
                        alert('Please fill in all required fields');
                        return;
                    }

                    // In a real application, you would send this data to a server
                    // For now, we'll just show a success message
                    alert('Thank you for your message! We will get back to you soon.');

                    // Reset the form
                    contactForm.reset();
                });
            }

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    if (this.getAttribute('href') !== '#' &&
                        !this.getAttribute('href').includes('Modal') &&
                        document.querySelector(this.getAttribute('href'))) {
                        e.preventDefault();

                        document.querySelector(this.getAttribute('href')).scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>