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

// Placeholder data for demonstration
// In a real application, this would come from database queries

// Sales data by date range (2025 timeline)
$dailySales = [
    ['date' => '2025-05-01', 'sales' => 1250, 'orders' => 25],
    ['date' => '2025-05-02', 'sales' => 1450, 'orders' => 29],
    ['date' => '2025-05-03', 'sales' => 1800, 'orders' => 36],
    ['date' => '2025-05-04', 'sales' => 1650, 'orders' => 33],
    ['date' => '2025-05-05', 'sales' => 2100, 'orders' => 42],
    ['date' => '2025-05-06', 'sales' => 2250, 'orders' => 45],
    ['date' => '2025-05-07', 'sales' => 2500, 'orders' => 50],
    ['date' => '2025-05-08', 'sales' => 2300, 'orders' => 46],
    ['date' => '2025-05-09', 'sales' => 2450, 'orders' => 49],
    ['date' => '2025-05-10', 'sales' => 2800, 'orders' => 56],
    ['date' => '2025-05-11', 'sales' => 3200, 'orders' => 64],
    ['date' => '2025-05-12', 'sales' => 3500, 'orders' => 70],
    ['date' => '2025-05-13', 'sales' => 3300, 'orders' => 66],
    ['date' => '2025-05-14', 'sales' => 3100, 'orders' => 62]
];

// Payment methods
$paymentMethods = [
    ['method' => 'Credit Card', 'count' => 450, 'amount' => 225000],
    ['method' => 'Cash', 'count' => 320, 'amount' => 160000],
    ['method' => 'Digital Wallet', 'count' => 180, 'amount' => 90000],
    ['method' => 'Bank Transfer', 'count' => 50, 'amount' => 25000]
];

// Sales by time of day
$salesByTime = [
    ['time' => '6-8 AM', 'sales' => 15000],
    ['time' => '8-10 AM', 'sales' => 25000],
    ['time' => '10-12 PM', 'sales' => 18000],
    ['time' => '12-2 PM', 'sales' => 30000],
    ['time' => '2-4 PM', 'sales' => 22000],
    ['time' => '4-6 PM', 'sales' => 28000],
    ['time' => '6-8 PM', 'sales' => 32000],
    ['time' => '8-10 PM', 'sales' => 20000]
];

// Recent transactions with Filipino names and 2025 dates
$recentTransactions = [
    ['id' => 'TRX-1001', 'customer' => 'Juan Dela Cruz', 'date' => '2025-05-14 14:30:45', 'amount' => 850, 'status' => 'completed', 'payment' => 'Credit Card'],
    ['id' => 'TRX-1002', 'customer' => 'Maria Santos', 'date' => '2025-05-14 13:15:22', 'amount' => 1250, 'status' => 'completed', 'payment' => 'Cash'],
    ['id' => 'TRX-1003', 'customer' => 'Carlo Reyes', 'date' => '2025-05-14 12:45:10', 'amount' => 450, 'status' => 'completed', 'payment' => 'Digital Wallet'],
    ['id' => 'TRX-1004', 'customer' => 'Jasmine Mendoza', 'date' => '2025-05-14 11:20:35', 'amount' => 975, 'status' => 'completed', 'payment' => 'Credit Card'],
    ['id' => 'TRX-1005', 'customer' => 'Miguel Bautista', 'date' => '2025-05-14 10:55:18', 'amount' => 325, 'status' => 'completed', 'payment' => 'Cash']
];

// Calculate totals
$totalSales = array_sum(array_column($dailySales, 'sales'));
$totalOrders = array_sum(array_column($dailySales, 'orders'));
$averageOrderValue = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
$totalTransactions = count($recentTransactions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Sales - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a href="sales.php" class="nav-link active">
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

            <!-- Include Welcome Card -->
            <?php include 'includes/welcome-card.php'; ?>

            <!-- Grid Layout -->
            <!-- First Row: Filter and Key Metrics -->
            <div class="row mb-4">
                <!-- Filter Data -->
                <div class="col-md-4 mb-4">
                    <div class="card fade-in h-100">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-funnel"></i> Filter Data</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="dateRange" class="form-label">Date Range</label>
                                <select class="form-select" id="dateRange">
                                    <option>Last 7 days</option>
                                    <option>Last 30 days</option>
                                    <option>Last 90 days</option>
                                    <option>Custom range</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i> Apply Filters
                                </button>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-download me-2"></i> Export
                                </button>
                                <button class="btn btn-outline-primary">
                                    <i class="bi bi-printer me-2"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="col-md-8 mb-4">
                    <div class="card fade-in h-100">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-bar-chart"></i> Key Metrics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6 col-sm-6">
                                    <div class="stat-card primary">
                                        <div class="stat-icon">
                                            <i class="bi bi-currency-dollar"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value">₱<?= number_format($totalSales, 2) ?></h3>
                                            <p class="stat-label">Total Sales</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-6">
                                    <div class="stat-card success">
                                        <div class="stat-icon">
                                            <i class="bi bi-bag"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value"><?= number_format($totalOrders) ?></h3>
                                            <p class="stat-label">Total Orders</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-6">
                                    <div class="stat-card info">
                                        <div class="stat-icon">
                                            <i class="bi bi-cash-stack"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value">₱<?= number_format($averageOrderValue, 2) ?></h3>
                                            <p class="stat-label">Avg. Order Value</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-6">
                                    <div class="stat-card secondary">
                                        <div class="stat-icon">
                                            <i class="bi bi-credit-card"></i>
                                        </div>
                                        <div class="stat-content">
                                            <h3 class="stat-value"><?= number_format($totalTransactions) ?></h3>
                                            <p class="stat-label">Transactions</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row: Sales Trends and Payment Methods -->
            <div class="row mb-4">
                <!-- Sales Trends -->
                <div class="col-md-8 mb-4">
                    <div class="card fade-in h-100">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-graph-up"></i> Daily Sales Trends</h5>
                            <div class="card-actions">
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 300px; min-height: 250px;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="col-md-4 mb-4">
                    <div class="card fade-in h-100">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-credit-card"></i> Payment Methods</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 200px; min-height: 180px;">
                                <canvas id="paymentChart"></canvas>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Method</th>
                                            <th>Amount</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalAmount = array_sum(array_column($paymentMethods, 'amount'));
                                        foreach ($paymentMethods as $method):
                                            $percentage = $totalAmount > 0 ? round(($method['amount'] / $totalAmount) * 100) : 0;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($method['method']) ?></td>
                                                <td>₱<?= number_format($method['amount']) ?></td>
                                                <td><?= $percentage ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Third Row: Time of Day and Recent Transactions -->
            <div class="row mb-4">
                <!-- Sales by Time of Day -->
                <div class="col-md-4 mb-4">
                    <div class="card fade-in h-100">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-clock"></i> Sales by Time</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 200px; min-height: 180px;">
                                <canvas id="timeChart"></canvas>
                            </div>
                            <div class="mt-3">
                                <h6 class="text-muted mb-2">Peak Hours</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php
                                    $salesByTimeArray = array_column($salesByTime, 'sales');
                                    $maxSales = max($salesByTimeArray);
                                    $peakHours = array_filter($salesByTime, function($item) use ($maxSales) {
                                        return $item['sales'] >= $maxSales * 0.8; // 80% of max sales
                                    });

                                    foreach ($peakHours as $peak):
                                    ?>
                                        <span class="badge-primary"><?= $peak['time'] ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="col-md-8 mb-4">
                    <div class="card fade-in h-100">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-receipt"></i> Recent Transactions</h5>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTransactions as $transaction): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium"><?= htmlspecialchars($transaction['id']) ?></div>
                                                </td>
                                                <td><?= htmlspecialchars($transaction['customer']) ?></td>
                                                <td>
                                                    <div>
                                                        <div class="fw-medium"><?= date('M d', strtotime($transaction['date'])) ?></div>
                                                        <div class="text-muted small"><?= date('h:i A', strtotime($transaction['date'])) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">₱<?= number_format($transaction['amount'], 2) ?></span>
                                                </td>
                                                <td>
                                                    <span class="cell-badge success">
                                                        <?= ucfirst($transaction['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fourth Row: Sales Breakdown and Forecast -->
            <div class="row mb-4">
                <!-- Sales Breakdown -->
                <div class="col-md-6 mb-4">
                    <div class="card fade-in h-100">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-pie-chart"></i> Sales Breakdown</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">By Category</h6>
                                                <i class="bi bi-tags text-primary"></i>
                                            </div>
                                            <div class="progress-list">
                                                <div class="progress-item mb-2">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>Coffee</span>
                                                        <span>45%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-primary" style="width: 45%"></div>
                                                    </div>
                                                </div>
                                                <div class="progress-item mb-2">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>Pastries</span>
                                                        <span>30%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-success" style="width: 30%"></div>
                                                    </div>
                                                </div>
                                                <div class="progress-item">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>Other</span>
                                                        <span>25%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-info" style="width: 25%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">By Location</h6>
                                                <i class="bi bi-geo-alt text-primary"></i>
                                            </div>
                                            <div class="progress-list">
                                                <div class="progress-item mb-2">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>Manila</span>
                                                        <span>55%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-primary" style="width: 55%"></div>
                                                    </div>
                                                </div>
                                                <div class="progress-item mb-2">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>Quezon City</span>
                                                        <span>25%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-success" style="width: 25%"></div>
                                                    </div>
                                                </div>
                                                <div class="progress-item">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span>Other</span>
                                                        <span>20%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <div class="progress-bar bg-info" style="width: 20%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Forecast -->
                <div class="col-md-6 mb-4">
                    <div class="card fade-in h-100">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-calendar-check"></i> Sales Forecast</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <div class="d-flex">
                                    <div class="me-3">
                                        <i class="bi bi-info-circle fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="alert-heading">Projected Growth</h6>
                                        <p class="mb-0">Based on current trends, sales are projected to increase by 15% next month.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="stat-icon-sm primary">
                                                        <i class="bi bi-graph-up-arrow"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-muted mb-0">Next Month</p>
                                                    <h5 class="mb-0">₱<?= number_format($totalSales * 1.15, 2) ?></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light border-0">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="stat-icon-sm success">
                                                        <i class="bi bi-bag-plus"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-muted mb-0">Projected Orders</p>
                                                    <h5 class="mb-0"><?= number_format($totalOrders * 1.2) ?></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button class="btn btn-primary">
                                    <i class="bi bi-file-earmark-text me-2"></i> Generate Report
                                </button>
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

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(function($item) {
                    return date('M d', strtotime($item['date']));
                }, $dailySales)) ?>,
                datasets: [{
                    label: 'Sales (₱)',
                    data: <?= json_encode(array_column($dailySales, 'sales')) ?>,
                    backgroundColor: 'rgba(126, 87, 194, 0.7)',
                    borderColor: 'rgba(126, 87, 194, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }, {
                    label: 'Orders',
                    data: <?= json_encode(array_column($dailySales, 'orders')) ?>,
                    backgroundColor: 'rgba(38, 166, 154, 0.7)',
                    borderColor: 'rgba(38, 166, 154, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth < 768 ? 'bottom' : 'top',
                        labels: {
                            boxWidth: window.innerWidth < 768 ? 12 : 15,
                            padding: window.innerWidth < 768 ? 10 : 15
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                if (context.dataset.label === 'Sales (₱)') {
                                    return 'Sales: ₱' + context.raw.toLocaleString();
                                } else {
                                    return 'Orders: ' + context.raw;
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            display: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Payment Methods Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($paymentMethods, 'method')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($paymentMethods, 'amount')) ?>,
                    backgroundColor: [
                        'rgba(126, 87, 194, 0.8)',
                        'rgba(38, 166, 154, 0.8)',
                        'rgba(239, 83, 80, 0.8)',
                        'rgba(255, 167, 38, 0.8)'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth < 768 ? 'bottom' : 'right',
                        labels: {
                            boxWidth: window.innerWidth < 768 ? 12 : 15,
                            padding: window.innerWidth < 768 ? 10 : 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // Time of Day Chart
        const timeCtx = document.getElementById('timeChart').getContext('2d');
        const timeChart = new Chart(timeCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($salesByTime, 'time')) ?>,
                datasets: [{
                    label: 'Sales by Time of Day',
                    data: <?= json_encode(array_column($salesByTime, 'sales')) ?>,
                    backgroundColor: 'rgba(38, 166, 154, 0.1)',
                    borderColor: 'rgba(38, 166, 154, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(38, 166, 154, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
</body>
</html>