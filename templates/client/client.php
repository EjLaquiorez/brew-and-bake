<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

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
    <link rel="stylesheet" href="../../assets/css/menu.css?v=<?= time() ?>">
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
            background-color: #111827;
            position: relative;
            z-index: 49; /* Lower than menu-nav */
            padding: 0.75rem 0;
        }

        /* Make menu-nav sticky */
        .menu-nav {
            position: sticky;
            top: 0;
            z-index: 50; /* Lower than dropdown menus but higher than regular content */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
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

        /* Main navigation styles removed */

        /* Header actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
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
        }

        .user-dropdown li a:hover,
        .user-dropdown li a.active {
            background-color: #334155;
            color: #ffffff;
        }

        /* Menu tabs styling */
        .menu-tabs {
            display: flex;
            padding: 0;
            margin: 0;
            max-width: 1200px;
            margin: 0 auto;
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
            text-transform: capitalize;
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

        /* Add scroll class for menu-nav */
        .menu-nav.scrolled {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Button styling */
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

        /* Add to Cart Modal Styles */
        .product-modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
        }

        .product-modal-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
            padding: 1rem 1.5rem;
        }

        .product-image-container {
            width: 100%;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            margin-right: 1rem;
        }

        .product-image-container img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            z-index: 1;
        }

        .product-image-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dee2e6;
            font-size: 3rem;
            z-index: 0;
        }

        .product-details-container {
            padding: 0 0.5rem 0 1rem;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #111827;
            line-height: 1.3;
        }

        .product-category-badge {
            background-color: #f59e0b;
            color: #111827;
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        .stock-badge {
            background-color: #10b981;
            color: white;
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
            margin-left: 0.5rem;
        }

        .product-description {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.5;
            max-height: 4.5rem;
            overflow-y: auto;
        }

        .product-modal-divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 1rem 0;
        }

        .quantity-control-modal {
            max-width: 180px;
        }

        .quantity-control-modal .form-control {
            text-align: center;
            font-weight: 600;
            border-radius: 0;
            height: 42px;
            border-color: #e5e7eb;
        }

        .btn-quantity {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
            border-color: #e5e7eb;
            color: #111827;
            font-size: 1rem;
            padding: 0;
        }

        .btn-quantity:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
        }

        .btn-quantity:first-child {
            border-radius: 6px 0 0 6px;
        }

        .btn-quantity:last-child {
            border-radius: 0 6px 6px 0;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .subtotal-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }

        /* Add to cart success animation */
        .add-to-cart-success {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1060;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .add-to-cart-success.show {
            opacity: 1;
            visibility: visible;
        }

        .success-icon {
            font-size: 3rem;
            color: #10b981;
            margin-bottom: 1rem;
            animation: scaleIn 0.5s ease;
        }

        .success-message {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
        }

        @keyframes scaleIn {
            0% { transform: scale(0); }
            70% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        /* Toast notification styles */
        .toast-container {
            z-index: 1070;
        }

        .toast {
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0.75rem;
            min-width: 280px;
        }

        .toast.bg-success {
            background-color: rgba(16, 185, 129, 0.95) !important;
        }

        .toast-body {
            padding: 0.75rem 1rem 0.5rem;
            font-weight: 500;
        }

        .toast-actions {
            display: flex;
            align-items: center;
        }

        .toast-actions .btn-light {
            background-color: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 255, 255, 0.9);
            color: #10b981;
            font-weight: 500;
            width: 100%;
        }

        @media (max-width: 768px) {
            .menu-tabs {
                justify-content: space-between;
            }

            .menu-tabs a {
                padding: 0.75rem 0.75rem;
                font-size: 0.8rem;
            }

            /* Mobile modal adjustments */
            .product-image-container {
                height: 140px;
                margin-right: 0;
                margin-bottom: 1rem;
            }

            .product-modal-top .row {
                flex-direction: column;
            }

            .product-modal-top .col-5,
            .product-modal-top .col-7 {
                width: 100%;
                max-width: 100%;
                flex: 0 0 100%;
            }

            .product-details-container {
                padding: 0;
            }

            .product-modal-title {
                font-size: 1.1rem;
            }

            .product-price {
                font-size: 1.25rem;
            }

            .subtotal-container {
                text-align: left !important;
                margin-top: 1rem !important;
            }

            .product-modal-bottom .row {
                flex-direction: column;
            }

            .product-modal-bottom .col-md-6 {
                width: 100%;
                max-width: 100%;
                flex: 0 0 100%;
            }

            .modal-footer {
                flex-direction: column;
                gap: 0.5rem;
            }

            .modal-footer .btn {
                width: 100%;
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
                <li><a href="client.php" class="active">Menu</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="profile.php">Account Settings</a></li>
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
                <section id="menu-section" class="menu-section">
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
                <section id="featured" class="menu-section">
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
                                    <p class="text-muted"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 60)) . (isset($product['description']) && strlen($product['description']) > 60 ? '...' : '') ?></p>
                                    <div class="product-meta">
                                        <span class="category"><?= htmlspecialchars(ucfirst($product['category_name'] ?? 'Uncategorized')) ?></span>
                                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                    </div>
                                    <?php if ($isLoggedIn && $userRole === 'client'): ?>
                                        <div class="d-flex flex-column">
                                            <small class="text-muted mb-1">Stock: <?= $product['stock'] ?></small>
                                            <button type="button" class="btn btn-primary w-100 add-to-cart-btn"
                                                data-product-id="<?= $product['id'] ?>"
                                                data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                                data-product-price="<?= $product['price'] ?>"
                                                data-product-image="<?= !empty($product['image']) ? htmlspecialchars($product['image']) : '' ?>"
                                                data-product-stock="<?= $product['stock'] ?>">
                                                <i class="bi bi-cart-plus"></i> Order Now
                                                <?php if ($product['stock'] <= 0): ?>
                                                    <span class="badge bg-danger ms-1">Out of Stock</span>
                                                <?php endif; ?>
                                            </button>
                                        </div>
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
                                                <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
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
                                                <div class="d-flex flex-column">
                                                    <small class="text-muted mb-1">Stock: <?= $product['stock'] ?></small>
                                                    <button type="button" class="btn btn-primary w-100 add-to-cart-btn"
                                                        data-product-id="<?= $product['id'] ?>"
                                                        data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                                        data-product-price="<?= $product['price'] ?>"
                                                        data-product-image="<?= !empty($product['image']) ? htmlspecialchars($product['image']) : '' ?>"
                                                        data-product-category="<?= htmlspecialchars(ucfirst($category['name'])) ?>"
                                                        data-product-stock="<?= $product['stock'] ?>">
                                                        <i class="bi bi-cart-plus"></i> Order Now
                                                        <?php if ($product['stock'] <= 0): ?>
                                                            <span class="badge bg-danger ms-1">Out of Stock</span>
                                                        <?php endif; ?>
                                                    </button>
                                                </div>
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
                    <img src="../../assets/images/about.jpg" alt="Our Coffee Shop" class="img-fluid rounded">
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
                            <li><a href="../../index.php">Home</a></li>
                            <li><a href="#featured">Featured</a></li>
                            <?php if ($isLoggedIn): ?>
                                <?php if ($userRole === 'admin'): ?>
                                    <li><a href="../../templates/admin/dashboard.php">Admin Dashboard</a></li>
                                <?php elseif ($userRole === 'staff'): ?>
                                    <li><a href="../../templates/staff/staff.php">Staff Dashboard</a></li>
                                <?php else: ?>
                                    <li><a href="profile.php">Account Settings</a></li>
                                <?php endif; ?>
                                <li><a href="../includes/logout.php">Logout</a></li>
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

                        <button type="submit" class="btn btn-primary w-100 mb-4" style="background-color: #f59e0b; border-color: #f59e0b; color: #111827;">
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

    <!-- Add to Cart Modal -->
    <div class="modal fade" id="addToCartModal" tabindex="-1" aria-labelledby="addToCartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header product-modal-header">
                    <h5 class="modal-title" id="addToCartModalLabel">Add to Cart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="product-modal-top mb-4">
                        <div class="row g-0">
                            <div class="col-5">
                                <div class="product-image-container">
                                    <img id="modal-product-image" src="" alt="Product" class="img-fluid rounded">
                                    <div class="product-image-placeholder">
                                        <i class="bi bi-cup-hot"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="product-details-container">
                                    <div class="product-badges mb-2">
                                        <span id="modal-product-category" class="badge product-category-badge"></span>
                                        <span id="modal-product-stock" class="badge stock-badge">In Stock</span>
                                    </div>
                                    <div id="stock-warning" class="alert alert-warning d-none mb-2" style="font-size: 0.8rem; padding: 0.5rem;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                        <span id="stock-warning-text"></span>
                                    </div>
                                    <h4 id="modal-product-name" class="product-modal-title"></h4>
                                    <p class="product-price">₱<span id="modal-product-price"></span></p>
                                    <div id="modal-product-description" class="product-description small text-muted mb-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="product-modal-divider"></div>

                    <div class="product-modal-bottom mt-4">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label for="product-quantity" class="form-label fw-bold">Quantity</label>
                                <div class="quantity-control-modal d-flex align-items-center">
                                    <button type="button" class="btn btn-quantity" id="decrease-quantity">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <input type="number" id="product-quantity" class="form-control mx-2" value="1" min="1" max="99">
                                    <button type="button" class="btn btn-quantity" id="increase-quantity">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="subtotal-container text-md-end mt-3 mt-md-0">
                                    <div class="text-muted small">Subtotal</div>
                                    <div class="subtotal-price">₱<span id="modal-subtotal"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer product-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-add-to-cart">
                        <i class="bi bi-cart-plus me-2"></i>Add to Cart
                    </button>
                </div>

                <!-- Add to Cart Success Message -->
                <div class="add-to-cart-success" id="add-to-cart-success">
                    <div class="success-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="success-message">Added to cart!</div>
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

                        <button type="submit" class="btn btn-primary w-100 mb-4" style="background-color: #f59e0b; border-color: #f59e0b; color: #111827;">
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

    <?php
    $root_path = '../../';
    include_once "../../templates/includes/footer-scripts.php";
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuNav = document.querySelector('.menu-nav');

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

            // Fetch cart items using AJAX
            if (typeof loadCartItems === 'function') {
                loadCartItems();
            }

            // Add to Cart Modal Functionality
            const addToCartModal = new bootstrap.Modal(document.getElementById('addToCartModal'));
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            const productQuantityInput = document.getElementById('product-quantity');
            const decreaseQuantityBtn = document.getElementById('decrease-quantity');
            const increaseQuantityBtn = document.getElementById('increase-quantity');
            const confirmAddToCartBtn = document.getElementById('confirm-add-to-cart');
            const stockWarning = document.getElementById('stock-warning');
            const stockWarningText = document.getElementById('stock-warning-text');

            let currentProductId = null;
            let currentProductPrice = 0;
            let currentProductStock = 0;
            let currentCartQuantity = 0;

            // Update subtotal based on quantity
            function updateSubtotal() {
                const quantity = parseInt(productQuantityInput.value);
                const subtotal = (currentProductPrice * quantity).toFixed(2);
                document.getElementById('modal-subtotal').textContent = subtotal;
            }

            // Check stock and update UI accordingly
            function checkStockLimits() {
                const quantity = parseInt(productQuantityInput.value);
                const availableStock = currentProductStock - currentCartQuantity;

                // Reset styles
                decreaseQuantityBtn.classList.remove('btn-danger');
                increaseQuantityBtn.classList.remove('btn-danger');
                productQuantityInput.classList.remove('text-danger', 'border-danger');
                stockWarning.classList.add('d-none');

                // Check if we're at stock limits
                if (currentProductStock <= 0) {
                    // Out of stock
                    productQuantityInput.value = 0;
                    productQuantityInput.disabled = true;
                    decreaseQuantityBtn.disabled = true;
                    increaseQuantityBtn.disabled = true;
                    confirmAddToCartBtn.disabled = true;

                    stockWarningText.textContent = "This product is out of stock.";
                    stockWarning.classList.remove('d-none');

                    // Update stock badge
                    document.getElementById('modal-product-stock').textContent = "Out of Stock";
                    document.getElementById('modal-product-stock').className = "badge bg-danger";

                    return;
                }

                // Enable controls
                productQuantityInput.disabled = false;
                confirmAddToCartBtn.disabled = false;

                if (availableStock <= 0) {
                    // All stock is in cart
                    productQuantityInput.value = 0;
                    productQuantityInput.disabled = true;
                    decreaseQuantityBtn.disabled = true;
                    increaseQuantityBtn.disabled = true;
                    confirmAddToCartBtn.disabled = true;

                    stockWarningText.textContent = "You already have all available items in your cart.";
                    stockWarning.classList.remove('d-none');

                    // Update stock badge
                    document.getElementById('modal-product-stock').textContent = "In Cart: " + currentCartQuantity;
                    document.getElementById('modal-product-stock').className = "badge bg-info";

                    return;
                }

                // Update stock badge
                if (currentProductStock <= 0) {
                    document.getElementById('modal-product-stock').textContent = "Out of Stock";
                    document.getElementById('modal-product-stock').className = "badge bg-danger";
                } else {
                    document.getElementById('modal-product-stock').textContent = "In Stock: " + currentProductStock;
                    document.getElementById('modal-product-stock').className = "badge stock-badge";
                }

                // Check if current quantity exceeds available stock
                if (quantity > availableStock) {
                    productQuantityInput.value = availableStock;
                    productQuantityInput.classList.add('text-danger', 'border-danger');
                    increaseQuantityBtn.classList.add('btn-danger');

                    stockWarningText.textContent = "Only " + availableStock + " more units available.";
                    stockWarning.classList.remove('d-none');
                }

                // Disable increase button if at max
                if (quantity >= availableStock) {
                    increaseQuantityBtn.disabled = true;
                    increaseQuantityBtn.classList.add('btn-danger');
                } else {
                    increaseQuantityBtn.disabled = false;
                }

                // Disable decrease button if at min
                decreaseQuantityBtn.disabled = quantity <= 1;

                updateSubtotal();
            }

            // Handle quantity changes
            productQuantityInput.addEventListener('change', function() {
                let value = parseInt(this.value);

                // Validate input
                if (isNaN(value) || value < 1) {
                    value = 1;
                } else if (value > 99) {
                    value = 99;
                }

                this.value = value;
                checkStockLimits();
            });

            // Decrease quantity button
            decreaseQuantityBtn.addEventListener('click', function() {
                let value = parseInt(productQuantityInput.value);
                if (value > 1) {
                    productQuantityInput.value = value - 1;
                    checkStockLimits();
                }
            });

            // Increase quantity button
            increaseQuantityBtn.addEventListener('click', function() {
                let value = parseInt(productQuantityInput.value);
                const availableStock = currentProductStock - currentCartQuantity;

                if (value < Math.min(99, availableStock)) {
                    productQuantityInput.value = value + 1;
                    checkStockLimits();
                }
            });

            // Show modal when add to cart button is clicked
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Get product details from data attributes
                    currentProductId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    currentProductPrice = parseFloat(this.dataset.productPrice);
                    const productImage = this.dataset.productImage;
                    const productCategory = this.dataset.productCategory || 'Product';
                    currentProductStock = parseInt(this.dataset.productStock) || 0;

                    // Check if product is already in cart
                    currentCartQuantity = 0;

                    // Get product description from the product card if available
                    let productDescription = '';
                    const productCard = this.closest('.product-card');
                    if (productCard) {
                        const descriptionElement = productCard.querySelector('.text-muted');
                        if (descriptionElement) {
                            productDescription = descriptionElement.textContent;
                        }
                    }

                    // Set modal content
                    document.getElementById('modal-product-name').textContent = productName;
                    document.getElementById('modal-product-price').textContent = currentProductPrice.toFixed(2);
                    document.getElementById('modal-product-category').textContent = productCategory;

                    // Set product description
                    const descriptionElement = document.getElementById('modal-product-description');
                    if (productDescription) {
                        descriptionElement.textContent = productDescription;
                        descriptionElement.style.display = 'block';
                    } else {
                        descriptionElement.style.display = 'none';
                    }

                    // Set product image or show placeholder
                    const imageElement = document.getElementById('modal-product-image');
                    const placeholderElement = document.querySelector('.product-image-placeholder');

                    if (productImage) {
                        imageElement.src = '../../assets/images/products/' + productImage;
                        imageElement.style.display = 'block';
                        placeholderElement.style.display = 'none';
                    } else {
                        imageElement.style.display = 'none';
                        placeholderElement.style.display = 'flex';
                    }

                    // Reset quantity to 1
                    productQuantityInput.value = 1;

                    // Check if product is in cart
                    if (typeof loadCartItems === 'function') {
                        // Fetch cart data to check current quantity
                        fetch('get_cart_items.php')
                            .then(response => response.json())
                            .then(data => {
                                if (data.items) {
                                    // Find this product in cart
                                    const cartItem = data.items.find(item => item.id === currentProductId);
                                    if (cartItem) {
                                        currentCartQuantity = cartItem.quantity;
                                    }
                                }
                                // Update UI based on stock
                                checkStockLimits();
                            })
                            .catch(error => {
                                console.error('Error fetching cart:', error);
                                // Still update UI with default values
                                checkStockLimits();
                            });
                    } else {
                        // Just update UI based on stock
                        checkStockLimits();
                    }

                    // Hide success message if it was shown
                    document.getElementById('add-to-cart-success').classList.remove('show');

                    // Show modal
                    addToCartModal.show();
                });
            });

            // Handle add to cart confirmation
            confirmAddToCartBtn.addEventListener('click', function() {
                if (currentProductId) {
                    const quantity = parseInt(productQuantityInput.value);
                    const productName = document.getElementById('modal-product-name').textContent;

                    // Show success animation
                    const successElement = document.getElementById('add-to-cart-success');
                    successElement.classList.add('show');

                    // Disable the button to prevent multiple clicks
                    this.disabled = true;

                    // Add to cart via AJAX instead of redirecting
                    fetch(`orders.php?add=${currentProductId}&quantity=${quantity}&ajax=1`)
                        .then(response => response.json())
                        .catch(error => {
                            console.error('Error:', error);
                            return { success: false, message: 'Network error occurred' };
                        })
                        .then(data => {
                            // Close modal after animation completes
                            setTimeout(() => {
                                // Hide the modal
                                addToCartModal.hide();

                                // Re-enable the button
                                this.disabled = false;

                                // Show toast notification
                                const toastContainer = document.getElementById('toast-container');
                                if (!toastContainer) {
                                    // Create toast container if it doesn't exist
                                    const container = document.createElement('div');
                                    container.id = 'toast-container';
                                    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                                    document.body.appendChild(container);
                                }

                                // Create toast element
                                const toastId = 'cart-toast-' + Date.now();
                                const toastHTML = `
                                    <div id="${toastId}" class="toast text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                        <div class="d-flex">
                                            <div class="toast-body">
                                                <i class="bi bi-check-circle me-2"></i>
                                                ${quantity} × ${productName} added to cart
                                            </div>
                                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                        </div>
                                        <div class="toast-actions px-3 pb-2">
                                            <a href="orders.php" class="btn btn-sm btn-light">
                                                <i class="bi bi-cart me-1"></i> View Cart
                                            </a>
                                        </div>
                                    </div>
                                `;

                                document.getElementById('toast-container').innerHTML += toastHTML;

                                // Initialize and show the toast
                                const toastElement = document.getElementById(toastId);
                                const toast = new bootstrap.Toast(toastElement, {
                                    delay: 5000,  // Longer delay to give time to use the buttons
                                    autohide: true
                                });
                                toast.show();



                                // Update cart count if available
                                if (typeof loadCartItems === 'function') {
                                    loadCartItems();
                                }
                            }, 1000);
                        });
                }
            });

            // Add keyboard support for quantity input
            productQuantityInput.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    let value = parseInt(this.value);
                    const availableStock = currentProductStock - currentCartQuantity;

                    if (value < Math.min(99, availableStock)) {
                        this.value = value + 1;
                        checkStockLimits();
                    }
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    let value = parseInt(this.value);
                    if (value > 1) {
                        this.value = value - 1;
                        checkStockLimits();
                    }
                }
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

                        fetch('../../templates/includes/test_email.php', {
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
                    fetch('../../templates/includes/login_handler.php', {
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
                    fetch('../../templates/includes/register_handler.php', {
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
