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

if (isset($_POST['update_categories'])) {
    try {
        // Read the SQL file
        $sqlFile = file_get_contents("../includes/update_categories.sql");
        
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
        
        $message = "Categories updated successfully! $successCount statements processed.";
        $alertType = 'success';
        
    } catch (PDOException $e) {
        $message = "Error updating categories: " . $e->getMessage();
        $alertType = 'danger';
    }
}

// Fetch current categories
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'categories'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $categories = [];
    }
} catch (PDOException $e) {
    $categories = [];
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
    <title>Update Categories - Brew & Bake</title>
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
                        <a href="products.php" class="nav-link">
                            <i class="bi bi-box-seam"></i>
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link active">
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
                            <h2 class="page-title">Update Categories</h2>
                            <div>
                                <a href="categories.php" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Categories
                                </a>
                            </div>
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
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-tags"></i> Update Brew & Bake Categories</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h5><i class="bi bi-info-circle"></i> About This Update</h5>
                                    <p>This will update your categories to match the new Brew & Bake menu structure:</p>
                                    
                                    <ul class="mb-0">
                                        <li><strong>Cakes</strong> - Delicious cakes and cheesecakes for all occasions</li>
                                        <li><strong>Pastries</strong> - Freshly baked pastries including croissants and rolls</li>
                                        <li><strong>Coffee</strong> - Premium coffee drinks from espresso to specialty lattes</li>
                                        <li><strong>Non-Coffee Drinks</strong> - Refreshing alternatives including tea and smoothies</li>
                                        <li><strong>Other Baked Goods</strong> - Cookies, brownies, and specialty desserts</li>
                                    </ul>
                                    
                                    <p class="mt-3 mb-0"><strong>Note:</strong> This will replace any existing categories in your database.</p>
                                </div>
                                
                                <form method="POST" class="mt-4">
                                    <div class="d-grid">
                                        <button type="submit" name="update_categories" class="btn btn-primary btn-lg">
                                            <i class="bi bi-arrow-repeat"></i> Update Categories
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-list-check"></i> Current Categories</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($categories)): ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        No categories found in the database.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Description</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categories as $category): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars(ucfirst($category['name'])) ?></td>
                                                        <td><?= htmlspecialchars($category['description']) ?></td>
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
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer-scripts.php'; ?>
</body>
</html>
