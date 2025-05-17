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

// Get current user
$currentUser = $_SESSION['user'] ?? [];

// Get current date
$currentDate = date('F j, Y');

// Fetch dashboard statistics
try {
    // Check if products table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'products'");
    $stmt->execute();
    $productsTableExists = $stmt->rowCount() > 0;

    // Check if orders table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $ordersTableExists = $stmt->rowCount() > 0;

    // Initialize statistics
    $totalProducts = 0;
    $activeProducts = 0;
    $lowStockProducts = 0;
    $totalOrders = 0;
    $pendingOrders = 0;
    $completedOrders = 0;
    $totalRevenue = 0;
    $recentProducts = [];
    $recentOrders = [];

    // Fetch product statistics
    if ($productsTableExists) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM products");
        $stmt->execute();
        $totalProducts = $stmt->fetchColumn();

        // Check if products table has a status column
        $stmt = $conn->prepare("SHOW COLUMNS FROM products LIKE 'status'");
        $stmt->execute();
        $hasStatusColumn = $stmt->rowCount() > 0;

        if ($hasStatusColumn) {
            // Get active products count (considering various status names)
            $activeStatuses = ['active', 'available', 'published', 'visible', '1'];
            $activeProducts = 0;

            foreach ($activeStatuses as $status) {
                if (is_numeric($status)) {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE status = ?");
                } else {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE LOWER(status) = ?");
                }
                $stmt->execute([$status]);
                $activeProducts += $stmt->fetchColumn();
            }
        } else {
            // If no status column, assume all products are active
            $activeProducts = $totalProducts;
        }

        // Check which column contains the stock information
        $stockColumn = null;
        $possibleStockColumns = ['stock', 'quantity', 'inventory', 'qty', 'stock_level'];

        foreach ($possibleStockColumns as $column) {
            $stmt = $conn->prepare("SHOW COLUMNS FROM products LIKE ?");
            $stmt->execute([$column]);
            if ($stmt->rowCount() > 0) {
                $stockColumn = $column;
                break;
            }
        }

        // If we found a stock column, count low stock products
        if ($stockColumn) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE $stockColumn < 10");
            $stmt->execute();
            $lowStockProducts = $stmt->fetchColumn();
        } else {
            $lowStockProducts = 0;
        }

        // Fetch recent products
        try {
            // Check which columns exist in the products table
            $stmt = $conn->prepare("DESCRIBE products");
            $stmt->execute();
            $productColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Check if categories table exists
            $stmt = $conn->prepare("SHOW TABLES LIKE 'categories'");
            $stmt->execute();
            $categoriesTableExists = $stmt->rowCount() > 0;

            // Determine if we can join with categories table
            $canJoinWithCategories = false;
            $joinCondition = "";

            if ($categoriesTableExists) {
                // Check which columns exist in the categories table
                $stmt = $conn->prepare("DESCRIBE categories");
                $stmt->execute();
                $categoryColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

                // Determine the join condition based on available columns
                if (in_array('category_id', $productColumns) && in_array('id', $categoryColumns)) {
                    $canJoinWithCategories = true;
                    $joinCondition = "p.category_id = c.id";
                } elseif (in_array('category', $productColumns) && in_array('name', $categoryColumns)) {
                    $canJoinWithCategories = true;
                    $joinCondition = "p.category = c.name";
                }
            }

            // Determine the ORDER BY clause based on available columns
            $orderByClause = "";
            $possibleDateColumns = ['created_at', 'date_added', 'added_date', 'timestamp'];

            foreach ($possibleDateColumns as $column) {
                if (in_array($column, $productColumns)) {
                    $orderByClause = "p.$column DESC";
                    break;
                }
            }

            // If no date column found, order by ID
            if (empty($orderByClause) && in_array('id', $productColumns)) {
                $orderByClause = "p.id DESC";
            } elseif (empty($orderByClause)) {
                // Fallback to a generic ORDER BY
                $orderByClause = "1";
            }

            // Build the SQL query based on available columns
            if ($canJoinWithCategories) {
                $sql = "
                    SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON $joinCondition
                    ORDER BY $orderByClause
                    LIMIT 5
                ";
            } else {
                $sql = "
                    SELECT *
                    FROM products
                    ORDER BY $orderByClause
                    LIMIT 5
                ";
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $recentProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If there's an error with the recent products query, set to empty array
            $recentProducts = [];
        }
    }

    // Fetch order statistics
    if ($ordersTableExists) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders");
        $stmt->execute();
        $totalOrders = $stmt->fetchColumn();

        // Get pending orders count (considering various status names)
        $pendingStatuses = ['pending', 'processing', 'waiting'];
        $pendingOrders = 0;

        foreach ($pendingStatuses as $status) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE LOWER(status) = ?");
            $stmt->execute([$status]);
            $pendingOrders += $stmt->fetchColumn();
        }

        // Get completed orders count (considering various status names)
        $completedStatuses = ['completed', 'delivered', 'finished'];
        $completedOrders = 0;

        foreach ($completedStatuses as $status) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE LOWER(status) = ?");
            $stmt->execute([$status]);
            $completedOrders += $stmt->fetchColumn();
        }

        // Check which column contains the order total
        $stmt = $conn->prepare("DESCRIBE orders");
        $stmt->execute();
        $orderColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        // Look for possible total column names
        $possibleTotalColumns = ['total', 'total_amount', 'amount', 'price', 'order_total', 'grand_total'];
        $totalColumn = null;

        foreach ($possibleTotalColumns as $column) {
            if (in_array($column, $orderColumns)) {
                $totalColumn = $column;
                break;
            }
        }

        // If we found a total column, sum it
        if ($totalColumn) {
            $stmt = $conn->prepare("SELECT SUM($totalColumn) FROM orders");
            $stmt->execute();
            $totalRevenue = $stmt->fetchColumn() ?: 0;
        } else {
            // If no total column found, set revenue to 0
            $totalRevenue = 0;
        }

        // Fetch recent orders
        try {
            // Check which columns exist in the orders table
            $stmt = $conn->prepare("DESCRIBE orders");
            $stmt->execute();
            $orderColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Check which columns exist in the users table
            $stmt = $conn->prepare("DESCRIBE users");
            $stmt->execute();
            $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Determine if we can join with users table
            $canJoinWithUsers = false;
            $joinCondition = "";

            if (in_array('user_id', $orderColumns) && in_array('id', $userColumns)) {
                $canJoinWithUsers = true;
                $joinCondition = "o.user_id = u.id";
            } elseif (in_array('client_id', $orderColumns) && in_array('id', $userColumns)) {
                $canJoinWithUsers = true;
                $joinCondition = "o.client_id = u.id";
            } elseif (in_array('customer_id', $orderColumns) && in_array('id', $userColumns)) {
                $canJoinWithUsers = true;
                $joinCondition = "o.customer_id = u.id";
            }

            // Build the SQL query based on available columns
            if ($canJoinWithUsers) {
                // Check which columns exist in the users table to build the SELECT clause
                $userSelectFields = [];

                // Always include these fields if they exist
                if (in_array('name', $userColumns)) {
                    $userSelectFields[] = "u.name as customer_name";
                }
                if (in_array('email', $userColumns)) {
                    $userSelectFields[] = "u.email";
                }
                if (in_array('phone', $userColumns)) {
                    $userSelectFields[] = "u.phone as customer_phone";
                } else if (in_array('contact_number', $userColumns)) {
                    $userSelectFields[] = "u.contact_number as customer_phone";
                } else if (in_array('mobile', $userColumns)) {
                    $userSelectFields[] = "u.mobile as customer_phone";
                }
                if (in_array('first_name', $userColumns) && in_array('last_name', $userColumns)) {
                    $userSelectFields[] = "CONCAT(u.first_name, ' ', u.last_name) as full_name";
                }

                // Build the SQL query with the available fields
                $userSelectClause = !empty($userSelectFields) ? ", " . implode(", ", $userSelectFields) : "";

                $sql = "
                    SELECT o.*$userSelectClause
                    FROM orders o
                    LEFT JOIN users u ON $joinCondition
                    ORDER BY o.created_at DESC
                    LIMIT 5
                ";
            } else {
                $sql = "
                    SELECT *
                    FROM orders
                    ORDER BY created_at DESC
                    LIMIT 5
                ";
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // If there's an error with the recent orders query, set to empty array
            $recentOrders = [];
        }
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching dashboard data: " . $e->getMessage();

    // Set default values in case of error
    $totalProducts = 0;
    $activeProducts = 0;
    $lowStockProducts = 0;
    $totalOrders = 0;
    $pendingOrders = 0;
    $completedOrders = 0;
    $totalRevenue = 0;
    $recentProducts = [];
    $recentOrders = [];
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
    <?php include 'includes/css-includes.php'; ?>
</head>
<body>
<!-- Admin Container -->
<div class="admin-container">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

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

            <!-- Dashboard Header -->
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="text-muted">Welcome back, <?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?>! Here's what's happening today.</p>
                </div>
                <div class="date-display">
                    <i class="bi bi-calendar3 me-2"></i>
                    <span><?= $currentDate ?></span>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="row mb-4">
                <div class="col-12 mb-3">
                    <h2 class="section-title">Store Overview</h2>
                </div>

                <!-- Orders Stats -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                                    <i class="bi bi-cart"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1">Total Orders</h6>
                                    <h3 class="card-title mb-0"><?= number_format($totalOrders) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Stats -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1">Total Revenue</h6>
                                    <h3 class="card-title mb-0">₱<?= number_format($totalRevenue, 2) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Stats -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                                    <i class="bi bi-box"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1">Total Products</h6>
                                    <h3 class="card-title mb-0"><?= number_format($totalProducts) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Stats -->
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1">Low Stock Items</h6>
                                    <h3 class="card-title mb-0"><?= number_format($lowStockProducts) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Stats Row -->
            <div class="row mb-4">
                <!-- Pending Orders -->
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                                    <i class="bi bi-hourglass-split"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1">Pending Orders</h6>
                                    <h3 class="card-title mb-0"><?= number_format($pendingOrders) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completed Orders -->
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                                    <i class="bi bi-check-circle"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1">Completed Orders</h6>
                                    <h3 class="card-title mb-0"><?= number_format($completedOrders) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Products -->
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card stats-card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                                    <i class="bi bi-tag"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted mb-1">Active Products</h6>
                                    <h3 class="card-title mb-0"><?= number_format($activeProducts) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="row mb-4">
                <!-- Recent Orders -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-cart me-2"></i>Recent Orders</h5>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($recentOrders) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td>#<?= $order['id'] ?></td>
                                                    <td>
                                                        <div class="cell-with-image">
                                                            <div class="cell-icon">
                                                                <i class="bi bi-person"></i>
                                                            </div>
                                                            <div>
                                                                <div class="cell-title"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></div>
                                                                <div class="cell-subtitle"><?= htmlspecialchars($order['email'] ?? 'No email') ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fw-bold">
                                                        <?php
                                                        // Check which column contains the order total
                                                        $orderTotal = 0;
                                                        $possibleTotalColumns = ['total', 'total_amount', 'amount', 'price', 'order_total', 'grand_total'];
                                                        foreach ($possibleTotalColumns as $column) {
                                                            if (isset($order[$column])) {
                                                                $orderTotal = $order[$column];
                                                                break;
                                                            }
                                                        }
                                                        ?>
                                                        ₱<?= number_format($orderTotal, 2) ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusClass = 'info';
                                                        $status = isset($order['status']) ? strtolower($order['status']) : 'unknown';

                                                        if ($status === 'completed' || $status === 'delivered' || $status === 'finished') {
                                                            $statusClass = 'success';
                                                        } elseif ($status === 'pending' || $status === 'processing' || $status === 'waiting') {
                                                            $statusClass = 'warning';
                                                        } elseif ($status === 'cancelled' || $status === 'canceled' || $status === 'failed') {
                                                            $statusClass = 'danger';
                                                        }
                                                        ?>
                                                        <span class="cell-badge <?= $statusClass ?>">
                                                            <?= ucfirst($status) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        // Check which column contains the order date
                                                        $orderDate = '';
                                                        $possibleDateColumns = ['created_at', 'date', 'order_date', 'created_date', 'timestamp'];
                                                        foreach ($possibleDateColumns as $column) {
                                                            if (isset($order[$column])) {
                                                                $orderDate = date('M j, Y', strtotime($order[$column]));
                                                                break;
                                                            }
                                                        }
                                                        echo $orderDate ?: 'N/A';
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-4 text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-cart" style="font-size: 2rem; color: var(--color-gray-400);"></i>
                                    </div>
                                    <h5>No Recent Orders</h5>
                                    <p class="text-muted">There are no recent orders to display.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Products -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="bi bi-box me-2"></i>Recent Products</h5>
                            <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($recentProducts) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Price</th>
                                                <th>Stock</th>
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
                                                            <div>
                                                                <div class="cell-title"><?= htmlspecialchars($product['name']) ?></div>
                                                                <div class="cell-subtitle"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 30)) . (strlen($product['description'] ?? '') > 30 ? '...' : '') ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="cell-badge primary">
                                                            <?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?>
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold">
                                                        <?php
                                                        // Check which column contains the price information
                                                        $productPrice = 0;
                                                        $possiblePriceColumns = ['price', 'unit_price', 'selling_price', 'retail_price', 'cost'];

                                                        foreach ($possiblePriceColumns as $column) {
                                                            if (isset($product[$column])) {
                                                                $productPrice = $product[$column];
                                                                break;
                                                            }
                                                        }
                                                        ?>
                                                        ₱<?= number_format($productPrice, 2) ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        // Check which column contains the stock information
                                                        $productStock = 'N/A';
                                                        $isLowStock = false;
                                                        $possibleStockColumns = ['stock', 'quantity', 'inventory', 'qty', 'stock_level'];

                                                        foreach ($possibleStockColumns as $column) {
                                                            if (isset($product[$column])) {
                                                                $productStock = $product[$column];
                                                                $isLowStock = $productStock < 10;
                                                                break;
                                                            }
                                                        }
                                                        ?>
                                                        <span class="fw-bold <?= $isLowStock ? 'text-warning' : '' ?>">
                                                            <?= htmlspecialchars($productStock) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-4 text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-box" style="font-size: 2rem; color: var(--color-gray-400);"></i>
                                    </div>
                                    <h5>No Recent Products</h5>
                                    <p class="text-muted">There are no recent products to display.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions and System Status -->
            <div class="row mb-4">
                <!-- Quick Actions -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="add_product.php" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-2"></i> Add New Product
                                </a>
                                <a href="orders.php" class="btn btn-secondary">
                                    <i class="bi bi-cart me-2"></i> View Orders
                                </a>
                                <a href="analytics.php" class="btn btn-info">
                                    <i class="bi bi-graph-up me-2"></i> View Analytics
                                </a>
                                <a href="settings.php" class="btn btn-light">
                                    <i class="bi bi-gear me-2"></i> System Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>System Status</h5>
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
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="bi bi-clock-history text-success me-2"></i>
                                        Last System Update
                                    </div>
                                    <span><?= date('M j, Y') ?></span>
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