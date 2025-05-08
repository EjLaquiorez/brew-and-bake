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

// Fetch statistics (dummy data for now, adapt as needed)
try {
    $totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $totalRevenue = $conn->query("SELECT IFNULL(SUM(total),0) FROM orders WHERE status = 'completed'")->fetchColumn();
    $totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $bestSellers = $conn->query("SELECT name, category, price, stock FROM products ORDER BY stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $totalOrders = $totalRevenue = $totalProducts = 0;
    $bestSellers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/orders.css">
    <link rel="stylesheet" href="../assets/css/reports.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="admin.php">
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
          <a class="nav-link" href="admin.php">
            <i class="bi bi-box-seam"></i> Products
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="reports.php">
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

<div class="container-fluid py-4">
    <div class="charts-flex-wrap">
        <!-- Animated Sales Overview Chart -->
        <div class="card mb-4 chart-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Sales Overview</h5>
            </div>
            <div class="card-body">
                <div class="chart-responsive"><canvas id="salesChart"></canvas></div>
            </div>
        </div>
        <!-- Best Sellers Bar Chart -->
        <div class="card mb-4 chart-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Best Sellers</h5>
            </div>
            <div class="card-body">
                <div class="chart-responsive"><canvas id="bestSellersChart"></canvas></div>
            </div>
        </div>
        <!-- Coffee to Pastries Combo Chart -->
        <div class="card mb-4 chart-card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Coffee & Pastry Combos</h5>
            </div>
            <div class="card-body">
                <div class="chart-responsive"><canvas id="comboChart"></canvas></div>
            </div>
        </div>
        <!-- Combo Types Chart -->
        <div class="card mb-4 chart-card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Popular Coffee & Pastry Combos</h5>
            </div>
            <div class="card-body">
                <div class="chart-responsive"><canvas id="comboTypesChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">Total Orders</h6>
                        <h2 class="mb-0"><?= number_format($totalOrders) ?></h2>
                    </div>
                    <i class="bi bi-receipt fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">Total Revenue</h6>
                        <h2 class="mb-0">₱<?= number_format($totalRevenue, 2) ?></h2>
                    </div>
                    <i class="bi bi-currency-dollar fs-1"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card bg-warning text-dark">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-1">Total Products</h6>
                        <h2 class="mb-0"><?= number_format($totalProducts) ?></h2>
                    </div>
                    <i class="bi bi-box-seam fs-1"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-star"></i> Best Sellers (Low Stock)</h5>
        </div>
        <div class="card-body">
            <?php if (count($bestSellers) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bestSellers as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category']) ?></td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $product['stock'] < 10 ? 'warning' : 'success' ?>">
                                            <?= $product['stock'] ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> No best sellers found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sales Overview Chart (already present)
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Sales (₱)',
            data: [12000, 15000, 11000, 18000, 22000, 20000, 25000, 23000, 21000, 24000, 26000, 28000],
            fill: true,
            backgroundColor: 'rgba(111, 78, 55, 0.08)',
            borderColor: 'rgba(111, 78, 55, 1)',
            tension: 0.4,
            pointBackgroundColor: 'rgba(111, 78, 55, 1)',
            pointRadius: 5,
            pointHoverRadius: 7,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                labels: {
                    color: '#6F4E37',
                    font: { weight: 'bold' }
                }
            },
            tooltip: {
                enabled: true,
                callbacks: {
                    label: function(context) {
                        return '₱' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#6F4E37', font: { weight: 'bold' } }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(111, 78, 55, 0.08)' },
                ticks: { color: '#6F4E37', font: { weight: 'bold' } }
            }
        },
        animation: {
            duration: 1200,
            easing: 'easeOutQuart'
        }
    }
});

// Best Sellers Bar Chart (sample data)
const bestSellersCtx = document.getElementById('bestSellersChart').getContext('2d');
const bestSellersChart = new Chart(bestSellersCtx, {
    type: 'bar',
    data: {
        labels: ['Cappuccino', 'Latte', 'Espresso', 'Croissant', 'Muffin'],
        datasets: [{
            label: 'Units Sold',
            data: [120, 110, 90, 80, 75],
            backgroundColor: [
                'rgba(111, 78, 55, 0.8)',
                'rgba(210, 180, 140, 0.8)',
                'rgba(245, 222, 179, 0.8)',
                'rgba(111, 78, 55, 0.6)',
                'rgba(210, 180, 140, 0.6)'
            ],
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' units';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#6F4E37', font: { weight: 'bold' } }
            },
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(111, 78, 55, 0.08)' },
                ticks: { color: '#6F4E37', font: { weight: 'bold' } }
            }
        },
        animation: {
            duration: 1200,
            easing: 'easeOutQuart'
        }
    }
});

// Coffee & Pastry Combo Chart (sample data)
const comboCtx = document.getElementById('comboChart').getContext('2d');
const comboChart = new Chart(comboCtx, {
    type: 'doughnut',
    data: {
        labels: ['Coffee Only', 'Pastry Only', 'Coffee + Pastry Combo'],
        datasets: [{
            label: 'Order Combos',
            data: [60, 30, 55],
            backgroundColor: [
                'rgba(111, 78, 55, 0.8)',
                'rgba(210, 180, 140, 0.8)',
                'rgba(245, 222, 179, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'bottom',
                labels: {
                    color: '#6F4E37',
                    font: { weight: 'bold' }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.parsed + ' orders';
                    }
                }
            }
        },
        animation: {
            duration: 1200,
            easing: 'easeOutQuart'
        }
    }
});

// Combo Types Horizontal Bar Chart (sample data)
const comboTypesCtx = document.getElementById('comboTypesChart').getContext('2d');
const comboTypesChart = new Chart(comboTypesCtx, {
    type: 'bar',
    data: {
        labels: [
            'Cappuccino + Croissant',
            'Latte + Muffin',
            'Espresso + Danish',
            'Americano + Scone',
            'Mocha + Brownie',
            'Cappuccino + Muffin',
            'Latte + Croissant'
        ],
        datasets: [{
            label: 'Combo Orders',
            data: [32, 28, 22, 18, 15, 12, 10],
            backgroundColor: [
                'rgba(111, 78, 55, 0.8)',
                'rgba(210, 180, 140, 0.8)',
                'rgba(245, 222, 179, 0.8)',
                'rgba(111, 78, 55, 0.6)',
                'rgba(210, 180, 140, 0.6)',
                'rgba(245, 222, 179, 0.6)',
                'rgba(111, 78, 55, 0.4)'
            ],
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.parsed.x + ' orders';
                    }
                }
            }
        },
        scales: {
            x: {
                beginAtZero: true,
                grid: { color: 'rgba(111, 78, 55, 0.08)' },
                ticks: { color: '#6F4E37', font: { weight: 'bold' } }
            },
            y: {
                grid: { display: false },
                ticks: { color: '#6F4E37', font: { weight: 'bold' } }
            }
        },
        animation: {
            duration: 1200,
            easing: 'easeOutQuart'
        }
    }
});
</script>
</body>
</html> 