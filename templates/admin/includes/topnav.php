<?php
require_once "../includes/auth.php";
$user_name = $_SESSION['user_name'] ?? 'Admin';
?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <button class="btn btn-link sidebar-toggle">
            <i class="bi bi-list"></i>
        </button>
        
        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle text-light" type="button" id="notificationsDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-bell"></i>
                    <span class="badge bg-danger">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <h6 class="dropdown-header">Notifications</h6>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-cart-check text-success"></i> New order #1234
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-person-plus text-primary"></i> New customer registered
                    </a>
                    <a class="dropdown-item" href="#">
                        <i class="bi bi-exclamation-triangle text-warning"></i> Low stock alert
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center" href="#">View all notifications</a>
                </div>
            </div>

            <div class="dropdown ms-3">
                <button class="btn btn-link dropdown-toggle text-light" type="button" id="userDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <span class="ms-1"><?= htmlspecialchars($user_name) ?></span>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="profile.php">
                        <i class="bi bi-person"></i> Profile
                    </a>
                    <a class="dropdown-item" href="settings.php">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="../views/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav> 