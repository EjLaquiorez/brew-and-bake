<?php
/**
 * Welcome card component for admin pages
 * This provides a consistent welcome message across all admin pages
 */

// Get the current page title
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'dashboard' => 'Dashboard',
    'products' => 'Products',
    'orders' => 'Orders',
    'sales' => 'Sales',
    'analytics' => 'Analytics',
    'categories' => 'Categories',
    'profile' => 'Profile',
    'settings' => 'Settings'
];

$page_title = $page_titles[$current_page] ?? ucfirst($current_page);

// Get page descriptions
$page_descriptions = [
    'dashboard' => "Here's an overview of your store's performance.",
    'products' => "View, edit, and manage your product inventory.",
    'orders' => "View and manage customer orders.",
    'sales' => "Detailed sales data and transaction history.",
    'analytics' => "Analyze your store's performance metrics.",
    'categories' => "Manage your product categories.",
    'profile' => "Update your profile information.",
    'settings' => "Configure your store settings."
];

$page_description = $page_descriptions[$current_page] ?? '';
?>

<div class="row mb-5">
    <div class="col-12 mb-4">
        <div class="card card-primary fade-in">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="mb-2">Welcome back, <?= htmlspecialchars($_SESSION['user']['name'] ?? 'Admin') ?>!</h2>
                        <p class="text-muted mb-0"><?= $page_description ?></p>
                    </div>
                    <div class="text-end mt-3 mt-md-0">
                        <h4 class="mb-1 font-medium" id="currentTime"></h4>
                        <p class="text-muted mb-0">Wednesday, May 14, 2025</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update the current time
function updateTime() {
    const now = new Date();
    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'

    document.getElementById('currentTime').textContent =
        hours + ':' + minutes + ':' + seconds + ' ' + ampm;
}

// Update time immediately and then every second
updateTime();
setInterval(updateTime, 1000);
</script>
