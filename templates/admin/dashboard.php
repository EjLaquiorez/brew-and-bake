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

// Fetch statistics
try {
    // Products statistics
    $totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $activeProducts = $conn->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $lowStockProducts = $conn->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn();

    // Orders statistics (placeholder - will be implemented when orders table exists)
    $totalOrders = 0;
    $pendingOrders = 0;
    $completedOrders = 0;
    $totalRevenue = 0;

    // Recent products
    $stmt = $conn->prepare("
        SELECT * FROM products
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top selling products (placeholder - will be implemented when orders table exists)
    $topProducts = [];

} catch (PDOException $e) {
    $errorMessage = "Error fetching data: " . $e->getMessage();
    $totalProducts = 0;
    $activeProducts = 0;
    $lowStockProducts = 0;
    $totalOrders = 0;
    $pendingOrders = 0;
    $completedOrders = 0;
    $totalRevenue = 0;
    $recentProducts = [];
    $topProducts = [];
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
    <title>Dashboard - Brew & Bake</title>
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
                        <a href="dashboard.php" class="nav-link active">
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
                        <a href="products.php" class="nav-link">
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

            <!-- Statistics Overview -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <h3 class="mb-4">Store Overview</h3>
                </div>

                <!-- Products Stats -->
                <div class="col-md-3 col-sm-6 col-6 mb-4">
                    <div class="stat-card primary fade-in delay-100">
                        <div class="stat-icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($totalProducts) ?></h3>
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
                            <h3 class="stat-value"><?= number_format($activeProducts) ?></h3>
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
                            <h3 class="stat-value"><?= number_format($lowStockProducts) ?></h3>
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
                            <h3 class="stat-value">₱<?= number_format($totalRevenue, 2) ?></h3>
                            <p class="stat-label">Total Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- Orders Stats -->
                <div class="col-md-4 col-sm-6 col-6 mb-4">
                    <div class="stat-card secondary fade-in delay-500">
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($totalOrders) ?></h3>
                            <p class="stat-label">Total Orders</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6 col-6 mb-4">
                    <div class="stat-card warning fade-in delay-600">
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($pendingOrders) ?></h3>
                            <p class="stat-label">Pending Orders</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6 col-6 mb-4">
                    <div class="stat-card success fade-in delay-700">
                        <div class="stat-icon">
                            <i class="bi bi-check2-all"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($completedOrders) ?></h3>
                            <p class="stat-label">Completed Orders</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row mb-5">
                <!-- Recent Products -->
                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-left">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-box-seam"></i> Recent Products</h5>
                            <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($recentProducts) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentProducts as $product): ?>
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
                                                                <p class="cell-subtitle"><?= htmlspecialchars($product['category']) ?></p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold">₱<?= number_format($product['price'], 2) ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="cell-badge <?= $product['status'] === 'active' ? 'success' : 'warning' ?>">
                                                            <?= ucfirst($product['status']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-4 text-center">
                                    <p class="text-muted mb-0">No products found.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-right">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-lightning"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="add_product.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i> Add New Product
                                </a>
                                <a href="orders.php" class="btn btn-secondary">
                                    <i class="bi bi-receipt me-2"></i> View Orders
                                </a>
                                <a href="analytics.php" class="btn btn-info">
                                    <i class="bi bi-bar-chart me-2"></i> View Analytics
                                </a>
                                <a href="settings.php" class="btn btn-light">
                                    <i class="bi bi-gear me-2"></i> System Settings
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="card mt-4 fade-in-right delay-300">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-info-circle"></i> System Status</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-hdd text-success me-2"></i>
                                        Database Connection
                                    </div>
                                    <span class="cell-badge success">Connected</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-globe text-success me-2"></i>
                                        Website Status
                                    </div>
                                    <span class="cell-badge success">Online</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-shield-check text-success me-2"></i>
                                        Security Status
                                    </div>
                                    <span class="cell-badge success">Secure</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer-scripts.php'; ?>
</body>
</html>