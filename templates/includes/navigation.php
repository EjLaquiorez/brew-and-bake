<?php
/**
 * Common navigation component for Brew & Bake
 * This file contains the navigation bar that is used across all pages
 */

// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Determine if we're in the admin section
$is_admin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;

// Determine if we're in the views section
$is_views = strpos($_SERVER['PHP_SELF'], '/views/') !== false;

// Determine the relative path to the root
$root_path = '';
if ($is_admin) {
    $root_path = '../../';
} elseif ($is_views) {
    $root_path = '../../';
} else {
    $root_path = '';
}

// Get user authentication status
$isLoggedIn = function_exists('isLoggedIn') ? isLoggedIn() : false;
$userRole = function_exists('getCurrentUserRole') ? getCurrentUserRole() : '';
?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $root_path ?>index.php">
            <i class="bi bi-cup-hot"></i> Brew & Bake
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" 
                       href="<?= $root_path ?>index.php">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'products.php' ? 'active' : '' ?>" 
                       href="<?= $root_path . ($is_views ? '' : 'views/') ?>products.php">MENU</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $root_path ?>index.php#about">ABOUT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $root_path ?>index.php#contact">CONTACT</a>
                </li>
                
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'cart.php' ? 'active' : '' ?>" 
                           href="<?= $root_path . ($is_views ? '' : 'views/') ?>cart.php">
                            <i class="bi bi-cart3"></i>
                            <span class="cart-badge">0</span>
                        </a>
                    </li>
                    
                    <?php if ($userRole === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $root_path ?>templates/admin/dashboard.php">ADMIN</a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            ACCOUNT
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="<?= $root_path . ($is_views ? '' : 'views/') ?>profile.php">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $root_path . ($is_views ? '' : 'views/') ?>orders.php">
                                    <i class="bi bi-bag"></i> My Orders
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= $root_path ?>templates/includes/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'login.php' ? 'active' : '' ?>" 
                           href="<?= $root_path . ($is_views ? '' : 'views/') ?>login.php">LOGIN</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'register.php' ? 'active' : '' ?>" 
                           href="<?= $root_path . ($is_views ? '' : 'views/') ?>register.php">REGISTER</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
