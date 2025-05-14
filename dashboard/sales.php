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

// Placeholder data for demonstration
// In a real application, this would come from database queries

// Sales data by date range
$dailySales = [
    ['date' => '2023-06-01', 'sales' => 1250, 'orders' => 25],
    ['date' => '2023-06-02', 'sales' => 1450, 'orders' => 29],
    ['date' => '2023-06-03', 'sales' => 1800, 'orders' => 36],
    ['date' => '2023-06-04', 'sales' => 1650, 'orders' => 33],
    ['date' => '2023-06-05', 'sales' => 2100, 'orders' => 42],
    ['date' => '2023-06-06', 'sales' => 2250, 'orders' => 45],
    ['date' => '2023-06-07', 'sales' => 2500, 'orders' => 50],
    ['date' => '2023-06-08', 'sales' => 2300, 'orders' => 46],
    ['date' => '2023-06-09', 'sales' => 2450, 'orders' => 49],
    ['date' => '2023-06-10', 'sales' => 2800, 'orders' => 56],
    ['date' => '2023-06-11', 'sales' => 3200, 'orders' => 64],
    ['date' => '2023-06-12', 'sales' => 3500, 'orders' => 70],
    ['date' => '2023-06-13', 'sales' => 3300, 'orders' => 66],
    ['date' => '2023-06-14', 'sales' => 3100, 'orders' => 62]
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

// Recent transactions
$recentTransactions = [
    ['id' => 'TRX-1001', 'customer' => 'John Doe', 'date' => '2023-06-14 14:30:45', 'amount' => 850, 'status' => 'completed', 'payment' => 'Credit Card'],
    ['id' => 'TRX-1002', 'customer' => 'Jane Smith', 'date' => '2023-06-14 13:15:22', 'amount' => 1250, 'status' => 'completed', 'payment' => 'Cash'],
    ['id' => 'TRX-1003', 'customer' => 'Robert Johnson', 'date' => '2023-06-14 12:45:10', 'amount' => 450, 'status' => 'completed', 'payment' => 'Digital Wallet'],
    ['id' => 'TRX-1004', 'customer' => 'Emily Wilson', 'date' => '2023-06-14 11:20:35', 'amount' => 975, 'status' => 'completed', 'payment' => 'Credit Card'],
    ['id' => 'TRX-1005', 'customer' => 'Michael Brown', 'date' => '2023-06-14 10:55:18', 'amount' => 325, 'status' => 'completed', 'payment' => 'Cash']
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
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
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

            <!-- Sales Overview -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="card card-primary fade-in">
                        <div class="card-body p-5">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-2">Sales Overview</h2>
                                    <p class="text-muted mb-0">Detailed sales data and transaction history</p>
                                </div>
                                <div class="d-flex gap-3">
                                    <select class="form-select">
                                        <option>Last 7 days</option>
                                        <option>Last 30 days</option>
                                        <option>Last 90 days</option>
                                        <option>Custom range</option>
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
                            <h3 class="stat-value">₱<?= number_format($totalSales, 2) ?></h3>
                            <p class="stat-label">Total Sales</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="stat-card success fade-in delay-200">
                        <div class="stat-icon">
                            <i class="bi bi-bag"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($totalOrders) ?></h3>
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
                            <i class="bi bi-credit-card"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-value"><?= number_format($totalTransactions) ?></h3>
                            <p class="stat-label">Transactions</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Trends -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="card fade-in">
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
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Methods & Time of Day -->
            <div class="row mb-5">
                <!-- Payment Methods -->
                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-left">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-credit-card"></i> Payment Methods</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="paymentChart"></canvas>
                            </div>
                            <div class="table-responsive mt-4">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Payment Method</th>
                                            <th>Transactions</th>
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
                                                <td><?= number_format($method['count']) ?></td>
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

                <!-- Sales by Time of Day -->
                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-right">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-clock"></i> Sales by Time of Day</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="height: 250px;">
                                <canvas id="timeChart"></canvas>
                            </div>
                            <div class="mt-4">
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
            </div>

            <!-- Recent Transactions -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-receipt"></i> Recent Transactions</h5>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All Orders</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
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
                                                        <div class="fw-medium"><?= date('M d, Y', strtotime($transaction['date'])) ?></div>
                                                        <div class="text-muted small"><?= date('h:i A', strtotime($transaction['date'])) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">₱<?= number_format($transaction['amount'], 2) ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($transaction['payment']) ?></td>
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
                        position: 'top',
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