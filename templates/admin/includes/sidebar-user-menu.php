<div class="user-menu" id="sidebarUserMenuToggle">
    <div class="user-avatar">
        <?= substr($_SESSION['user']['name'] ?? 'A', 0, 1) ?>
    </div>
    <div class="user-info">
        <h6 class="user-name"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?></h6>
        <p class="user-role">Administrator</p>
    </div>
    <i class="bi bi-chevron-down user-menu-toggle"></i>
</div>

<!-- User Menu Dropdown -->
<div id="userMenu" class="user-menu-dropdown">
    <div class="user-menu-header">
        <h6>USER MENU</h6>
    </div>
    <div class="user-menu-items">
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
