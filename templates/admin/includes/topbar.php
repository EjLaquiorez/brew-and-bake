<?php
/**
 * Topbar component for the admin dashboard
 * This file contains the topbar with notifications, messages, and user profile dropdowns
 */

// Include settings if not already included
if (!isset($settings)) {
    require_once __DIR__ . "/../../includes/settings.php";
}

// Get the current page title
$page_title = '';
$current_page = basename($_SERVER['PHP_SELF']);

// Set page title based on current page
switch ($current_page) {
    case 'dashboard.php':
        $page_title = 'Dashboard';
        break;
    case 'products.php':
        $page_title = 'Products';
        break;
    case 'orders.php':
        $page_title = 'Orders';
        break;
    case 'categories.php':
        $page_title = 'Categories';
        break;
    case 'analytics.php':
        $page_title = 'Analytics';
        break;
    case 'sales.php':
        $page_title = 'Sales';
        break;
    case 'profile.php':
        $page_title = 'Profile';
        break;
    case 'settings.php':
        $page_title = 'System Settings';
        break;
    default:
        $page_title = 'Admin Dashboard';
}
?>

<!-- Topbar -->
<header class="admin-topbar">
    <div class="topbar-left">
        <button class="menu-toggle">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="page-title"><?= $page_title ?></h1>
    </div>
    <div class="topbar-right">
        <!-- Notifications Dropdown -->
        <div class="topbar-icon" id="notificationIcon">
            <i class="bi bi-bell"></i>
            <span class="topbar-badge">3</span>

            <!-- Notifications Dropdown Menu -->
            <div class="dropdown-menu" id="notificationsDropdown">
                <div class="dropdown-header">
                    <h6 class="dropdown-title">Notifications</h6>
                    <div class="dropdown-actions">
                        <a href="#" class="dropdown-action" id="markAllRead">Mark all as read</a>
                    </div>
                </div>
                <div class="dropdown-body">
                    <!-- Unread Notification -->
                    <a href="#" class="notification-item unread">
                        <div class="notification-icon success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="notification-content">
                            <h6 class="notification-title">New order received</h6>
                            <p class="notification-text">Order #1234 has been placed and is awaiting processing.</p>
                            <div class="notification-time">2 minutes ago</div>
                        </div>
                    </a>

                    <!-- Unread Notification -->
                    <a href="#" class="notification-item unread">
                        <div class="notification-icon warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="notification-content">
                            <h6 class="notification-title">Low stock alert</h6>
                            <p class="notification-text">Cappuccino Blend is running low. Current stock: 5 items.</p>
                            <div class="notification-time">1 hour ago</div>
                        </div>
                    </a>

                    <!-- Read Notification -->
                    <a href="#" class="notification-item">
                        <div class="notification-icon primary">
                            <i class="bi bi-star"></i>
                        </div>
                        <div class="notification-content">
                            <h6 class="notification-title">New review received</h6>
                            <p class="notification-text">A customer left a 5-star review for Chocolate Croissant.</p>
                            <div class="notification-time">Yesterday</div>
                        </div>
                    </a>
                </div>
                <div class="dropdown-footer">
                    <a href="notifications.php">View all notifications</a>
                </div>
            </div>
        </div>

        <!-- Messages Dropdown -->
        <div class="topbar-icon" id="messageIcon">
            <i class="bi bi-envelope"></i>
            <span class="topbar-badge">5</span>

            <!-- Messages Dropdown Menu -->
            <div class="dropdown-menu" id="messagesDropdown">
                <div class="dropdown-header">
                    <h6 class="dropdown-title">Messages</h6>
                    <div class="dropdown-actions">
                        <a href="#" class="dropdown-action">Mark all as read</a>
                    </div>
                </div>
                <div class="dropdown-body">
                    <!-- Unread Message -->
                    <a href="#" class="notification-item unread">
                        <div class="notification-icon info">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="notification-content">
                            <h6 class="notification-title">John Doe</h6>
                            <p class="notification-text">I'd like to inquire about my order status #1234.</p>
                            <div class="notification-time">5 minutes ago</div>
                        </div>
                    </a>

                    <!-- Unread Message -->
                    <a href="#" class="notification-item unread">
                        <div class="notification-icon info">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="notification-content">
                            <h6 class="notification-title">Jane Smith</h6>
                            <p class="notification-text">Do you offer gluten-free options for your pastries?</p>
                            <div class="notification-time">30 minutes ago</div>
                        </div>
                    </a>

                    <!-- Read Message -->
                    <a href="#" class="notification-item">
                        <div class="notification-icon info">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="notification-content">
                            <h6 class="notification-title">Support Team</h6>
                            <p class="notification-text">Your monthly report is ready for review.</p>
                            <div class="notification-time">2 days ago</div>
                        </div>
                    </a>
                </div>
                <div class="dropdown-footer">
                    <a href="messages.php">View all messages</a>
                </div>
            </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="topbar-profile" id="userProfileIcon">
            <div class="topbar-avatar">
                <?= substr($_SESSION['user_name'] ?? 'A', 0, 1) ?>
            </div>
            <span class="topbar-user d-none d-md-block"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
            <i class="bi bi-chevron-down topbar-dropdown"></i>

            <!-- User Menu Dropdown -->
            <div class="dropdown-menu" id="userDropdown">
                <div class="dropdown-header">
                    <h6 class="dropdown-title">User Menu</h6>
                </div>
                <div class="dropdown-body">
                    <a href="profile.php" class="user-menu-item">
                        <i class="bi bi-person user-menu-icon"></i>
                        My Profile
                    </a>
                    <a href="settings.php" class="user-menu-item">
                        <i class="bi bi-gear user-menu-icon"></i>
                        Settings
                    </a>
                    <a href="#" class="user-menu-item">
                        <i class="bi bi-question-circle user-menu-icon"></i>
                        Help & Support
                    </a>
                    <div class="user-menu-divider"></div>
                    <a href="../../templates/includes/logout.php" class="user-menu-item">
                        <i class="bi bi-box-arrow-right user-menu-icon"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
