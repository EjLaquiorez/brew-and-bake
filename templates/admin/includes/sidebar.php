<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h3><i class="bi bi-cup-hot"></i> Brew & Bake</h3>
        <p class="text-muted">Admin Dashboard</p>
        <button class="sidebar-close d-lg-none">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <nav class="sidebar-nav">
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
                <a class="nav-link <?= $current_page === 'orders.php' ? 'active' : '' ?>" href="orders.php">
                    <i class="bi bi-cart"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'categories.php' ? 'active' : '' ?>" href="categories.php">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>
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
        </ul>
    </nav>
</aside>