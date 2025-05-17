<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../views/login.php");
    exit;
}

require_once "../includes/db.php";

// Initialize variables
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Handle batch operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['batch_action']) && isset($_POST['selected_products'])) {
        $action = $_POST['batch_action'];
        $selectedProducts = $_POST['selected_products'];

        if (!empty($selectedProducts)) {
            try {
                switch ($action) {
                    case 'delete':
                        $placeholders = implode(',', array_fill(0, count($selectedProducts), '?'));
                        $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        $successMessage = count($selectedProducts) . " product(s) deleted successfully.";
                        break;
                    case 'activate':
                        $placeholders = implode(',', array_fill(0, count($selectedProducts), '?'));
                        $stmt = $conn->prepare("UPDATE products SET status = 'active' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        $successMessage = count($selectedProducts) . " product(s) activated successfully.";
                        break;
                    case 'deactivate':
                        $placeholders = implode(',', array_fill(0, count($selectedProducts), '?'));
                        $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id IN ($placeholders)");
                        $stmt->execute($selectedProducts);
                        $successMessage = count($selectedProducts) . " product(s) deactivated successfully.";
                        break;
                }
            } catch (PDOException $e) {
                $errorMessage = "Error performing batch operation: " . $e->getMessage();
            }
        } else {
            $errorMessage = "No products selected for batch operation.";
        }
    }
}

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

// Fetch categories for filter
try {
    $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    $errorMessage = "Error fetching categories: " . $e->getMessage();
}

// Fetch products with category information
try {
    // Build the query
    $query = "
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($category)) {
        $query .= " AND p.category_id = ?";
        $params[] = $category;
    }

    if (!empty($status)) {
        $query .= " AND p.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Add sorting
    switch ($sort) {
        case 'price_asc':
            $query .= " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $query .= " ORDER BY p.price DESC";
            break;
        case 'stock_asc':
            $query .= " ORDER BY p.stock ASC";
            break;
        case 'stock_desc':
            $query .= " ORDER BY p.stock DESC";
            break;
        case 'name_desc':
            $query .= " ORDER BY p.name DESC";
            break;
        default: // name_asc
            $query .= " ORDER BY p.name ASC";
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group products by category for tabs
    $productsByCategory = [];
    foreach ($products as $product) {
        $categoryName = $product['category_name'] ?? 'Uncategorized';
        if (!isset($productsByCategory[$categoryName])) {
            $productsByCategory[$categoryName] = [];
        }
        $productsByCategory[$categoryName][] = $product;
    }

    // Sort categories alphabetically
    ksort($productsByCategory);

} catch (PDOException $e) {
    $products = [];
    $productsByCategory = [];
    $errorMessage = "Error fetching products: " . $e->getMessage();
}

// Function to get category image
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
    } elseif ($categoryName == 'beverage' || $categoryName == 'beverages' || $categoryName == 'non-coffee drinks') {
        return "beverage.png";
    } elseif ($categoryName == 'sandwich' || $categoryName == 'sandwiches') {
        return "sandwich.png";
    } elseif ($categoryName == 'other baked goods') {
        return "baked-goods.png";
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
    <title>Product Management - Brew & Bake Admin</title>
    <?php include 'includes/css-includes.php'; ?>
    <style>
        /* Additional page-specific styles */
        .product-image-container {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }

        .product-image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-card {
            transition: all 0.3s ease;
            height: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            margin-bottom: 1.5rem;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .product-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-card:hover .product-actions {
            opacity: 1;
        }

        .product-status {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }

        .category-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            border-radius: 0;
            border-bottom: 3px solid transparent;
        }

        .category-tabs .nav-link.active {
            color: #111827;
            border-bottom: 3px solid #f59e0b;
            background-color: transparent;
        }

        .category-tabs .nav-link:hover:not(.active) {
            border-bottom: 3px solid #e5e7eb;
        }

        .stock-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            z-index: 10;
        }

        .product-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 20;
        }

        .batch-toolbar {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .product-actions {
                opacity: 1;
            }

            .admin-content {
                padding: 1rem;
            }

            .category-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 768px) {
            .admin-content {
                padding: 0.75rem;
            }

            .product-image-container {
                height: 140px;
            }

            .product-card .card-body {
                padding: 0.875rem;
            }

            .card-header, .card-body {
                padding: 0.75rem !important;
            }

            .category-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
                margin-bottom: 1rem;
                padding-bottom: 5px;
            }

            .category-tabs::-webkit-scrollbar {
                height: 3px;
            }

            .category-tabs::-webkit-scrollbar-thumb {
                background-color: rgba(0,0,0,0.2);
                border-radius: 3px;
            }

            .category-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }

            .batch-toolbar {
                padding: 0.75rem;
            }

            .batch-toolbar .d-flex {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .page-header > div:last-child {
                width: 100%;
            }

            .page-header > div:last-child .btn {
                width: 100%;
            }
        }

        /* Specific styles for screens 742px and smaller */
        @media (max-width: 742px) {
            .admin-content {
                padding: 0.5rem;
            }

            .card {
                margin-bottom: 0.75rem;
            }

            .card-header, .card-body {
                padding: 0.625rem !important;
            }

            /* Adjust search and filter form */
            .col-md-4, .col-md-3, .col-md-2, .col-md-1 {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            /* Make product grid 2 columns on small screens */
            .col-sm-6 {
                width: 50%;
                padding-left: 0.25rem;
                padding-right: 0.25rem;
            }

            .product-card {
                margin-bottom: 0.75rem;
            }

            .product-image-container {
                height: 120px;
            }

            .product-card .card-body {
                padding: 0.625rem !important;
            }

            .product-card .card-title {
                font-size: 0.9rem;
                margin-bottom: 0.25rem;
            }

            .product-card .card-text {
                font-size: 0.75rem;
                margin-bottom: 0.5rem;
                max-height: 2.4em;
                overflow: hidden;
            }

            /* Stack batch operations toolbar vertically */
            .batch-toolbar .row {
                flex-direction: column;
            }

            .batch-toolbar .col-md-6 {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .batch-toolbar .d-flex {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .batch-toolbar .text-md-end {
                text-align: left !important;
            }

            /* Adjust form elements for better mobile viewing */
            .form-select, .form-control, .btn {
                font-size: 0.875rem;
                padding: 0.375rem 0.5rem;
            }

            /* Very small screens (under 576px) */
            @media (max-width: 576px) {
                .col-sm-6 {
                    width: 100%; /* 1 column layout for very small screens */
                }

                .product-image-container {
                    height: 140px; /* Slightly larger images for 1 column layout */
                }

                /* Hide product description on very small screens */
                .product-card .card-text {
                    display: none;
                }

                /* Make batch action select full width */
                .batch-toolbar select.form-select {
                    width: 100% !important;
                    margin-right: 0 !important;
                    margin-bottom: 0.5rem;
                }

                .batch-toolbar .form-check {
                    width: 100%;
                    margin-bottom: 0.5rem;
                }

                .batch-toolbar button {
                    width: 100%;
                }
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Include Topbar -->
            <?php include 'includes/topbar.php'; ?>

            <!-- Content Area -->
            <div class="admin-content">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="page-title">Product Management</h1>
                        <p class="text-muted">Manage your products, categories, and inventory</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="manage_product_images_list.php" class="btn btn-outline-primary">
                            <i class="bi bi-images me-2"></i>Manage Images
                        </a>
                        <a href="add_product.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Add New Product
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="alert-icon">
                            <div class="alert-icon-symbol">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <div class="alert-content">
                                <h6 class="alert-title">Success</h6>
                                <p class="alert-text"><?= htmlspecialchars($successMessage) ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="alert-icon">
                            <div class="alert-icon-symbol">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="alert-content">
                                <h6 class="alert-title">Error</h6>
                                <p class="alert-text"><?= htmlspecialchars($errorMessage) ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Search and Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <!-- Search Input -->
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>

                            <!-- Category Filter -->
                            <div class="col-lg-3 col-md-6 col-12">
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(ucfirst($cat['name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="col-lg-2 col-md-4 col-6">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>

                            <!-- Sort Order -->
                            <div class="col-lg-2 col-md-4 col-6">
                                <select class="form-select" name="sort">
                                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price (Low-High)</option>
                                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price (High-Low)</option>
                                    <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>Stock (Low-High)</option>
                                    <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Stock (High-Low)</option>
                                </select>
                            </div>

                            <!-- Submit Button -->
                            <div class="col-lg-1 col-md-4 col-12">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Batch Operations Toolbar -->
                <form action="" method="POST" id="batch-form">
                    <div class="batch-toolbar mb-4">
                        <div class="row align-items-center">
                            <div class="col-lg-6 col-md-8 col-12 mb-2 mb-lg-0">
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="form-check me-3 mb-2 mb-md-0">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                        <label class="form-check-label" for="select-all">Select All</label>
                                    </div>
                                    <div class="d-flex flex-grow-1 flex-wrap">
                                        <select class="form-select me-2 mb-2 mb-md-0" name="batch_action" style="width: auto; min-width: 150px;">
                                            <option value="">Batch Actions</option>
                                            <option value="activate">Activate</option>
                                            <option value="deactivate">Deactivate</option>
                                            <option value="delete">Delete</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-secondary" id="apply-batch">Apply</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-4 col-12 text-lg-end text-md-end text-start">
                                <span class="text-muted"><?= count($products) ?> products found</span>
                            </div>
                        </div>
                    </div>

                    <!-- Category Tabs -->
                    <ul class="nav nav-tabs category-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#all-products">All Products</a>
                        </li>
                        <?php foreach ($productsByCategory as $catName => $catProducts): ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#category-<?= md5($catName) ?>">
                                    <?= htmlspecialchars(ucfirst($catName)) ?>
                                    <span class="badge bg-secondary"><?= count($catProducts) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- All Products Tab -->
                        <div class="tab-pane fade show active" id="all-products">
                            <div class="row">
                                <?php if (empty($products)): ?>
                                    <div class="col-12 text-center py-5">
                                        <i class="bi bi-box" style="font-size: 3rem; color: #d1d5db;"></i>
                                        <h4 class="mt-3">No products found</h4>
                                        <p class="text-muted">Try adjusting your search or filter criteria</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                            <div class="product-card">
                                                <!-- Product Checkbox -->
                                                <div class="product-checkbox">
                                                    <div class="form-check">
                                                        <input class="form-check-input product-select" type="checkbox" name="selected_products[]" value="<?= $product['id'] ?>">
                                                    </div>
                                                </div>

                                                <!-- Product Status Badge -->
                                                <?php if ($product['status'] === 'inactive'): ?>
                                                    <div class="product-status">
                                                        <span class="badge bg-danger">Inactive</span>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Product Image -->
                                                <div class="product-image-container">
                                                    <?php if (!empty($product['image'])): ?>
                                                        <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                                    <?php else: ?>
                                                        <?php
                                                        $categoryImage = getCategoryImage($product['category_name']);
                                                        if (!empty($categoryImage)):
                                                        ?>
                                                            <img src="../../assets/images/categories/<?= $categoryImage ?>"
                                                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                                                 style="opacity: 0.7;">
                                                        <?php else: ?>
                                                            <div class="text-center text-muted">
                                                                <i class="bi bi-image" style="font-size: 3rem;"></i>
                                                                <p>No image</p>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Product Info -->
                                                <div class="card-body">
                                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                                    <p class="card-text text-muted small">
                                                        <?= htmlspecialchars(substr($product['description'] ?? '', 0, 60)) . (strlen($product['description'] ?? '') > 60 ? '...' : '') ?>
                                                    </p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-bold text-primary">₱<?= number_format($product['price'], 2) ?></span>
                                                        <span class="badge bg-<?= $product['stock'] < 10 ? 'warning' : 'info' ?>">
                                                            Stock: <?= $product['stock'] ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Product Actions -->
                                                <div class="product-actions">
                                                    <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="manage_product_images.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-image"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Category Tabs -->
                        <?php foreach ($productsByCategory as $catName => $catProducts): ?>
                            <div class="tab-pane fade" id="category-<?= md5($catName) ?>">
                                <div class="row">
                                    <?php if (empty($catProducts)): ?>
                                        <div class="col-12 text-center py-5">
                                            <i class="bi bi-box" style="font-size: 3rem; color: #d1d5db;"></i>
                                            <h4 class="mt-3">No products found in this category</h4>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($catProducts as $product): ?>
                                            <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                <div class="product-card">
                                                    <!-- Product Checkbox -->
                                                    <div class="product-checkbox">
                                                        <div class="form-check">
                                                            <input class="form-check-input product-select" type="checkbox" name="selected_products[]" value="<?= $product['id'] ?>">
                                                        </div>
                                                    </div>

                                                    <!-- Product Status Badge -->
                                                    <?php if ($product['status'] === 'inactive'): ?>
                                                        <div class="product-status">
                                                            <span class="badge bg-danger">Inactive</span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Product Image -->
                                                    <div class="product-image-container">
                                                        <?php if (!empty($product['image'])): ?>
                                                            <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                                                 alt="<?= htmlspecialchars($product['name']) ?>">
                                                        <?php else: ?>
                                                            <?php
                                                            $categoryImage = getCategoryImage($product['category_name']);
                                                            if (!empty($categoryImage)):
                                                            ?>
                                                                <img src="../../assets/images/categories/<?= $categoryImage ?>"
                                                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                                                     style="opacity: 0.7;">
                                                            <?php else: ?>
                                                                <div class="text-center text-muted">
                                                                    <i class="bi bi-image" style="font-size: 3rem;"></i>
                                                                    <p>No image</p>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Product Info -->
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                                        <p class="card-text text-muted small">
                                                            <?= htmlspecialchars(substr($product['description'] ?? '', 0, 60)) . (strlen($product['description'] ?? '') > 60 ? '...' : '') ?>
                                                        </p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="fw-bold text-primary">₱<?= number_format($product['price'], 2) ?></span>
                                                            <span class="badge bg-<?= $product['stock'] < 10 ? 'warning' : 'info' ?>">
                                                                Stock: <?= $product['stock'] ?>
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Product Actions -->
                                                    <div class="product-actions">
                                                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="manage_product_images.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-info">
                                                            <i class="bi bi-image"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Include Footer Scripts -->
    <?php include 'includes/footer-scripts.php'; ?>

    <!-- Page-specific JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select All Checkbox
            const selectAllCheckbox = document.getElementById('select-all');
            const productCheckboxes = document.querySelectorAll('.product-select');

            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const isChecked = this.checked;
                    productCheckboxes.forEach(checkbox => {
                        checkbox.checked = isChecked;
                    });
                });
            }

            // Batch Actions Form Validation
            const batchForm = document.getElementById('batch-form');
            const batchActionSelect = document.querySelector('select[name="batch_action"]');
            const applyBatchBtn = document.getElementById('apply-batch');

            if (batchForm && applyBatchBtn) {
                batchForm.addEventListener('submit', function(e) {
                    // Check if an action is selected
                    if (!batchActionSelect.value) {
                        e.preventDefault();
                        alert('Please select a batch action.');
                        return false;
                    }

                    // Check if any products are selected
                    const selectedProducts = document.querySelectorAll('.product-select:checked');
                    if (selectedProducts.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one product.');
                        return false;
                    }

                    // Confirm delete action
                    if (batchActionSelect.value === 'delete' && !confirm('Are you sure you want to delete the selected products?')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }

            // Update select all checkbox state when individual checkboxes change
            productCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = document.querySelectorAll('.product-select:checked').length === productCheckboxes.length;
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allChecked;
                        selectAllCheckbox.indeterminate = !allChecked && document.querySelectorAll('.product-select:checked').length > 0;
                    }
                });
            });
        });
    </script>
</body>
</html>