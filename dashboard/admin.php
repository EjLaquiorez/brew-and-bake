<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: ../views/login.php");
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
    <title>Admin Dashboard - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-cup-hot"></i> Brew & Bake Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
                        <a class="nav-link" href="orders.php">
            <i class="bi bi-receipt"></i> Orders
          </a>
        </li>
        <li class="nav-item">
                        <a class="nav-link active" href="admin.php">
            <i class="bi bi-box-seam"></i> Products
          </a>
        </li>
        <li class="nav-item">
                        <a class="nav-link" href="reports.php">
            <i class="bi bi-bar-chart-line"></i> Reports
          </a>
        </li>
        <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-gear"></i> Settings
          </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="bi bi-sliders"></i> System Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Main Content -->
    <div class="container-fluid py-4">
    <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">Welcome back, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?>!</h4>
                                <p class="mb-0">Here's what's happening with your store today.</p>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-1" id="currentTime"></h5>
                                <p class="mb-0"><?= date('l, F j, Y') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Products</h6>
                                <h2 class="card-text mb-0"><?= number_format($stats['total_products']) ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Active Products</h6>
                                <h2 class="card-text mb-0"><?= number_format($stats['active_products']) ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class="bi bi-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Low Stock Items</h6>
                                <h2 class="card-text mb-0"><?= number_format($stats['low_stock']) ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1">Total Revenue</h6>
                                <h2 class="card-text mb-0">₱<?= number_format($stats['total_revenue'], 2) ?></h2>
                            </div>
                            <div class="fs-1">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions and Recent Products -->
        <div class="row mb-4">
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="add_product.php" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Add New Product
                            </a>
                            <a href="orders.php" class="btn btn-primary">
                                <i class="bi bi-receipt"></i> View Orders
                            </a>
                            <a href="reports.php" class="btn btn-info text-white">
                                <i class="bi bi-bar-chart"></i> Generate Reports
                            </a>
                            <a href="settings.php" class="btn btn-secondary">
                                <i class="bi bi-gear"></i> System Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Products -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Recent Products</h5>
                            <a href="products.php" class="btn btn-sm btn-light">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($recentProducts) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentProducts as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($product['image'])): ?>
                                                            <img src="../assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                                                 class="rounded-circle me-2" 
                                                                 width="40" 
                                                                 height="40" 
                                                                 alt="<?= htmlspecialchars($product['name']) ?>">
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                                            <small class="text-muted">Added <?= date('M d, Y', strtotime($product['created_at'])) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($product['category']) ?></td>
                                                <td>₱<?= number_format($product['price'], 2) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst($product['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle"></i> No products found. Start by adding your first product!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Section -->
        <div class="page-header">
            <h3><i class="bi bi-box-seam"></i> Manage Products</h3>
        <a href="add_product.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add New Product
        </a>
    </div>

    <?php if (count($products) > 0): ?>
        <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Name</th>
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
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="../assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                             class="rounded-circle me-2" 
                                             width="40" 
                                             height="40" 
                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($product['description']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($product['category']) ?></td>
                            <td>₱<?= number_format($product['price'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($product['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="edit_product.php?id=<?= $product['id'] ?>" 
                                       class="btn btn-warning btn-sm" 
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-danger btn-sm" 
                                            onclick="confirmDelete(<?= $product['id'] ?>)"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No products found. Start by adding your first product!
        </div>
    <?php endif; ?>
</div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this product? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete confirmation
        function confirmDelete(productId) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('confirmDeleteBtn').href = `delete_product.php?id=${productId}`;
            modal.show();
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        function updateTime() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit', hour12: true };
            document.getElementById('currentTime').textContent = now.toLocaleTimeString([], options);
        }

        // Update time immediately and set interval to update every minute
        updateTime();
        setInterval(updateTime, 60000); // Update every 60 seconds
    </script>
</body>
</html>
