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

$message = '';
$alertType = '';

if (isset($_POST['update_menu'])) {
    try {
        // Read the SQL file
        $sqlFile = file_get_contents("../includes/update_full_menu.sql");

        // Begin transaction for safety
        $conn->beginTransaction();

        // Split SQL file into individual statements
        $sqlStatements = explode(';', $sqlFile);

        // Execute each statement
        $successCount = 0;
        foreach ($sqlStatements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && strpos($statement, '/*') === false && strpos($statement, '--') !== 0) {
                $conn->exec($statement);
                $successCount++;
            }
        }

        // Commit the transaction
        $conn->commit();

        $message = "SQL script executed successfully! $successCount statements processed.";
        $alertType = 'success';

    } catch (PDOException $e) {
        // If a transaction is active, roll it back
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = "Error executing SQL: " . $e->getMessage();
        $alertType = 'danger';
    }
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
    <title>Update Full Menu - Brew & Bake</title>
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
            <?php include 'includes/sidebar-user-menu.php'; ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Include Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Content Area -->
        <div class="admin-content">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="page-title">Update Full Menu</h2>
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Back to Products
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-cup-hot"></i> Update Complete Brew & Bake Menu</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h5><i class="bi bi-info-circle"></i> About This Update</h5>
                                    <p>This will update your product catalog with the complete Brew & Bake menu including:</p>

                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <h6><i class="bi bi-cake2"></i> Cakes</h6>
                                            <ul class="mb-3 small">
                                                <li>New York Cheesecake (₱250)</li>
                                                <li>Blueberry Cheesecake (₱270)</li>
                                                <li>Matcha Cheesecake (₱280)</li>
                                                <li>And 7 more cake varieties</li>
                                            </ul>

                                            <h6><i class="bi bi-cup"></i> Coffee</h6>
                                            <ul class="mb-3 small">
                                                <li>Espresso (₱150)</li>
                                                <li>Americano (₱160)</li>
                                                <li>Caffè Latte (₱180)</li>
                                                <li>And 7 more coffee varieties</li>
                                            </ul>
                                        </div>

                                        <div class="col-md-6">
                                            <h6><i class="bi bi-egg"></i> Pastries</h6>
                                            <ul class="mb-3 small">
                                                <li>Classic Croissant (₱100)</li>
                                                <li>Chocolate Croissant (₱120)</li>
                                                <li>Almond Croissant (₱130)</li>
                                                <li>And 7 more pastry varieties</li>
                                            </ul>

                                            <h6><i class="bi bi-cup-straw"></i> Non-Coffee Drinks</h6>
                                            <ul class="mb-3 small">
                                                <li>Matcha Latte (₱220)</li>
                                                <li>Chai Tea Latte (₱210)</li>
                                                <li>Hot Chocolate (₱200)</li>
                                                <li>And 7 more drink varieties</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12">
                                            <h6><i class="bi bi-cookie"></i> Other Baked Goods</h6>
                                            <ul class="mb-0 small">
                                                <li>Brownies (₱80), Cookies (₱90), Banana Bread (₱150)</li>
                                                <li>And 7 more baked good varieties</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <p class="mt-3 mb-0"><strong>Note:</strong> This will replace any existing products in your database.</p>
                                </div>

                                <form method="POST" class="mt-4">
                                    <div class="d-grid">
                                        <button type="submit" name="update_menu" class="btn btn-primary btn-lg">
                                            <i class="bi bi-arrow-repeat"></i> Update Complete Menu
                                        </button>
                                    </div>
                                </form>
                            </div>
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
