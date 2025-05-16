<?php
/**
 * Common header component for Brew & Bake
 * This file contains the header that is used across all client pages
 */

// Get user authentication status if not already defined
if (!isset($isLoggedIn)) {
    $isLoggedIn = function_exists('isLoggedIn') ? isLoggedIn() : false;
}
if (!isset($userRole)) {
    $userRole = function_exists('getCurrentUserRole') ? getCurrentUserRole() : '';
}

// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Determine the relative path to the root
$root_path = '';
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
    $root_path = '../../';
} elseif (strpos($_SERVER['PHP_SELF'], '/client/') !== false) {
    $root_path = '../../';
} elseif (strpos($_SERVER['PHP_SELF'], '/views/') !== false) {
    $root_path = '../../';
} else {
    $root_path = '';
}

// Add the site-header CSS
echo '<link rel="stylesheet" href="' . $root_path . 'assets/css/site-header.css?v=' . time() . '">';
?>

<!-- Header -->
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <div class="logo">
                <a href="<?= $root_path ?>index.php">
                    <i class="bi bi-cup-hot"></i> Brew & Bake
                </a>
            </div>
            <div class="header-actions">
                <?php if ($isLoggedIn): ?>
                    <div class="cart-menu">
                        <a href="<?= $root_path ?>templates/client/orders.php" class="cart-icon">
                            <i class="bi bi-cart"></i>
                            <?php if (!empty($_SESSION['cart'])): ?>
                                <span class="cart-count"><?= count($_SESSION['cart']) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="cart-dropdown">
                            <div class="cart-dropdown-header">
                                <h6>Your Cart Items</h6>
                            </div>
                            <div class="cart-dropdown-items">
                                <!-- Cart items will be loaded here via JavaScript -->
                                <div class="cart-dropdown-loading">
                                    <div class="spinner-border spinner-border-sm text-secondary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span>Loading cart items...</span>
                                </div>
                            </div>
                            <div class="cart-dropdown-footer">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <a href="<?= $root_path ?>templates/client/orders.php" class="cart-dropdown-link">
                                        <i class="bi bi-bag me-1"></i> My Orders
                                    </a>
                                </div>
                                <a href="<?= $root_path ?>templates/client/orders.php" class="btn btn-primary w-100">View My Shopping Cart</a>
                                <div class="text-center mt-2">
                                    <small class="text-muted cart-total-count">0 items in cart</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="user-menu">
                        <a href="#" class="user-icon">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <div class="user-dropdown">
                            <ul>
                                <?php if ($userRole === 'admin'): ?>
                                    <li><a href="<?= $root_path ?>templates/admin/dashboard.php">Admin Dashboard</a></li>
                                <?php elseif ($userRole === 'staff'): ?>
                                    <li><a href="<?= $root_path ?>templates/staff/staff.php">Staff Dashboard</a></li>
                                <?php else: ?>
                                    <li><a href="<?= $root_path ?>templates/client/profile.php">My Account</a></li>
                                    <li><a href="<?= $root_path ?>templates/client/orders.php">My Orders</a></li>
                                    <li><a href="<?= $root_path ?>templates/includes/logout.php">Logout</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="#" class="cart-icon" id="loginCartLink">
                        <i class="bi bi-cart"></i>
                    </a>
                    <div class="user-menu">
                        <a href="#" class="user-icon">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <div class="user-dropdown">
                            <ul>
                                <li><a href="#" class="login-link">Login</a></li>
                                <li><a href="#" class="register-link">Register</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
