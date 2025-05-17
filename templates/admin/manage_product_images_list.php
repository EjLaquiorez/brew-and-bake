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

// Get category filter
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

// Get search query
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch all categories
try {
    $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching categories: " . $e->getMessage();
}

// Fetch products with category information
try {
    $query = "
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE 1=1
    ";

    $params = [];

    // Add category filter if selected
    if (!empty($categoryFilter)) {
        $query .= " AND p.category_id = ?";
        $params[] = $categoryFilter;
    }

    // Add search filter if provided
    if (!empty($searchQuery)) {
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$searchQuery%";
        $params[] = "%$searchQuery%";
    }

    $query .= " ORDER BY p.name ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching products: " . $e->getMessage();
}

// Count products with and without images
$productsWithImages = 0;
$productsWithoutImages = 0;

foreach ($products as $product) {
    if (!empty($product['image'])) {
        $productsWithImages++;
    } else {
        $productsWithoutImages++;
    }
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
    <title>Manage Product Images - Brew & Bake Admin</title>
    <?php include 'includes/css-includes.php'; ?>
    <style>
        .product-card {
            transition: all 0.3s ease;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .product-image-container {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .product-image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-image-container .placeholder {
            opacity: 0.5;
            max-height: 120px;
        }

        .product-image-container .no-image-icon {
            font-size: 3rem;
            color: #6c757d;
        }

        .product-card .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #111827;
        }

        .product-card .card-subtitle {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .product-card .card-text {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .product-card .card-footer {
            background-color: transparent;
            border-top: none;
            padding-top: 0;
        }

        .stats-card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .product-image-container {
                height: 150px;
            }

            .product-card .card-title {
                font-size: 0.95rem;
            }

            .product-card .card-subtitle,
            .product-card .card-text {
                font-size: 0.8rem;
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
                        <h1 class="page-title">Manage Product Images</h1>
                        <p class="text-muted">Update and optimize product images</p>
                    </div>
                    <div>
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Products
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

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                                        <i class="bi bi-grid-3x3"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Total Products</h6>
                                        <h3 class="card-title mb-0"><?= count($products) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                                        <i class="bi bi-image"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Products with Images</h6>
                                        <h3 class="card-title mb-0"><?= $productsWithImages ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                                        <i class="bi bi-image-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Products without Images</h6>
                                        <h3 class="card-title mb-0"><?= $productsWithoutImages ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-section">
                    <form action="" method="get" class="row g-3">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" placeholder="Search products..." name="search" value="<?= htmlspecialchars($searchQuery) ?>">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $categoryFilter == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Products Grid -->
                <div class="row">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                <div class="card product-card">
                                    <div class="card-body">
                                        <div class="product-image-container">
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                                     alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid">
                                            <?php elseif (!empty($product['category_name'])): ?>
                                                <img src="../../assets/images/categories/<?= getCategoryImage($product['category_name']) ?>"
                                                     alt="<?= htmlspecialchars($product['category_name']) ?>"
                                                     class="img-fluid placeholder">
                                            <?php else: ?>
                                                <i class="bi bi-image no-image-icon"></i>
                                            <?php endif; ?>
                                        </div>

                                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                        <h6 class="card-subtitle">
                                            <span class="badge bg-light text-dark">
                                                <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                                            </span>
                                        </h6>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <?php if (!empty($product['image'])): ?>
                                                    <i class="bi bi-check-circle-fill text-success me-1"></i> Image: <?= htmlspecialchars($product['image']) ?>
                                                <?php else: ?>
                                                    <i class="bi bi-exclamation-circle-fill text-warning me-1"></i> No image uploaded
                                                <?php endif; ?>
                                            </small>
                                        </p>
                                    </div>
                                    <div class="card-footer">
                                        <a href="manage_product_images.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm w-100">
                                            <?php if (!empty($product['image'])): ?>
                                                <i class="bi bi-pencil-square me-1"></i>Update Image
                                            <?php else: ?>
                                                <i class="bi bi-upload me-1"></i>Add Image
                                            <?php endif; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No Products Found</h5>
                                <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                <a href="?<?= !empty($categoryFilter) ? '' : 'category=' . urlencode($categoryFilter) ?>" class="btn btn-outline-primary mt-3">
                                    <i class="bi bi-arrow-repeat me-1"></i>Reset Filters
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer-scripts.php'; ?>
</body>
</html>