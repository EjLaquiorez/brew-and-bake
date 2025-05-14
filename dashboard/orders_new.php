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

// Handle messages
$successMessage = $_SESSION['success'] ?? '';
$errorMessage = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Handle filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Fetch orders
$where = [];
$params = [];
if ($search) {
    $where[] = "customer_name LIKE ?";
    $params[] = "%$search%";
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}
$whereSQL = $where ? ("WHERE " . implode(" AND ", $where)) : "";

try {
    // Check if orders table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        $stmt = $conn->prepare("SELECT * FROM orders $whereSQL ORDER BY created_at DESC");
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Placeholder data for demonstration
        $orders = [
            [
                'id' => 1001,
                'customer_name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'total' => 850.00,
                'status' => 'completed',
                'created_at' => '2023-06-15 14:30:45',
                'items' => 3
            ],
            [
                'id' => 1002,
                'customer_name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'total' => 1250.75,
                'status' => 'processing',
                'created_at' => '2023-06-16 09:15:22',
                'items' => 5
            ],
            [
                'id' => 1003,
                'customer_name' => 'Robert Johnson',
                'email' => 'robert.j@example.com',
                'total' => 450.50,
                'status' => 'pending',
                'created_at' => '2023-06-16 16:45:10',
                'items' => 2
            ],
            [
                'id' => 1004,
                'customer_name' => 'Emily Wilson',
                'email' => 'emily.w@example.com',
                'total' => 975.25,
                'status' => 'completed',
                'created_at' => '2023-06-17 11:20:35',
                'items' => 4
            ],
            [
                'id' => 1005,
                'customer_name' => 'Michael Brown',
                'email' => 'michael.b@example.com',
                'total' => 325.00,
                'status' => 'cancelled',
                'created_at' => '2023-06-17 13:55:18',
                'items' => 1
            ]
        ];
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}

// Order statistics
$totalOrders = count($orders);
$pendingOrders = count(array_filter($orders, function($order) { return $order['status'] === 'pending'; }));
$processingOrders = count(array_filter($orders, function($order) { return $order['status'] === 'processing'; }));
$completedOrders = count(array_filter($orders, function($order) { return $order['status'] === 'completed'; }));
$cancelledOrders = count(array_filter($orders, function($order) { return $order['status'] === 'cancelled'; }));

// Calculate total revenue
$totalRevenue = array_sum(array_column($orders, 'total'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Orders - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
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
                        <a href="orders.php" class="nav-link active">
                            <i class="bi bi-receipt"></i>
                            Orders
                            <span class="nav-badge"><?= $totalOrders ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="admin.php" class="nav-link">
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
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="menu-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="page-title">Orders</h1>
            </div>
            <div class="topbar-right">
                <div class="topbar-icon">
                    <i class="bi bi-bell"></i>
                    <span class="topbar-badge">3</span>
                </div>
                <div class="topbar-icon">
                    <i class="bi bi-envelope"></i>
                    <span class="topbar-badge">5</span>
                </div>
                <div class="topbar-profile">
                    <div class="topbar-avatar">
                        <?= substr($_SESSION['user']['name'] ?? 'A', 0, 1) ?>
                    </div>
                    <span class="topbar-user d-none d-md-block"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></span>
                    <i class="bi bi-chevron-down topbar-dropdown"></i>
                </div>
            </div>
        </header>

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

            <!-- Order Statistics -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <h3 class="mb-4">Order Statistics</h3>
                </div>

                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="stat-card primary fade-in delay-100">
                        <div class="stat-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($totalOrders) ?></h3>
                            <p class="stat-label">Total Orders</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="stat-card info fade-in delay-200">
                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value">₱<?= number_format($totalRevenue, 2) ?></h3>
                            <p class="stat-label">Total Revenue</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="stat-card success fade-in delay-300">
                        <div class="stat-icon">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($completedOrders) ?></h3>
                            <p class="stat-label">Completed Orders</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="stat-card warning fade-in delay-400">
                        <div class="stat-icon">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($pendingOrders) ?></h3>
                            <p class="stat-label">Pending Orders</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="stat-card secondary fade-in delay-500">
                        <div class="stat-icon">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($processingOrders) ?></h3>
                            <p class="stat-label">Processing Orders</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="stat-card danger fade-in delay-600">
                        <div class="stat-icon">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($cancelledOrders) ?></h3>
                            <p class="stat-label">Cancelled Orders</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Management -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                        <div>
                            <h3 class="mb-1">Orders Management</h3>
                            <p class="text-muted mb-0">View and manage customer orders</p>
                        </div>
                        <form class="d-flex gap-2" method="get" action="">
                            <input type="text" name="search" class="form-control" placeholder="Search customer..." value="<?= htmlspecialchars($search) ?>">
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <button class="btn btn-primary">
                                <i class="bi bi-search me-md-2"></i>
                                <span class="d-none d-md-inline">Search</span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-12">
                    <div class="table-container fade-in-up">
                        <div class="table-header">
                            <h5 class="table-title"><i class="bi bi-receipt"></i> All Orders</h5>
                            <div class="table-actions">
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                                <button class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-download"></i> Export
                                </button>
                            </div>
                        </div>

                        <?php if (count($orders) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold">#<?= htmlspecialchars($order['id']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="cell-with-image">
                                                        <div class="cell-icon">
                                                            <i class="bi bi-person"></i>
                                                        </div>
                                                        <div class="cell-image-content">
                                                            <h6 class="cell-title"><?= htmlspecialchars($order['customer_name']) ?></h6>
                                                            <p class="cell-subtitle"><?= htmlspecialchars($order['email'] ?? 'No email provided') ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <div class="fw-medium"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                                                        <div class="text-muted small"><?= date('h:i A', strtotime($order['created_at'])) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">₱<?= number_format($order['total'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                        $statusClass = '';
                                                        switch ($order['status']) {
                                                            case 'completed': $statusClass = 'success'; break;
                                                            case 'processing': $statusClass = 'info'; break;
                                                            case 'cancelled': $statusClass = 'danger'; break;
                                                            default: $statusClass = 'warning';
                                                        }
                                                    ?>
                                                    <span class="cell-badge <?= $statusClass ?>">
                                                        <?= ucfirst($order['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="cell-actions">
                                                        <button type="button" class="action-button view" data-bs-toggle="modal" data-bs-target="#orderModal" data-order-id="<?= $order['id'] ?>">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <a href="edit_order.php?id=<?= $order['id'] ?>" class="action-button edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="action-button delete" onclick="confirmDelete(<?= $order['id'] ?>)">
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
                                    Showing <span class="fw-bold"><?= count($orders) ?></span> of <span class="fw-bold"><?= count($orders) ?></span> orders
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
                                    <i class="bi bi-receipt" style="font-size: 3rem; color: var(--color-gray-400);"></i>
                                </div>
                                <h4>No Orders Found</h4>
                                <p class="text-muted mb-4">There are no orders matching your search criteria.</p>
                                <a href="orders.php" class="btn btn-primary">
                                    <i class="bi bi-arrow-repeat me-2"></i> Reset Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-receipt text-primary me-2"></i>
                    Order Details
                </h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="orderDetailsContent">
                    <div class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading order details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">
                    <i class="bi bi-printer me-2"></i> Print Order
                </button>
            </div>
        </div>
    </div>
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
                <p>Are you sure you want to delete this order? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div class="alert-content">
                            <p class="alert-text mb-0">Deleting this order will remove it from your system permanently.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="bi bi-trash me-2"></i> Delete Order
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.querySelector('.menu-toggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const sidebarClose = document.querySelector('.sidebar-close');

        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.add('show');
            });
        }

        if (sidebarClose) {
            sidebarClose.addEventListener('click', function() {
                sidebar.classList.remove('show');
            });
        }

        // Delete confirmation
        window.confirmDelete = function(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            document.getElementById('confirmDeleteBtn').href = `delete_order.php?id=${orderId}`;
            modal.show();
        };

        // Load order details into modal (placeholder for AJAX)
        document.getElementById('orderModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var orderId = button.getAttribute('data-order-id');
            var modalBody = document.getElementById('orderDetailsContent');

            // Simulate loading
            setTimeout(() => {
                // Placeholder: Replace with AJAX call to fetch order details
                modalBody.innerHTML = `
                    <div class="order-details">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Order Information</h6>
                                <p class="mb-1"><strong>Order ID:</strong> #${orderId}</p>
                                <p class="mb-1"><strong>Date:</strong> June 15, 2023</p>
                                <p class="mb-1"><strong>Status:</strong> <span class="cell-badge success">Completed</span></p>
                                <p class="mb-0"><strong>Payment Method:</strong> Credit Card</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Customer Information</h6>
                                <p class="mb-1"><strong>Name:</strong> John Doe</p>
                                <p class="mb-1"><strong>Email:</strong> john.doe@example.com</p>
                                <p class="mb-1"><strong>Phone:</strong> +63 912 345 6789</p>
                                <p class="mb-0"><strong>Address:</strong> 123 Main St, Manila, Philippines</p>
                            </div>
                        </div>

                        <h6 class="text-muted mb-3">Order Items</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Chocolate Cake</td>
                                        <td>₱350.00</td>
                                        <td>1</td>
                                        <td class="text-end">₱350.00</td>
                                    </tr>
                                    <tr>
                                        <td>Cappuccino</td>
                                        <td>₱150.00</td>
                                        <td>2</td>
                                        <td class="text-end">₱300.00</td>
                                    </tr>
                                    <tr>
                                        <td>Croissant</td>
                                        <td>₱100.00</td>
                                        <td>2</td>
                                        <td class="text-end">₱200.00</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                        <td class="text-end">₱850.00</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Shipping:</strong></td>
                                        <td class="text-end">₱0.00</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td class="text-end fw-bold">₱850.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <h6 class="text-muted mb-3">Order Notes</h6>
                        <p class="mb-0">Please deliver to the front desk. Thank you!</p>
                    </div>
                `;
            }, 1000);
        });

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>
</body>
</html>