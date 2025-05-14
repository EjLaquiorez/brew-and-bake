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

// Fetch products with error handling
try {
    $stmt = $conn->prepare("
        SELECT * FROM products
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching products: " . $e->getMessage();
    $products = [];
}

// Get statistics
try {
    $stats = [
        'total_products' => $conn->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'total_orders' => 0, // Will be implemented when orders table is created
        'total_revenue' => 0, // Will be implemented when orders table is created
        'active_products' => $conn->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn(),
        'low_stock' => $conn->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn()
    ];
} catch (PDOException $e) {
    $stats = [
        'total_products' => 0,
        'total_orders' => 0,
        'total_revenue' => 0,
        'active_products' => 0,
        'low_stock' => 0
    ];
}

// Get recent products
$recentProducts = array_slice($products, 0, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Products - Brew & Bake</title>
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
            <div class="user-menu">
                <div class="user-avatar">
                    <?= substr($_SESSION['user']['name'] ?? 'A', 0, 1) ?>
                </div>
                <div class="user-info">
                    <h6 class="user-name"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></h6>
                    <p class="user-role">Administrator</p>
                </div>
                <i class="bi bi-chevron-down user-menu-toggle"></i>
            </div>
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

            <!-- Include Welcome Card -->
            <?php include 'includes/welcome-card.php'; ?>

            <!-- Statistics -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <h3 class="mb-4">Product Statistics</h3>
                </div>

                <div class="col-md-3 col-sm-6 col-6 mb-4">
                    <div class="stat-card primary fade-in delay-100">
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($stats['total_products']) ?></h3>
                            <p class="stat-label">Total Products</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-6 mb-4">
                    <div class="stat-card success fade-in delay-200">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($stats['active_products']) ?></h3>
                            <p class="stat-label">Active Products</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-6 mb-4">
                    <div class="stat-card warning fade-in delay-300">
                        <div class="stat-icon">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($stats['low_stock']) ?></h3>
                            <p class="stat-label">Low Stock Items</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 col-6 mb-4">
                    <div class="stat-card info fade-in delay-400">
                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value">₱<?= number_format($stats['total_revenue'], 2) ?></h3>
                            <p class="stat-label">Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Management -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                        <div>
                            <h3 class="mb-1">Product Management</h3>
                            <p class="text-muted mb-0">View, edit, and manage your product inventory</p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <div class="d-flex flex-grow-1">
                                <input type="text" class="form-control" placeholder="Search products..." id="productSearch">
                            </div>
                            <a href="add_product.php" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-md-2"></i>
                                <span class="d-none d-md-inline">Add Product</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="table-container fade-in-up">
                        <div class="table-header">
                            <h5 class="table-title"><i class="bi bi-box-seam"></i> All Products</h5>
                            <div class="table-actions">
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </div>
                        </div>

                        <?php if (count($products) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="cell-with-image">
                                                        <?php if (!empty($product['image'])): ?>
                                                            <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                                                class="cell-image"
                                                                alt="<?= htmlspecialchars($product['name']) ?>">
                                                        <?php else: ?>
                                                            <div class="cell-icon">
                                                                <i class="bi bi-image"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="cell-image-content">
                                                            <h6 class="cell-title"><?= htmlspecialchars($product['name']) ?></h6>
                                                            <p class="cell-subtitle"><?= htmlspecialchars(substr($product['description'], 0, 60)) . (strlen($product['description']) > 60 ? '...' : '') ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="cell-badge primary">
                                                        <?= htmlspecialchars($product['category']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">₱<?= number_format($product['price'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <span class="cell-badge <?= $product['status'] === 'active' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($product['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="cell-actions">
                                                        <a href="view_product.php?id=<?= $product['id'] ?>" class="action-button view">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="action-button edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="action-button delete" onclick="confirmDelete(<?= $product['id'] ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-footer">
                                <div class="pagination-info">
                                    Showing <span class="fw-bold"><?= count($products) ?></span> of <span class="fw-bold"><?= count($products) ?></span> products
                                </div>
                                <div class="pagination-controls">
                                    <button class="pagination-button disabled">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <button class="pagination-button active">1</button>
                                    <button class="pagination-button disabled">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="p-5 text-center">
                                <div class="mb-4">
                                    <i class="bi bi-box-seam" style="font-size: 3rem; color: var(--color-gray-400);"></i>
                                </div>
                                <h4>No Products Found</h4>
                                <p class="text-muted mb-4">You haven't added any products yet. Start by adding your first product!</p>
                                <a href="add_product.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i> Add New Product
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Confirm Delete
                </h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div class="alert-content">
                            <p class="alert-text mb-0">Deleting this product will remove it from your inventory and any associated orders.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="bi bi-trash me-2"></i> Delete Product
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer-scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete confirmation
        window.confirmDelete = function(productId) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('confirmDeleteBtn').href = `delete_product.php?id=${productId}`;
            modal.show();
        };

        // Product search functionality
        const productSearch = document.getElementById('productSearch');
        if (productSearch) {
            productSearch.addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase();
                const tableRows = document.querySelectorAll('tbody tr');

                tableRows.forEach(row => {
                    const productName = row.querySelector('.cell-title').textContent.toLowerCase();
                    const productDesc = row.querySelector('.cell-subtitle').textContent.toLowerCase();
                    const productCategory = row.querySelector('.cell-badge').textContent.toLowerCase();

                    if (productName.includes(searchValue) ||
                        productDesc.includes(searchValue) ||
                        productCategory.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update pagination info
                const visibleRows = document.querySelectorAll('tbody tr:not([style*="display: none"])');
                const paginationInfo = document.querySelector('.pagination-info');
                if (paginationInfo) {
                    paginationInfo.innerHTML = `Showing <span class="fw-bold">${visibleRows.length}</span> of <span class="fw-bold">${tableRows.length}</span> products`;
                }
            });
        }
    });
</script>
</body>
</html>