<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
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

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Product ID is required.";
    header("Location: products.php");
    exit;
}

$productId = $_GET['id'];

// Fetch product details with error handling
try {
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: products.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching product: " . $e->getMessage();
    header("Location: products.php");
    exit;
}

// Format dates for display
$createdDate = !empty($product['created_at']) ? date('F j, Y, g:i a', strtotime($product['created_at'])) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>View Product - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
<!-- Admin Container -->
<div class="admin-container">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <div class="sidebar-logo">
                    <i class="bi bi-cup-hot"></i>
                </div>
                <div>
                    <h3 class="sidebar-title">Brew & Bake</h3>
                    <p class="sidebar-subtitle">Admin Dashboard</p>
                </div>
            </a>
            <button class="sidebar-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section">
                <h6 class="nav-section-title">Main</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="bi bi-speedometer2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link">
                            <i class="bi bi-receipt"></i>
                            Orders
                            <span class="nav-badge">5</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products.php" class="nav-link active">
                            <i class="bi bi-box-seam"></i>
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link">
                            <i class="bi bi-tags"></i>
                            Categories
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h6 class="nav-section-title">Analytics</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="analytics.php" class="nav-link">
                            <i class="bi bi-bar-chart-line"></i>
                            Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="sales.php" class="nav-link">
                            <i class="bi bi-graph-up"></i>
                            Sales
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h6 class="nav-section-title">Settings</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <i class="bi bi-person"></i>
                            Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="bi bi-gear"></i>
                            System Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <?php include 'includes/sidebar-user-menu.php'; ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Include Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Content Area -->
        <div class="admin-content">
            <?php if ($successMessage): ?>
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

            <?php if ($errorMessage): ?>
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

            <!-- Product Details -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                        <div>
                            <h3 class="mb-1">Product Details</h3>
                            <p class="text-muted mb-0">View detailed information about this product</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i>
                                Back to Products
                            </a>
                            <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-primary">
                                <i class="bi bi-pencil me-2"></i>
                                Edit Product
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body p-0">
                            <div class="product-image-container">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                         class="product-detail-image"
                                         alt="<?= htmlspecialchars($product['name']) ?>">
                                <?php else: ?>
                                    <div class="product-no-image">
                                        <i class="bi bi-image"></i>
                                        <p>No Image Available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card h-100">
                        <div class="card-body">
                            <h4 class="product-title"><?= htmlspecialchars($product['name']) ?></h4>
                            
                            <div class="product-meta mb-4">
                                <span class="product-id">ID: <?= htmlspecialchars($product['id']) ?></span>
                                <span class="product-category">
                                    <i class="bi bi-tag"></i>
                                    <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                                </span>
                                <span class="product-status <?= $product['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <i class="bi bi-circle-fill"></i>
                                    <?= ucfirst($product['status']) ?>
                                </span>
                            </div>
                            
                            <div class="product-price-stock mb-4">
                                <div class="product-price">
                                    <h5>Price</h5>
                                    <p class="price-value">₱<?= number_format($product['price'], 2) ?></p>
                                </div>
                                <div class="product-stock">
                                    <h5>Stock</h5>
                                    <p class="stock-value <?= $product['stock'] < 10 ? 'text-warning' : 'text-success' ?>">
                                        <?= htmlspecialchars($product['stock']) ?> units
                                        <?php if ($product['stock'] < 10): ?>
                                            <span class="stock-warning"><i class="bi bi-exclamation-triangle"></i> Low Stock</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="product-description mb-4">
                                <h5>Description</h5>
                                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                            </div>
                            
                            <div class="product-metadata">
                                <h5>Additional Information</h5>
                                <div class="metadata-item">
                                    <span class="metadata-label">Created At:</span>
                                    <span class="metadata-value"><?= $createdDate ?></span>
                                </div>
                                <div class="metadata-item">
                                    <span class="metadata-label">Category ID:</span>
                                    <span class="metadata-value"><?= htmlspecialchars($product['category_id'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer-scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Any specific scripts for product details page
    });
</script>
</body>
</html>
