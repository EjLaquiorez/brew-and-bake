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

// Monthly sales data for 2025 with realistic seasonal patterns
$monthlySales = [
    // Q1: Post-holiday slowdown, then gradual recovery
    ['month' => 'Jan 2025', 'sales' => 18500],  // Post-holiday slowdown
    ['month' => 'Feb 2025', 'sales' => 17200],  // Valentine's Day bump but still winter slump
    ['month' => 'Mar 2025', 'sales' => 19800],  // Beginning of spring recovery

    // Q2: Strong spring and early summer growth
    ['month' => 'Apr 2025', 'sales' => 22500],  // Spring break boost
    ['month' => 'May 2025', 'sales' => 24300],  // Graduation season
    ['month' => 'Jun 2025', 'sales' => 27800],  // Summer vacation starts

    // Q3: Peak summer season then back-to-school transition
    ['month' => 'Jul 2025', 'sales' => 31500],  // Summer peak
    ['month' => 'Aug 2025', 'sales' => 29700],  // Late summer
    ['month' => 'Sep 2025', 'sales' => 26400],  // Back to school/work

    // Q4: Holiday season build-up and peak
    ['month' => 'Oct 2025', 'sales' => 28900],  // Fall season, Halloween
    ['month' => 'Nov 2025', 'sales' => 32600],  // Pre-holiday shopping
    ['month' => 'Dec 2025', 'sales' => 38500]   // Holiday peak season
];

// Top selling products from Brew & Bake complete menu
$topProducts = [
    ['name' => 'Caramel Macchiato', 'category' => 'Coffee', 'sales' => 450, 'revenue' => 90000],
    ['name' => 'Tiramisu', 'category' => 'Cake', 'sales' => 320, 'revenue' => 96000],
    ['name' => 'Ube Cheese Pandesal', 'category' => 'Pastry', 'sales' => 580, 'revenue' => 34800],
    ['name' => 'Matcha Latte', 'category' => 'Drink', 'sales' => 410, 'revenue' => 90200],
    ['name' => 'Chocolate Lava Cake', 'category' => 'Cake', 'sales' => 280, 'revenue' => 89600]
];

// Sales by category
$salesByCategory = [
    ['category' => 'Coffee', 'sales' => 1200, 'revenue' => 204000],
    ['category' => 'Drink', 'sales' => 850, 'revenue' => 153000],
    ['category' => 'Cake', 'sales' => 950, 'revenue' => 285000],
    ['category' => 'Pastry', 'sales' => 1050, 'revenue' => 126000],
    ['category' => 'Dessert', 'sales' => 750, 'revenue' => 82500]
];

// Customer demographics
$customerDemographics = [
    ['age_group' => '18-24', 'percentage' => 15],
    ['age_group' => '25-34', 'percentage' => 35],
    ['age_group' => '35-44', 'percentage' => 25],
    ['age_group' => '45-54', 'percentage' => 15],
    ['age_group' => '55+', 'percentage' => 10]
];

// Calculate totals
$totalSales = array_sum(array_column($salesByCategory, 'sales'));
$totalRevenue = array_sum(array_column($salesByCategory, 'revenue'));
$averageOrderValue = $totalSales > 0 ? $totalRevenue / $totalSales : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Analytics - Brew & Bake</title>
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
                        <a href="analytics.php" class="nav-link active">
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

            <!-- Analytics Overview -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="card card-primary fade-in">
                        <div class="card-body p-5">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-2">Analytics Overview</h2>
                                    <p class="text-muted mb-0">Insights and performance metrics for your store</p>
                                </div>
                                <div class="d-flex gap-3">
                                    <select class="form-select">
                                        <option>Last 30 days</option>
                                        <option>Last 90 days</option>
                                        <option>Last 6 months</option>
                                        <option>Last year</option>
                                        <option>All time</option>
                                    </select>
                                    <button class="btn btn-outline-primary">
                                        <i class="bi bi-download me-2"></i> Export
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <h3 class="mb-4">Key Metrics</h3>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card primary fade-in delay-100">
                        <div class="stat-icon">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value">₱<?= number_format($totalRevenue, 2) ?></h3>
                            <p class="stat-label">Total Revenue</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card success fade-in delay-200">
                        <div class="stat-icon">
                            <i class="bi bi-bag"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($totalSales) ?></h3>
                            <p class="stat-label">Total Orders</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card info fade-in delay-300">
                        <div class="stat-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value">₱<?= number_format($averageOrderValue, 2) ?></h3>
                            <p class="stat-label">Avg. Order Value</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card secondary fade-in delay-400">
                        <div class="stat-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value">850</h3>
                            <p class="stat-label">Total Customers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Trends -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-graph-up"></i> Monthly Sales Trends</h5>
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
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Products & Categories -->
            <div class="row mb-5">
                <!-- Top Products -->
                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-left">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-star"></i> Top Selling Products</h5>
                            <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Category</th>
                                            <th>Sales</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topProducts as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-medium"><?= htmlspecialchars($product['name']) ?></div>
                                                </td>
                                                <td>
                                                    <span class="cell-badge primary">
                                                        <?= htmlspecialchars($product['category']) ?>
                                                    </span>
                                                </td>
                                                <td><?= number_format($product['sales']) ?></td>
                                                <td>₱<?= number_format($product['revenue']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales by Category -->
                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-right">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-pie-chart"></i> Sales by Category</h5>
                            <a href="categories.php" class="btn btn-sm btn-outline-primary">View Categories</a>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Demographics & Insights -->
            <div class="row mb-5">
                <!-- Customer Demographics -->
                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-left">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-people"></i> Customer Demographics</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="demographicsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Insights -->
                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-right">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-lightbulb"></i> Key Insights</h5>
                        </div>
                        <div class="card-body">
                            <div class="insights-list">
                                <div class="insight-item">
                                    <div class="insight-icon success">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h6 class="insight-title">Sales Growth</h6>
                                        <p class="insight-text">December sales increased by 18% compared to November, reflecting strong holiday season performance.</p>
                                    </div>
                                </div>

                                <div class="insight-item">
                                    <div class="insight-icon primary">
                                        <i class="bi bi-star"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h6 class="insight-title">Top Performer</h6>
                                        <p class="insight-text">Ube Cheese Pandesal is your best-selling product by volume this month.</p>
                                    </div>
                                </div>

                                <div class="insight-item">
                                    <div class="insight-icon warning">
                                        <i class="bi bi-clock"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h6 class="insight-title">Peak Hours</h6>
                                        <p class="insight-text">Most orders are placed between 7-9 AM and 12-2 PM.</p>
                                    </div>
                                </div>

                                <div class="insight-item">
                                    <div class="insight-icon secondary">
                                        <i class="bi bi-calendar-check"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h6 class="insight-title">Seasonal Trends</h6>
                                        <p class="insight-text">Summer (July) and holiday season (December) show the highest sales volumes of the year.</p>
                                    </div>
                                </div>

                                <div class="insight-item">
                                    <div class="insight-icon info">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="insight-content">
                                        <h6 class="insight-title">Customer Retention</h6>
                                        <p class="insight-text">65% of customers are returning customers.</p>
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

<?php include 'includes/footer-scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlySales, 'month')) ?>,
                datasets: [{
                    label: 'Monthly Sales (₱)',
                    data: <?= json_encode(array_column($monthlySales, 'sales')) ?>,
                    backgroundColor: 'rgba(126, 87, 194, 0.1)',
                    borderColor: 'rgba(126, 87, 194, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgba(126, 87, 194, 1)',
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
                        mode: 'index',
                        intersect: false,
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

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($salesByCategory, 'category')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($salesByCategory, 'revenue')) ?>,
                    backgroundColor: [
                        'rgba(126, 87, 194, 0.8)',
                        'rgba(38, 166, 154, 0.8)',
                        'rgba(239, 83, 80, 0.8)',
                        'rgba(255, 167, 38, 0.8)',
                        'rgba(41, 182, 246, 0.8)'
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
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 15
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

        // Demographics Chart
        const demographicsCtx = document.getElementById('demographicsChart').getContext('2d');
        const demographicsChart = new Chart(demographicsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($customerDemographics, 'age_group')) ?>,
                datasets: [{
                    label: 'Percentage of Customers',
                    data: <?= json_encode(array_column($customerDemographics, 'percentage')) ?>,
                    backgroundColor: 'rgba(126, 87, 194, 0.7)',
                    borderColor: 'rgba(126, 87, 194, 1)',
                    borderWidth: 1,
                    borderRadius: 4
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
                                return context.raw + '%';
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
                                return value + '%';
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