<?php
/**
 * Page header component for admin pages
 * This provides a consistent page header across all admin pages except dashboard
 */

// Get the current page title
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_titles = [
    'dashboard' => 'Dashboard',
    'products' => 'Products Management',
    'orders' => 'Orders Management',
    'sales' => 'Sales Analytics',
    'analytics' => 'Analytics Dashboard',
    'categories' => 'Categories Management',
    'profile' => 'Profile Settings',
    'settings' => 'System Settings',
    'add_product' => 'Add New Product',
    'edit_product' => 'Edit Product',
    'view_product' => 'Product Details',
    'manage_product_images' => 'Manage Product Images'
];

$page_title = $page_titles[$current_page] ?? ucfirst(str_replace('_', ' ', $current_page));

// Get page descriptions
$page_descriptions = [
    'dashboard' => "Here's an overview of your store's performance.",
    'products' => "View, edit, and manage your product inventory.",
    'orders' => "View and manage customer orders.",
    'sales' => "Detailed sales data and transaction history.",
    'analytics' => "Analyze your store's performance metrics.",
    'categories' => "Organize your products with categories.",
    'profile' => "Update your profile information.",
    'settings' => "Configure your store settings.",
    'add_product' => "Create a new product for your inventory.",
    'edit_product' => "Update product information and details.",
    'view_product' => "View detailed information about this product.",
    'manage_product_images' => "Upload and edit product images."
];

$page_description = $page_descriptions[$current_page] ?? '';

// Define page-specific actions
$page_actions = [
    'products' => [
        [
            'url' => 'add_product.php',
            'icon' => 'bi-plus-lg',
            'text' => 'Add Product',
            'class' => 'btn-primary'
        ],
        [
            'url' => 'manage_product_images.php',
            'icon' => 'bi-images',
            'text' => 'Manage Images',
            'class' => 'btn-outline-primary'
        ]
    ],
    'orders' => [
        [
            'url' => 'orders.php?status=pending',
            'icon' => 'bi-hourglass-split',
            'text' => 'Pending Orders',
            'class' => 'btn-warning'
        ],
        [
            'url' => 'orders.php',
            'icon' => 'bi-cart',
            'text' => 'All Orders',
            'class' => 'btn-outline-primary'
        ]
    ],
    'categories' => [
        [
            'url' => '#',
            'icon' => 'bi-plus-lg',
            'text' => 'Add Category',
            'class' => 'btn-primary',
            'data' => 'data-bs-toggle="modal" data-bs-target="#addCategoryModal"'
        ]
    ],
    'analytics' => [
        [
            'url' => '#',
            'icon' => 'bi-download',
            'text' => 'Export Report',
            'class' => 'btn-outline-primary'
        ]
    ],
    'view_product' => [
        [
            'url' => 'edit_product.php?id=' . ($_GET['id'] ?? ''),
            'icon' => 'bi-pencil',
            'text' => 'Edit Product',
            'class' => 'btn-primary'
        ],
        [
            'url' => 'manage_product_images.php?id=' . ($_GET['id'] ?? ''),
            'icon' => 'bi-images',
            'text' => 'Manage Images',
            'class' => 'btn-outline-primary'
        ]
    ],
    'edit_product' => [
        [
            'url' => 'view_product.php?id=' . ($_GET['id'] ?? ''),
            'icon' => 'bi-eye',
            'text' => 'View Product',
            'class' => 'btn-outline-primary'
        ]
    ],
    'manage_product_images' => [
        [
            'url' => 'products.php',
            'icon' => 'bi-arrow-left',
            'text' => 'Back to Products',
            'class' => 'btn-outline-primary'
        ]
    ],
    'add_product' => [
        [
            'url' => 'products.php',
            'icon' => 'bi-arrow-left',
            'text' => 'Back to Products',
            'class' => 'btn-outline-primary'
        ]
    ]
];

$current_actions = $page_actions[$current_page] ?? [];

// Get page icon
$page_icons = [
    'dashboard' => 'bi-speedometer2',
    'products' => 'bi-box',
    'orders' => 'bi-cart',
    'sales' => 'bi-cash-coin',
    'analytics' => 'bi-graph-up',
    'categories' => 'bi-tags',
    'profile' => 'bi-person',
    'settings' => 'bi-gear',
    'add_product' => 'bi-plus-circle',
    'edit_product' => 'bi-pencil-square',
    'view_product' => 'bi-eye',
    'manage_product_images' => 'bi-images'
];

$page_icon = $page_icons[$current_page] ?? 'bi-file-earmark';
?>

<div class="row mb-5">
    <div class="col-12 mb-4">
        <div class="card page-header-card fade-in">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <div class="page-header-icon">
                            <i class="bi <?= $page_icon ?>"></i>
                        </div>
                        <div class="ms-3">
                            <h2 class="mb-1 page-header-title"><?= $page_title ?></h2>
                            <p class="page-header-subtitle mb-0"><?= $page_description ?></p>
                        </div>
                    </div>
                    <?php if (!empty($current_actions)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($current_actions as $action): ?>
                                <a href="<?= $action['url'] ?>" class="btn <?= $action['class'] ?>" <?= $action['data'] ?? '' ?>>
                                    <i class="bi <?= $action['icon'] ?> me-2"></i>
                                    <?= $action['text'] ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
