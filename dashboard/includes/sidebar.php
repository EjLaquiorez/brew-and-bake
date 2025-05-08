<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="bi bi-cup-hot"></i> Brew & Bake</h3>
        <p class="text-muted">Admin Dashboard</p>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">
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
            <a class="nav-link <?= $current_page === 'customers.php' ? 'active' : '' ?>" href="customers.php">
                <i class="bi bi-people"></i> Customers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'reports.php' ? 'active' : '' ?>" href="reports.php">
                <i class="bi bi-graph-up"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $current_page === 'settings.php' ? 'active' : '' ?>" href="settings.php">
                <i class="bi bi-gear"></i> Settings
            </a>
        </li>
    </ul>
</div> 