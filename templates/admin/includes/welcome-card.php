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

<?php
// Set up variables for the readable card component
$card_title = "Welcome back, " . htmlspecialchars($_SESSION['user_name'] ?? 'Admin') . "!";
$card_content = "<p class='welcome-subtitle mb-3 leading-relaxed'>" . $page_description . "</p>
                <div class='mt-4'>
                    <a href='products.php' class='btn btn-light btn-sm mt-2'>
                        <i class='bi bi-plus-lg me-1'></i> Add New Product
                    </a>
                    <a href='orders.php' class='btn btn-outline-light btn-sm mt-2 ms-2'>
                        <i class='bi bi-cart me-1'></i> View Orders
                    </a>
                </div>";
$card_footer = "<div class='text-end'>
                    <h4 class='mb-1 welcome-time font-medium' id='currentTime'></h4>
                    <p class='welcome-date mb-0 text-tertiary' id='currentDate'></p>
                </div>";
$card_class = "welcome-card fade-in";
$card_body_class = "p-4 p-md-5";
?>

<div class="row mb-5">
    <div class="col-12 mb-4">
        <?php include '../../templates/includes/components/readable_card.php'; ?>
    </div>
</div>

<script>
// Update the current time and date
function updateDateTime() {
    const now = new Date();

    // Update time
    let hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'

    document.getElementById('currentTime').textContent =
        hours + ':' + minutes + ':' + seconds + ' ' + ampm;

    // Update date
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    const dayName = days[now.getDay()];
    const monthName = months[now.getMonth()];
    const date = now.getDate();
    const year = now.getFullYear();

    document.getElementById('currentDate').textContent =
        dayName + ', ' + monthName + ' ' + date + ', ' + year;
}

// Update time immediately and then every second
updateDateTime();
setInterval(updateDateTime, 1000);
</script>
