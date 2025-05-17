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

// Get filter parameters
$dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$paymentMethod = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$timeFrame = isset($_GET['time_frame']) ? $_GET['time_frame'] : 'daily';

// Set default date range if not provided (last 30 days)
if (empty($dateRange)) {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-30 days'));
    $dateRange = "$startDate - $endDate";
} else {
    $dates = explode(' - ', $dateRange);
    if (count($dates) == 2) {
        $startDate = date('Y-m-d', strtotime($dates[0]));
        $endDate = date('Y-m-d', strtotime($dates[1]));
    } else {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime('-30 days'));
    }
}

// Fetch categories for filter
try {
    $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
    $errorMessage = "Error fetching categories: " . $e->getMessage();
}

// Check if tables exist
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $ordersTableExists = $stmt->rowCount() > 0;

    $stmt = $conn->prepare("SHOW TABLES LIKE 'order_items'");
    $stmt->execute();
    $orderItemsTableExists = $stmt->rowCount() > 0;

    $stmt = $conn->prepare("SHOW TABLES LIKE 'products'");
    $stmt->execute();
    $productsTableExists = $stmt->rowCount() > 0;

    $stmt = $conn->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $usersTableExists = $stmt->rowCount() > 0;

    $stmt = $conn->prepare("SHOW TABLES LIKE 'payments'");
    $stmt->execute();
    $paymentsTableExists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $errorMessage = "Error checking tables: " . $e->getMessage();
    $ordersTableExists = $orderItemsTableExists = $productsTableExists = $usersTableExists = $paymentsTableExists = false;
}

// Initialize analytics data
$totalOrders = 0;
$totalRevenue = 0;
$averageOrderValue = 0;
$totalCustomers = 0;
$newCustomers = 0;
$returningCustomers = 0;
$pendingOrders = 0;
$completedOrders = 0;
$cancelledOrders = 0;
$topProducts = [];
$lowStockProducts = [];
$salesByCategory = [];
$salesByPaymentMethod = [];
$salesTrend = [];
$customerRetention = 0;

// Fetch analytics data if tables exist
if ($ordersTableExists && $orderItemsTableExists && $productsTableExists) {
    try {
        // 1. Key Performance Indicators (KPIs)
        // Total Orders
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE created_at BETWEEN ? AND ?");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $totalOrders = $stmt->fetchColumn() ?: 0;

        // Total Revenue
        $stmt = $conn->prepare("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled' AND created_at BETWEEN ? AND ?");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $totalRevenue = $stmt->fetchColumn() ?: 0;

        // Average Order Value
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Order Status Counts
        $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $orderStatusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orderStatusCounts as $status) {
            if ($status['status'] === 'pending') {
                $pendingOrders = $status['count'];
            } elseif ($status['status'] === 'completed') {
                $completedOrders = $status['count'];
            } elseif ($status['status'] === 'cancelled') {
                $cancelledOrders = $status['count'];
            }
        }

        // 2. Customer Insights
        if ($usersTableExists) {
            // Total Customers
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT client_id) FROM orders WHERE created_at BETWEEN ? AND ?");
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $totalCustomers = $stmt->fetchColumn() ?: 0;

            // New Customers (first order in the date range)
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM (
                    SELECT client_id, MIN(created_at) as first_order_date
                    FROM orders
                    GROUP BY client_id
                    HAVING first_order_date BETWEEN ? AND ?
                ) as new_customers
            ");
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $newCustomers = $stmt->fetchColumn() ?: 0;

            // Returning Customers
            $returningCustomers = $totalCustomers - $newCustomers;

            // Customer Retention Rate
            $customerRetention = $totalCustomers > 0 ? ($returningCustomers / $totalCustomers) * 100 : 0;
        }

        // 3. Product Performance
        // Top Selling Products
        $stmt = $conn->prepare("
            SELECT p.id, p.name, p.price, p.stock, p.image, c.name as category_name,
                   SUM(oi.quantity) as total_quantity,
                   SUM(oi.total_price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY total_quantity DESC
            LIMIT 10
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Low Stock Products
        $stmt = $conn->prepare("
            SELECT id, name, price, stock, image, category_id
            FROM products
            WHERE stock < 10 AND status = 'active'
            ORDER BY stock ASC
            LIMIT 10
        ");
        $stmt->execute();
        $lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Sales by Category
        $stmt = $conn->prepare("
            SELECT c.name as category_name, SUM(oi.total_price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY total_revenue DESC
        ");
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $salesByCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Payment Methods
        if ($paymentsTableExists) {
            $stmt = $conn->prepare("
                SELECT payment_method, COUNT(*) as count, SUM(amount) as total_amount
                FROM payments
                JOIN orders o ON payments.order_id = o.id
                WHERE o.created_at BETWEEN ? AND ?
                GROUP BY payment_method
            ");
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $salesByPaymentMethod = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 5. Sales Trend Data
        // Determine the appropriate grouping based on the selected time frame
        $groupFormat = '';
        $groupBy = '';

        switch ($timeFrame) {
            case 'daily':
                $groupFormat = '%Y-%m-%d';
                $groupBy = 'day';
                break;
            case 'weekly':
                $groupFormat = '%Y-%u';
                $groupBy = 'week';
                break;
            case 'monthly':
                $groupFormat = '%Y-%m';
                $groupBy = 'month';
                break;
            case 'yearly':
                $groupFormat = '%Y';
                $groupBy = 'year';
                break;
            default:
                $groupFormat = '%Y-%m-%d';
                $groupBy = 'day';
        }

        $stmt = $conn->prepare("
            SELECT
                DATE_FORMAT(created_at, ?) as period,
                SUM(total_price) as revenue,
                COUNT(*) as orders
            FROM orders
            WHERE created_at BETWEEN ? AND ?
            GROUP BY period
            ORDER BY period ASC
        ");
        $stmt->execute([$groupFormat, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $salesTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $errorMessage = "Error fetching analytics data: " . $e->getMessage();
    }
} else {
    // Placeholder data for demonstration
    $totalOrders = 156;
    $totalRevenue = 125750.50;
    $averageOrderValue = 806.09;
    $totalCustomers = 87;
    $newCustomers = 32;
    $returningCustomers = 55;
    $pendingOrders = 18;
    $completedOrders = 132;
    $cancelledOrders = 6;
    $customerRetention = 63.22;

    // Sample top products
    $topProducts = [
        ['name' => 'Cappuccino', 'total_quantity' => 245, 'total_revenue' => 29400, 'category_name' => 'Coffee'],
        ['name' => 'Chocolate Cake', 'total_quantity' => 120, 'total_revenue' => 21600, 'category_name' => 'Cakes'],
        ['name' => 'Latte', 'total_quantity' => 180, 'total_revenue' => 23400, 'category_name' => 'Coffee'],
        ['name' => 'Croissant', 'total_quantity' => 210, 'total_revenue' => 15750, 'category_name' => 'Pastries'],
        ['name' => 'Americano', 'total_quantity' => 165, 'total_revenue' => 15675, 'category_name' => 'Coffee']
    ];

    // Sample low stock products
    $lowStockProducts = [
        ['name' => 'Red Velvet Cake', 'stock' => 3, 'category_id' => 2],
        ['name' => 'Ube Cake', 'stock' => 5, 'category_id' => 2],
        ['name' => 'Club Sandwich', 'stock' => 7, 'category_id' => 5]
    ];

    // Sample sales by category
    $salesByCategory = [
        ['category_name' => 'Coffee', 'total_revenue' => 68475],
        ['category_name' => 'Cakes', 'total_revenue' => 32400],
        ['category_name' => 'Pastries', 'total_revenue' => 15750],
        ['category_name' => 'Non-Coffee Drinks', 'total_revenue' => 5625],
        ['category_name' => 'Sandwiches', 'total_revenue' => 3500]
    ];

    // Sample sales by payment method
    $salesByPaymentMethod = [
        ['payment_method' => 'gcash', 'count' => 78, 'total_amount' => 62875.25],
        ['payment_method' => 'cash', 'count' => 45, 'total_amount' => 36270.00],
        ['payment_method' => 'credit_card', 'count' => 33, 'total_amount' => 26605.25]
    ];

    // Sample sales trend data
    $salesTrend = [];
    $startTimestamp = strtotime($startDate);
    $endTimestamp = strtotime($endDate);
    $currentTimestamp = $startTimestamp;

    while ($currentTimestamp <= $endTimestamp) {
        $day = date('Y-m-d', $currentTimestamp);
        $salesTrend[] = [
            'period' => $day,
            'revenue' => rand(3000, 6000),
            'orders' => rand(5, 15)
        ];
        $currentTimestamp = strtotime('+1 day', $currentTimestamp);
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

// Function to get chart colors
function getChartColor($index) {
    $colors = [
        '#f59e0b', // Amber/Gold (Primary)
        '#3b82f6', // Blue
        '#10b981', // Green
        '#8b5cf6', // Purple
        '#ef4444', // Red
        '#f97316', // Orange
        '#06b6d4', // Cyan
        '#ec4899', // Pink
        '#84cc16', // Lime
        '#14b8a6', // Teal
        '#6366f1', // Indigo
        '#f43f5e', // Rose
    ];

    return $colors[$index % count($colors)];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Brew & Bake Admin</title>
    <?php include 'includes/css-includes.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        /* Analytics Dashboard Styles */
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
            height: 100%;
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

        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }

        .chart-card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
        }

        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .product-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 1rem;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .product-category {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .product-stats {
            text-align: right;
            min-width: 80px;
        }

        .export-dropdown .dropdown-menu {
            min-width: 200px;
            padding: 1rem;
        }

        .export-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
        }

        .export-dropdown .dropdown-item i {
            margin-right: 0.5rem;
            font-size: 1.25rem;
        }

        .time-period-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 0;
            border-bottom: 3px solid transparent;
        }

        .time-period-tabs .nav-link.active {
            color: #111827;
            border-bottom: 3px solid #f59e0b;
            background-color: transparent;
        }

        .time-period-tabs .nav-link:hover:not(.active) {
            border-bottom: 3px solid #e5e7eb;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .admin-content {
                padding: 1rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }

            .stats-icon {
                width: 42px;
                height: 42px;
                font-size: 1.3rem;
            }

            .chart-container {
                height: 250px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .page-header > div:last-child {
                width: 100%;
            }

            .filter-section {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .admin-content {
                padding: 0.75rem;
            }

            .card-header, .card-body, .card-footer {
                padding: 0.75rem !important;
            }

            .filter-section {
                padding: 0.75rem;
            }

            .stats-icon {
                width: 38px;
                height: 38px;
                font-size: 1.2rem;
            }

            .stats-card .card-title {
                font-size: 1.25rem;
            }

            .chart-container {
                height: 220px;
            }

            .time-period-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
                margin-bottom: 1rem;
                padding-bottom: 5px;
            }

            .time-period-tabs::-webkit-scrollbar {
                height: 3px;
            }

            .time-period-tabs::-webkit-scrollbar-thumb {
                background-color: rgba(0,0,0,0.2);
                border-radius: 3px;
            }

            .time-period-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
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

            .card-header, .card-body, .card-footer {
                padding: 0.625rem !important;
            }

            .filter-section {
                padding: 0.625rem;
                margin-bottom: 0.75rem;
            }

            /* Adjust stats cards for better mobile view */
            .stats-card {
                margin-bottom: 0.5rem;
            }

            .stats-card .card-body {
                padding: 0.75rem !important;
            }

            .stats-icon {
                width: 36px;
                height: 36px;
                font-size: 1.1rem;
                margin-right: 0.5rem !important;
            }

            .stats-card .card-title {
                font-size: 1.1rem;
                margin-bottom: 0;
            }

            .stats-card .card-subtitle {
                font-size: 0.7rem;
                margin-bottom: 0.25rem;
            }

            .chart-container {
                height: 180px;
            }

            /* Adjust form elements for better mobile viewing */
            .form-select, .form-control, .btn {
                font-size: 0.875rem;
                padding: 0.375rem 0.5rem;
            }

            /* Very small screens (under 576px) */
            @media (max-width: 576px) {
                .chart-container {
                    height: 160px;
                }

                .product-image {
                    width: 32px;
                    height: 32px;
                    margin-right: 0.5rem;
                }

                .product-name {
                    font-size: 0.9rem;
                }

                .product-category {
                    font-size: 0.7rem;
                }

                .product-stats {
                    min-width: 60px;
                    font-size: 0.85rem;
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
                        <h1 class="page-title">Analytics Dashboard</h1>
                        <p class="text-muted">Track your business performance and make data-driven decisions</p>
                    </div>
                    <div class="dropdown export-dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download me-2"></i>Export Report
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                            <li><h6 class="dropdown-header">Export Options</h6></li>
                            <li><a class="dropdown-item" href="#" id="exportCSV"><i class="bi bi-filetype-csv"></i>CSV File</a></li>
                            <li><a class="dropdown-item" href="#" id="exportPDF"><i class="bi bi-filetype-pdf"></i>PDF Document</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="printReport"><i class="bi bi-printer"></i>Print Report</a></li>
                        </ul>
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

                <!-- Filter Section -->
                <div class="filter-section">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                <input type="text" class="form-control" id="dateRangePicker" name="date_range" placeholder="Date range" value="<?= htmlspecialchars($dateRange) ?>">
                            </div>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($cat['name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6">
                            <select class="form-select" name="payment_method">
                                <option value="">All Payment Methods</option>
                                <option value="cash" <?= $paymentMethod === 'cash' ? 'selected' : '' ?>>Cash</option>
                                <option value="credit_card" <?= $paymentMethod === 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                                <option value="gcash" <?= $paymentMethod === 'gcash' ? 'selected' : '' ?>>GCash</option>
                                <option value="bank_transfer" <?= $paymentMethod === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-12">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </form>
                </div>

                <!-- KPI Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
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
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Status Breakdown</small>
                                    </div>
                                    <div class="progress mt-1" style="height: 6px;">
                                        <?php
                                        $pendingPercent = $totalOrders > 0 ? ($pendingOrders / $totalOrders) * 100 : 0;
                                        $completedPercent = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;
                                        $cancelledPercent = $totalOrders > 0 ? ($cancelledOrders / $totalOrders) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $pendingPercent ?>%" aria-valuenow="<?= $pendingPercent ?>" aria-valuemin="0" aria-valuemax="100" title="Pending: <?= $pendingOrders ?>"></div>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $completedPercent ?>%" aria-valuenow="<?= $completedPercent ?>" aria-valuemin="0" aria-valuemax="100" title="Completed: <?= $completedOrders ?>"></div>
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $cancelledPercent ?>%" aria-valuenow="<?= $cancelledPercent ?>" aria-valuemin="0" aria-valuemax="100" title="Cancelled: <?= $cancelledOrders ?>"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small><span class="text-warning">■</span> Pending (<?= $pendingOrders ?>)</small>
                                        <small><span class="text-success">■</span> Completed (<?= $completedOrders ?>)</small>
                                        <small><span class="text-danger">■</span> Cancelled (<?= $cancelledOrders ?>)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
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
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Average Order Value</small>
                                        <span class="badge bg-success">₱<?= number_format($averageOrderValue, 2) ?></span>
                                    </div>
                                    <div class="progress mt-1" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Total Customers</h6>
                                        <h3 class="card-title mb-0"><?= number_format($totalCustomers) ?></h3>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Customer Breakdown</small>
                                    </div>
                                    <div class="progress mt-1" style="height: 6px;">
                                        <?php
                                        $newPercent = $totalCustomers > 0 ? ($newCustomers / $totalCustomers) * 100 : 0;
                                        $returningPercent = $totalCustomers > 0 ? ($returningCustomers / $totalCustomers) * 100 : 0;
                                        ?>
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $newPercent ?>%" aria-valuenow="<?= $newPercent ?>" aria-valuemin="0" aria-valuemax="100" title="New: <?= $newCustomers ?>"></div>
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?= $returningPercent ?>%" aria-valuenow="<?= $returningPercent ?>" aria-valuemin="0" aria-valuemax="100" title="Returning: <?= $returningCustomers ?>"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <small><span class="text-primary">■</span> New (<?= $newCustomers ?>)</small>
                                        <small><span class="text-info">■</span> Returning (<?= $returningCustomers ?>)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Customer Retention</h6>
                                        <h3 class="card-title mb-0"><?= number_format($customerRetention, 1) ?>%</h3>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">Retention Rate</small>
                                    </div>
                                    <div class="progress mt-1" style="height: 6px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $customerRetention ?>%" aria-valuenow="<?= $customerRetention ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            <?php if ($customerRetention > 70): ?>
                                                <i class="bi bi-emoji-smile text-success"></i> Excellent retention rate
                                            <?php elseif ($customerRetention > 40): ?>
                                                <i class="bi bi-emoji-neutral text-warning"></i> Average retention rate
                                            <?php else: ?>
                                                <i class="bi bi-emoji-frown text-danger"></i> Low retention rate
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Trend Chart -->
                <div class="card chart-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-graph-up me-2"></i>Sales Analytics</h5>
                        <ul class="nav nav-tabs time-period-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?= $timeFrame === 'daily' ? 'active' : '' ?>" href="?time_frame=daily<?= !empty($dateRange) ? '&date_range=' . urlencode($dateRange) : '' ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($paymentMethod) ? '&payment_method=' . urlencode($paymentMethod) : '' ?>">Daily</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $timeFrame === 'weekly' ? 'active' : '' ?>" href="?time_frame=weekly<?= !empty($dateRange) ? '&date_range=' . urlencode($dateRange) : '' ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($paymentMethod) ? '&payment_method=' . urlencode($paymentMethod) : '' ?>">Weekly</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $timeFrame === 'monthly' ? 'active' : '' ?>" href="?time_frame=monthly<?= !empty($dateRange) ? '&date_range=' . urlencode($dateRange) : '' ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($paymentMethod) ? '&payment_method=' . urlencode($paymentMethod) : '' ?>">Monthly</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $timeFrame === 'yearly' ? 'active' : '' ?>" href="?time_frame=yearly<?= !empty($dateRange) ? '&date_range=' . urlencode($dateRange) : '' ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($paymentMethod) ? '&payment_method=' . urlencode($paymentMethod) : '' ?>">Yearly</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesTrendChart"
                                data-labels='<?= json_encode(array_column($salesTrend, 'period')) ?>'
                                data-revenue='<?= json_encode(array_column($salesTrend, 'revenue')) ?>'
                                data-orders='<?= json_encode(array_column($salesTrend, 'orders')) ?>'>
                            </canvas>
                        </div>
                    </div>
                </div>

                <!-- Product Performance and Customer Insights -->
                <div class="row mb-4">
                    <!-- Product Performance -->
                    <div class="col-lg-8 mb-4 mb-lg-0">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0"><i class="bi bi-box me-2"></i>Product Performance</h5>
                                <ul class="nav nav-tabs card-header-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#top-products">Top Sellers</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="tab" href="#low-stock">Low Stock</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- Top Products Tab -->
                                    <div class="tab-pane fade show active" id="top-products">
                                        <?php if (empty($topProducts)): ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-box" style="font-size: 3rem; color: #d1d5db;"></i>
                                                <h5 class="mt-3">No product data available</h5>
                                                <p class="text-muted">Try adjusting your filter criteria or date range</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($topProducts as $index => $product): ?>
                                                <div class="product-item">
                                                    <div class="product-image">
                                                        <?php if (!empty($product['image'])): ?>
                                                            <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                                        <?php else: ?>
                                                            <?php
                                                            $categoryImage = getCategoryImage($product['category_name'] ?? '');
                                                            if (!empty($categoryImage)):
                                                            ?>
                                                                <img src="../../assets/images/categories/<?= $categoryImage ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="opacity: 0.7;">
                                                            <?php else: ?>
                                                                <i class="bi bi-box"></i>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="product-details">
                                                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                                        <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></div>
                                                    </div>
                                                    <div class="product-stats">
                                                        <div class="fw-bold"><?= number_format($product['total_quantity']) ?> sold</div>
                                                        <div class="text-success">₱<?= number_format($product['total_revenue'], 2) ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Low Stock Tab -->
                                    <div class="tab-pane fade" id="low-stock">
                                        <?php if (empty($lowStockProducts)): ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-check-circle" style="font-size: 3rem; color: #10b981;"></i>
                                                <h5 class="mt-3">All products are well-stocked</h5>
                                                <p class="text-muted">No products with low inventory levels</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($lowStockProducts as $product): ?>
                                                <div class="product-item">
                                                    <div class="product-image">
                                                        <?php if (!empty($product['image'])): ?>
                                                            <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                                        <?php else: ?>
                                                            <i class="bi bi-box"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="product-details">
                                                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                                        <div class="product-category">
                                                            <?php
                                                            $categoryName = 'Uncategorized';
                                                            foreach ($categories as $cat) {
                                                                if ($cat['id'] == $product['category_id']) {
                                                                    $categoryName = $cat['name'];
                                                                    break;
                                                                }
                                                            }
                                                            echo htmlspecialchars(ucfirst($categoryName));
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <div class="product-stats">
                                                        <div class="fw-bold text-<?= $product['stock'] < 5 ? 'danger' : 'warning' ?>">
                                                            <?= $product['stock'] ?> in stock
                                                        </div>
                                                        <div>₱<?= number_format($product['price'], 2) ?></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales by Category -->
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-pie-chart me-2"></i>Sales by Category</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="categoryChart"
                                        data-labels='<?= json_encode(array_column($salesByCategory, 'category_name')) ?>'
                                        data-values='<?= json_encode(array_column($salesByCategory, 'total_revenue')) ?>'
                                        data-colors='<?= json_encode(array_map(function($index) { return getChartColor($index); }, array_keys($salesByCategory))) ?>'>
                                    </canvas>
                                </div>
                                <div class="mt-3">
                                    <?php foreach ($salesByCategory as $index => $category): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <div class="me-2" style="width: 12px; height: 12px; border-radius: 50%; background-color: <?= getChartColor($index) ?>;"></div>
                                                <span><?= htmlspecialchars($category['category_name'] ?? 'Uncategorized') ?></span>
                                            </div>
                                            <span class="fw-bold">₱<?= number_format($category['total_revenue'], 2) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods and Order Times -->
                <div class="row mb-4">
                    <!-- Payment Methods -->
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-credit-card me-2"></i>Payment Methods</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="paymentMethodChart"
                                        data-labels='<?= json_encode(array_map(function($payment) {
                                            $method = $payment['payment_method'];
                                            if ($method === 'credit_card') return 'Credit Card';
                                            if ($method === 'gcash') return 'GCash';
                                            if ($method === 'bank_transfer') return 'Bank Transfer';
                                            return 'Cash';
                                        }, $salesByPaymentMethod)) ?>'
                                        data-values='<?= json_encode(array_column($salesByPaymentMethod, 'total_amount')) ?>'>
                                    </canvas>
                                </div>
                                <div class="mt-3">
                                    <?php if (empty($salesByPaymentMethod)): ?>
                                        <div class="text-center py-2">
                                            <p class="text-muted">No payment data available for the selected period</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Payment Method</th>
                                                        <th class="text-center">Orders</th>
                                                        <th class="text-end">Amount</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($salesByPaymentMethod as $payment): ?>
                                                        <tr>
                                                            <td>
                                                                <?php
                                                                $paymentIcon = 'cash';
                                                                $paymentName = 'Cash';

                                                                if ($payment['payment_method'] === 'credit_card') {
                                                                    $paymentIcon = 'credit-card';
                                                                    $paymentName = 'Credit Card';
                                                                } elseif ($payment['payment_method'] === 'gcash') {
                                                                    $paymentIcon = 'wallet2';
                                                                    $paymentName = 'GCash';
                                                                } elseif ($payment['payment_method'] === 'bank_transfer') {
                                                                    $paymentIcon = 'bank';
                                                                    $paymentName = 'Bank Transfer';
                                                                }
                                                                ?>
                                                                <i class="bi bi-<?= $paymentIcon ?> me-2"></i><?= $paymentName ?>
                                                            </td>
                                                            <td class="text-center"><?= number_format($payment['count']) ?></td>
                                                            <td class="text-end">₱<?= number_format($payment['total_amount'], 2) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Fulfillment -->
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-clock-history me-2"></i>Order Fulfillment</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container" style="height: 250px;">
                                    <canvas id="orderStatusChart"
                                        data-values='[<?= $pendingOrders ?>, <?= $completedOrders ?>, <?= $cancelledOrders ?>]'>
                                    </canvas>
                                </div>
                                <div class="mt-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="d-flex flex-column">
                                                <span class="fs-4 fw-bold text-warning"><?= number_format($pendingOrders) ?></span>
                                                <span class="text-muted">Pending</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="d-flex flex-column">
                                                <span class="fs-4 fw-bold text-success"><?= number_format($completedOrders) ?></span>
                                                <span class="text-muted">Completed</span>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="d-flex flex-column">
                                                <span class="fs-4 fw-bold text-danger"><?= number_format($cancelledOrders) ?></span>
                                                <span class="text-muted">Cancelled</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $pendingPercent ?>%" aria-valuenow="<?= $pendingPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $completedPercent ?>%" aria-valuenow="<?= $completedPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $cancelledPercent ?>%" aria-valuenow="<?= $cancelledPercent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Include Footer Scripts -->
    <?php include 'includes/footer-scripts.php'; ?>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <!-- Date Range Picker -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <!-- Analytics Dashboard JavaScript -->
    <script src="../../assets/js/analytics-dashboard.js"></script>

    <!-- Export Functions -->
    <script>
        // Add event listeners when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Export to CSV
            document.getElementById('exportCSV').addEventListener('click', function(e) {
                e.preventDefault();
                exportTableToCSV('brew-and-bake-analytics-report.csv');
            });

            // Export to PDF
            document.getElementById('exportPDF').addEventListener('click', function(e) {
                e.preventDefault();
                exportToPDF();
            });

            // Print report
            document.getElementById('printReport').addEventListener('click', function(e) {
                e.preventDefault();
                window.print();
            });
        });

        /**
         * Export analytics data to CSV
         */
        function exportTableToCSV(filename) {
            const rows = [];

            // Add title
            rows.push(['Brew & Bake - Analytics Report']);
            rows.push(['Generated on:', new Date().toLocaleString()]);
            rows.push(['Period:', '<?= htmlspecialchars($dateRange) ?>']);
            rows.push([]);

            // Add KPIs
            rows.push(['Key Performance Indicators']);
            rows.push(['Total Orders:', '<?= $totalOrders ?>']);
            rows.push(['Total Revenue:', '₱<?= number_format($totalRevenue, 2) ?>']);
            rows.push(['Average Order Value:', '₱<?= number_format($averageOrderValue, 2) ?>']);
            rows.push(['Total Customers:', '<?= $totalCustomers ?>']);
            rows.push(['New Customers:', '<?= $newCustomers ?>']);
            rows.push(['Returning Customers:', '<?= $returningCustomers ?>']);
            rows.push(['Customer Retention Rate:', '<?= number_format($customerRetention, 1) ?>%']);
            rows.push([]);

            // Add order status
            rows.push(['Order Status']);
            rows.push(['Pending:', '<?= $pendingOrders ?>']);
            rows.push(['Completed:', '<?= $completedOrders ?>']);
            rows.push(['Cancelled:', '<?= $cancelledOrders ?>']);
            rows.push([]);

            // Add top products
            rows.push(['Top Products']);
            rows.push(['Product', 'Category', 'Quantity Sold', 'Revenue']);
            <?php foreach ($topProducts as $product): ?>
            rows.push([
                '<?= addslashes($product['name']) ?>',
                '<?= addslashes($product['category_name'] ?? 'Uncategorized') ?>',
                '<?= $product['total_quantity'] ?>',
                '₱<?= number_format($product['total_revenue'], 2) ?>'
            ]);
            <?php endforeach; ?>
            rows.push([]);

            // Add sales by category
            rows.push(['Sales by Category']);
            rows.push(['Category', 'Revenue']);
            <?php foreach ($salesByCategory as $category): ?>
            rows.push([
                '<?= addslashes($category['category_name'] ?? 'Uncategorized') ?>',
                '₱<?= number_format($category['total_revenue'], 2) ?>'
            ]);
            <?php endforeach; ?>
            rows.push([]);

            // Add payment methods
            rows.push(['Payment Methods']);
            rows.push(['Method', 'Orders', 'Amount']);
            <?php foreach ($salesByPaymentMethod as $payment): ?>
            rows.push([
                '<?php
                    $method = $payment['payment_method'];
                    if ($method === 'credit_card') echo 'Credit Card';
                    elseif ($method === 'gcash') echo 'GCash';
                    elseif ($method === 'bank_transfer') echo 'Bank Transfer';
                    else echo 'Cash';
                ?>',
                '<?= $payment['count'] ?>',
                '₱<?= number_format($payment['total_amount'], 2) ?>'
            ]);
            <?php endforeach; ?>

            // Convert to CSV
            let csvContent = "data:text/csv;charset=utf-8,";
            rows.forEach(function(rowArray) {
                const row = rowArray.join(',');
                csvContent += row + '\r\n';
            });

            // Download CSV
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        /**
         * Export analytics data to PDF
         */
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Add title
            doc.setFontSize(18);
            doc.setTextColor(17, 24, 39); // #111827
            doc.text('Brew & Bake - Analytics Report', 14, 20);

            // Add period
            doc.setFontSize(10);
            doc.setTextColor(107, 114, 128); // #6b7280
            doc.text(`Generated on: ${new Date().toLocaleString()}`, 14, 30);
            doc.text(`Period: <?= htmlspecialchars($dateRange) ?>`, 14, 35);

            // Add KPIs
            doc.setFontSize(14);
            doc.setTextColor(17, 24, 39);
            doc.text('Key Performance Indicators', 14, 45);

            doc.setFontSize(10);
            doc.setTextColor(55, 65, 81);
            doc.text(`Total Orders: <?= $totalOrders ?>`, 14, 55);
            doc.text(`Total Revenue: ₱<?= number_format($totalRevenue, 2) ?>`, 14, 60);
            doc.text(`Average Order Value: ₱<?= number_format($averageOrderValue, 2) ?>`, 14, 65);
            doc.text(`Total Customers: <?= $totalCustomers ?>`, 14, 70);
            doc.text(`Customer Retention Rate: <?= number_format($customerRetention, 1) ?>%`, 14, 75);

            // Add top products table
            doc.setFontSize(14);
            doc.setTextColor(17, 24, 39);
            doc.text('Top Products', 14, 90);

            const topProductsData = [
                ['Product', 'Category', 'Quantity', 'Revenue']
            ];

            <?php foreach (array_slice($topProducts, 0, 5) as $product): ?>
            topProductsData.push([
                '<?= addslashes($product['name']) ?>',
                '<?= addslashes($product['category_name'] ?? 'Uncategorized') ?>',
                '<?= $product['total_quantity'] ?>',
                '₱<?= number_format($product['total_revenue'], 2) ?>'
            ]);
            <?php endforeach; ?>

            doc.autoTable({
                startY: 95,
                head: [topProductsData[0]],
                body: topProductsData.slice(1),
                theme: 'grid',
                headStyles: {
                    fillColor: [17, 24, 39],
                    textColor: [255, 255, 255]
                }
            });

            // Add sales by category table
            const finalY = doc.lastAutoTable.finalY + 15;
            doc.setFontSize(14);
            doc.setTextColor(17, 24, 39);
            doc.text('Sales by Category', 14, finalY);

            const categoryData = [
                ['Category', 'Revenue']
            ];

            <?php foreach ($salesByCategory as $category): ?>
            categoryData.push([
                '<?= addslashes($category['category_name'] ?? 'Uncategorized') ?>',
                '₱<?= number_format($category['total_revenue'], 2) ?>'
            ]);
            <?php endforeach; ?>

            doc.autoTable({
                startY: finalY + 5,
                head: [categoryData[0]],
                body: categoryData.slice(1),
                theme: 'grid',
                headStyles: {
                    fillColor: [17, 24, 39],
                    textColor: [255, 255, 255]
                }
            });

            // Save PDF
            doc.save('brew-and-bake-analytics-report.pdf');
        }
    </script>
</body>
</html>