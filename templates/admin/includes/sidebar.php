<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <i class="bi bi-cup-hot" style="font-size: 1.75rem; color: #f59e0b;"></i>
            </div>
            <div>
                <h3 class="sidebar-title mb-0">Brew & Bake</h3>
                <p class="sidebar-subtitle mb-0">Admin Dashboard</p>
            </div>
        </div>
        <button class="sidebar-close d-lg-none">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <p class="nav-section-title">MAIN NAVIGATION</p>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'products.php' ? 'active' : '' ?>" href="products.php">
                        <i class="bi bi-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'manage_product_images_list.php' || $current_page === 'manage_product_images.php' ? 'active' : '' ?>" href="manage_product_images_list.php">
                        <i class="bi bi-images"></i> Product Images
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'orders.php' ? 'active' : '' ?>" href="orders.php">
                        <i class="bi bi-cart"></i> Orders
                        <?php if ($current_page === 'orders.php'): ?>
                            <span class="nav-badge">New</span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'categories.php' ? 'active' : '' ?>" href="categories.php">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <p class="nav-section-title">REPORTS & ANALYTICS</p>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'sales.php' ? 'active' : '' ?>" href="sales.php">
                        <i class="bi bi-cash-coin"></i> Sales
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <p class="nav-section-title">USER SETTINGS</p>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'settings.php' ? 'active' : '' ?>" href="settings.php">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../../templates/includes/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-center p-3">
            <span class="text-muted small">© <?= date('Y') ?> Brew & Bake</span>
        </div>
    </div>
</aside>