<?php
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'name' => 'Test Admin',
        'role' => 'Administrator'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sidebar User Menu - Brew and Bake Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <img src="../../assets/images/logo.png" alt="Brew and Bake" class="logo-img">
                    <h1 class="logo-text">Brew and Bake</h1>
                </div>
                <button class="sidebar-close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="sidebar-content">
                <div class="sidebar-menu">
                    <ul class="nav-list">
                        <li class="nav-item active">
                            <a href="dashboard.php" class="nav-link">
                                <i class="bi bi-speedometer2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="products.php" class="nav-link">
                                <i class="bi bi-box-seam"></i>
                                Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="orders.php" class="nav-link">
                                <i class="bi bi-receipt"></i>
                                Orders
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
                <div class="row mb-5">
                    <div class="col-12 mb-4">
                        <h3 class="mb-4">Test Sidebar User Menu</h3>
                        <p>This page is used to test the sidebar user menu dropdown functionality.</p>
                        <p>Click on the user menu in the sidebar to see if the dropdown appears correctly.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer-scripts.php'; ?>
</body>
</html>
