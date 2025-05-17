<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../views/login.php");
    exit;
}

require_once "../includes/db.php";

// Initialize variables
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'desc';

// Check if orders table exists
try {
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $ordersTableExists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $errorMessage = "Error checking orders table: " . $e->getMessage();
    $ordersTableExists = false;
}

// Fetch orders with user information
$orders = [];
$totalOrders = 0;
$pendingOrders = 0;
$completedOrders = 0;
$totalRevenue = 0;

try {
    if ($ordersTableExists) {
        // Build the query
        $query = "
            SELECT o.*, u.name, u.email,
                   COALESCE(u.name, '') as full_name,
                   COALESCE(u.email, '') as user_email,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items
            FROM orders o
            LEFT JOIN users u ON o.client_id = u.id
            WHERE 1=1
        ";

        $params = [];

        // Add search filter
        if (!empty($search)) {
            $query .= " AND (u.name LIKE ? OR u.email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        // Add status filter
        if (!empty($status)) {
            $query .= " AND o.status = ?";
            $params[] = $status;
        }

        // Add date range filter
        if (!empty($dateRange)) {
            $dates = explode(' - ', $dateRange);
            if (count($dates) == 2) {
                $startDate = date('Y-m-d 00:00:00', strtotime($dates[0]));
                $endDate = date('Y-m-d 23:59:59', strtotime($dates[1]));
                $query .= " AND o.created_at BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
            }
        }

        // Add sorting
        $query .= " ORDER BY o.$sortBy $sortOrder";

        // Execute query
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total orders count
        $stmt = $conn->query("SELECT COUNT(*) FROM orders");
        $totalOrders = $stmt->fetchColumn();

        // Get pending orders count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $stmt->execute();
        $pendingOrders = $stmt->fetchColumn();

        // Get completed orders count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
        $stmt->execute();
        $completedOrders = $stmt->fetchColumn();

        // Get total revenue
        $stmt = $conn->prepare("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'");
        $stmt->execute();
        $totalRevenue = $stmt->fetchColumn() ?: 0;
    } else {
        // Placeholder data for demonstration
        $orders = [
            [
                'id' => 1001,
                'client_id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'total_price' => 850.00,
                'status' => 'completed',
                'payment_status' => 'paid',
                'created_at' => '2023-06-15 14:30:45',
                'items' => 3
            ],
            [
                'id' => 1002,
                'client_id' => 2,
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'total_price' => 1250.75,
                'status' => 'processing',
                'payment_status' => 'paid',
                'created_at' => '2023-06-16 09:15:22',
                'items' => 5
            ],
            [
                'id' => 1003,
                'client_id' => 3,
                'name' => 'Robert Johnson',
                'email' => 'robert@example.com',
                'total_price' => 450.50,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'created_at' => '2023-06-16 16:45:10',
                'items' => 2
            ],
            [
                'id' => 1004,
                'client_id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'total_price' => 750.25,
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
                'created_at' => '2023-06-17 11:20:30',
                'items' => 4
            ]
        ];

        $totalOrders = count($orders);
        $pendingOrders = 1;
        $completedOrders = 1;
        $totalRevenue = 2100.75;
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Brew & Bake Admin</title>
    <?php include 'includes/css-includes.php'; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <style>
        /* Enhanced customer display styles */
        .cell-with-image {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cell-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #6c757d;
        }
        .cell-image {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
        }
        .cell-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cell-title {
            margin-bottom: 2px;
            font-size: 0.95rem;
        }
        .cell-subtitle {
            margin-bottom: 0;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }
        /* Enhanced status badges */
        .cell-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        .cell-badge.success {
            color: #0f5132;
            background-color: #d1e7dd;
        }
        .cell-badge.warning {
            color: #664d03;
            background-color: #fff3cd;
        }
        .cell-badge.info {
            color: #055160;
            background-color: #cff4fc;
        }
        .cell-badge.danger {
            color: #842029;
            background-color: #f8d7da;
        }
        /* Order details modal */
        .order-details-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        /* Stats cards */
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        /* Filter section */
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        /* Responsive styles - matching products.php patterns */
        @media (max-width: 992px) {
            .admin-content {
                padding: 1rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }

            .stats-icon {
                width: 42px;
                height: 42px;
                font-size: 1.3rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .page-header > div:last-child {
                width: 100%;
            }

            .page-header > div:last-child .btn {
                width: 100%;
            }

            .filter-section {
                padding: 1rem;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-header .dropdown {
                margin-top: 0.5rem;
                align-self: flex-start;
            }
        }

        @media (max-width: 768px) {
            .admin-content {
                padding: 0.75rem;
            }

            .card-header, .card-body, .card-footer {
                padding: 0.75rem !important;
            }

            .filter-section {
                padding: 0.75rem;
            }

            .stats-icon {
                width: 38px;
                height: 38px;
                font-size: 1.2rem;
            }

            .stats-card .card-title {
                font-size: 1.25rem;
            }

            .table th, .table td {
                padding: 0.625rem;
            }

            .cell-icon, .cell-image {
                width: 36px;
                height: 36px;
            }

            .cell-title {
                font-size: 0.9rem;
            }

            .cell-subtitle {
                font-size: 0.75rem;
            }

            .order-actions .btn {
                padding: 0.25rem 0.5rem;
            }

            .pagination-container {
                margin-top: 0.5rem;
            }
        }

        /* Specific styles for screens 742px and smaller */
        @media (max-width: 742px) {
            .admin-content {
                padding: 0.5rem;
            }

            .card {
                margin-bottom: 0.75rem;
            }

            .card-header, .card-body, .card-footer {
                padding: 0.625rem !important;
            }

            .filter-section {
                padding: 0.625rem;
                margin-bottom: 0.75rem;
            }

            /* Adjust stats cards for better mobile view */
            .stats-card {
                margin-bottom: 0.5rem;
            }

            .stats-card .card-body {
                padding: 0.75rem !important;
            }

            .stats-icon {
                width: 36px;
                height: 36px;
                font-size: 1.1rem;
                margin-right: 0.5rem !important;
            }

            .stats-card .card-title {
                font-size: 1.1rem;
                margin-bottom: 0;
            }

            .stats-card .card-subtitle {
                font-size: 0.7rem;
                margin-bottom: 0.25rem;
            }

            /* Optimize table for small screens */
            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }

            .table th {
                white-space: nowrap;
            }

            .cell-icon, .cell-image {
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }

            .cell-with-image {
                gap: 8px;
            }

            .cell-title {
                font-size: 0.85rem;
                margin-bottom: 1px;
            }

            .cell-subtitle {
                font-size: 0.7rem;
            }

            /* Adjust form elements for better mobile viewing */
            .form-select, .form-control, .btn {
                font-size: 0.875rem;
                padding: 0.375rem 0.5rem;
            }

            /* Adjust order actions buttons */
            .order-actions .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }

            .order-actions .bi {
                font-size: 0.9rem;
            }

            /* Adjust pagination for mobile */
            .pagination-container {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .pagination {
                flex-wrap: nowrap;
                white-space: nowrap;
            }

            /* Adjust modal for mobile */
            .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100% - 1rem);
            }

            .modal-header {
                padding: 0.75rem;
            }

            .modal-body {
                padding: 0.75rem;
            }

            .modal-footer {
                padding: 0.75rem;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .modal-footer .btn {
                flex: 1;
                min-width: 120px;
            }
        }

        /* Very small screens (under 576px) */
        @media (max-width: 576px) {
            /* Make table fully responsive */
            .table-responsive {
                margin: 0;
                padding: 0;
            }

            /* Further reduce sizes */
            .stats-icon {
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }

            .stats-card .card-title {
                font-size: 1rem;
            }

            /* Optimize order actions for very small screens */
            .order-actions {
                display: flex;
                flex-direction: column;
                gap: 0.25rem;
                align-items: flex-end;
            }

            .order-actions .btn {
                width: 100%;
                padding: 0.25rem 0.5rem;
                margin-left: 0 !important;
            }

            /* Adjust modal for very small screens */
            .modal-dialog {
                margin: 0.25rem;
                max-width: calc(100% - 0.5rem);
            }

            .modal-header {
                padding: 0.625rem;
            }

            .modal-body {
                padding: 0.625rem;
            }

            .modal-footer {
                padding: 0.625rem;
            }

            /* Adjust daterangepicker for mobile */
            .daterangepicker {
                width: 280px !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
            }

            .daterangepicker .calendar {
                max-width: 270px !important;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Include Topbar -->
            <?php include 'includes/topbar.php'; ?>

            <!-- Content Area -->
            <div class="admin-content">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="page-title">Orders</h1>
                        <p class="text-muted">Manage customer orders and track order status</p>
                    </div>
                    <div>
                        <a href="add_order.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>Add Order
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="alert-icon">
                            <div class="alert-icon-symbol">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <div class="alert-content">
                                <h6 class="alert-title">Success</h6>
                                <p class="alert-text"><?= htmlspecialchars($successMessage) ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="alert-icon">
                            <div class="alert-icon-symbol">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="alert-content">
                                <h6 class="alert-title">Error</h6>
                                <p class="alert-text"><?= htmlspecialchars($errorMessage) ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Order Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                                        <i class="bi bi-cart"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Total Orders</h6>
                                        <h3 class="card-title mb-0"><?= $totalOrders ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                                        <i class="bi bi-cash-coin"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Total Revenue</h6>
                                        <h3 class="card-title mb-0">₱<?= number_format($totalRevenue, 2) ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                                        <i class="bi bi-hourglass-split"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Pending Orders</h6>
                                        <h3 class="card-title mb-0"><?= $pendingOrders ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-12 mb-3">
                        <div class="card stats-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted mb-1">Completed Orders</h6>
                                        <h3 class="card-title mb-0"><?= $completedOrders ?></h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-section">
                    <form action="" method="get" class="row g-3 order-filters">
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 col-12">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" class="form-control" placeholder="Search customer..." name="search" value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-6 col-md-6 col-sm-6 col-12">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-xl-4 col-lg-8 col-md-8 col-sm-6 col-12">
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                <input type="text" class="form-control" id="dateRangePicker" name="date_range" placeholder="Date range" value="<?= htmlspecialchars($dateRange) ?>">
                            </div>
                        </div>
                        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-12 col-12">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="card-title mb-2 mb-sm-0"><i class="bi bi-cart me-2"></i>All Orders</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-sort-down me-1"></i> <span class="d-none d-sm-inline">Sort</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                                <li><a class="dropdown-item <?= $sortBy === 'created_at' && $sortOrder === 'desc' ? 'active' : '' ?>" href="?sort_by=created_at&sort_order=desc<?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $dateRange ? '&date_range=' . urlencode($dateRange) : '' ?>">Newest First</a></li>
                                <li><a class="dropdown-item <?= $sortBy === 'created_at' && $sortOrder === 'asc' ? 'active' : '' ?>" href="?sort_by=created_at&sort_order=asc<?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $dateRange ? '&date_range=' . urlencode($dateRange) : '' ?>">Oldest First</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item <?= $sortBy === 'total_price' && $sortOrder === 'desc' ? 'active' : '' ?>" href="?sort_by=total_price&sort_order=desc<?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $dateRange ? '&date_range=' . urlencode($dateRange) : '' ?>">Highest Amount</a></li>
                                <li><a class="dropdown-item <?= $sortBy === 'total_price' && $sortOrder === 'asc' ? 'active' : '' ?>" href="?sort_by=total_price&sort_order=asc<?= $search ? '&search=' . urlencode($search) : '' ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $dateRange ? '&date_range=' . urlencode($dateRange) : '' ?>">Lowest Amount</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th class="d-none d-sm-table-cell">Order ID</th>
                                        <th>Customer</th>
                                        <th class="d-none d-md-table-cell">Date</th>
                                        <th>Total</th>
                                        <th class="d-none d-sm-table-cell">Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($orders) > 0): ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td class="d-none d-sm-table-cell">
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-dark text-white me-2">
                                                            <i class="bi bi-hash"></i>
                                                        </span>
                                                        <span class="fw-bold"><?= htmlspecialchars($order['id']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="cell-with-image">
                                                        <div class="cell-icon">
                                                            <i class="bi bi-person-circle"></i>
                                                        </div>
                                                        <div>
                                                            <!-- Show order ID on mobile only -->
                                                            <div class="d-sm-none mb-1">
                                                                <span class="badge bg-dark text-white me-1">
                                                                    <i class="bi bi-hash"></i> <?= htmlspecialchars($order['id']) ?>
                                                                </span>

                                                                <!-- Show status on mobile only -->
                                                                <?php
                                                                    $status = $order['status'] ?? 'pending';
                                                                    $statusClass = '';
                                                                    switch ($status) {
                                                                        case 'completed': $statusClass = 'success'; break;
                                                                        case 'processing': $statusClass = 'info'; break;
                                                                        case 'cancelled': $statusClass = 'danger'; break;
                                                                        default: $statusClass = 'warning';
                                                                    }
                                                                    $statusIcon = '';
                                                                    switch ($status) {
                                                                        case 'completed': $statusIcon = '<i class="bi bi-check-circle-fill me-1"></i>'; break;
                                                                        case 'processing': $statusIcon = '<i class="bi bi-arrow-repeat me-1"></i>'; break;
                                                                        case 'cancelled': $statusIcon = '<i class="bi bi-x-circle-fill me-1"></i>'; break;
                                                                        default: $statusIcon = '<i class="bi bi-hourglass-split me-1"></i>';
                                                                    }
                                                                ?>
                                                                <span class="cell-badge <?= $statusClass ?> d-sm-none">
                                                                    <?= $statusIcon . ucfirst($status) ?>
                                                                </span>
                                                            </div>

                                                            <h6 class="cell-title fw-semibold">
                                                                <?php
                                                                // Try different field names for customer name with enhanced display
                                                                if (!empty($order['full_name']) && $order['full_name'] != ' ') {
                                                                    echo htmlspecialchars($order['full_name']);
                                                                } elseif (!empty($order['first_name']) && !empty($order['last_name'])) {
                                                                    echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                                                } elseif (!empty($order['customer_name'])) {
                                                                    echo htmlspecialchars($order['customer_name']);
                                                                } elseif (!empty($order['name'])) {
                                                                    echo htmlspecialchars($order['name']);
                                                                } elseif (!empty($order['user_name'])) {
                                                                    echo htmlspecialchars($order['user_name']);
                                                                } elseif (!empty($order['customer'])) {
                                                                    echo htmlspecialchars($order['customer']);
                                                                } elseif (!empty($order['fullname'])) {
                                                                    echo htmlspecialchars($order['fullname']);
                                                                } elseif (!empty($order['user_id'])) {
                                                                    echo '<span class="text-primary">Client #' . htmlspecialchars($order['user_id']) . '</span>';
                                                                } elseif (!empty($order['customer_id'])) {
                                                                    echo '<span class="text-primary">Client #' . htmlspecialchars($order['customer_id']) . '</span>';
                                                                } elseif (!empty($order['uid'])) {
                                                                    echo '<span class="text-primary">Client #' . htmlspecialchars($order['uid']) . '</span>';
                                                                } elseif (!$ordersTableExists) {
                                                                    // For placeholder data, show a friendly name
                                                                    echo '<span class="text-muted">Sample Customer</span>';
                                                                } else {
                                                                    echo '<span class="text-muted">Guest Order #' . htmlspecialchars($order['id']) . '</span>';
                                                                }
                                                                ?>
                                                            </h6>
                                                            <p class="cell-subtitle mb-1">
                                                                <?php
                                                                // Try different field names for email with icon
                                                                if (!empty($order['email'])) {
                                                                    echo '<i class="bi bi-envelope-fill text-muted me-1 small"></i> ' . htmlspecialchars($order['email']);
                                                                } elseif (!empty($order['user_email'])) {
                                                                    echo '<i class="bi bi-envelope-fill text-muted me-1 small"></i> ' . htmlspecialchars($order['user_email']);
                                                                } elseif (!empty($order['customer_email'])) {
                                                                    echo '<i class="bi bi-envelope-fill text-muted me-1 small"></i> ' . htmlspecialchars($order['customer_email']);
                                                                } else {
                                                                    echo '<span class="text-muted fst-italic small"><i class="bi bi-envelope text-muted me-1"></i> No email provided</span>';
                                                                }
                                                                ?>
                                                            </p>

                                                            <!-- Show date on mobile only -->
                                                            <p class="cell-subtitle d-md-none mt-1">
                                                                <i class="bi bi-calendar-event text-muted me-1 small"></i>
                                                                <small><?= date('M d, Y', strtotime($order['created_at'])) ?></small>
                                                                <span class="mx-1">•</span>
                                                                <i class="bi bi-clock text-muted me-1 small"></i>
                                                                <small><?= date('h:i A', strtotime($order['created_at'])) ?></small>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <div>
                                                        <div class="fw-medium">
                                                            <i class="bi bi-calendar-event text-primary me-1"></i>
                                                            <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                                        </div>
                                                        <div class="text-muted small">
                                                            <i class="bi bi-clock text-muted me-1"></i>
                                                            <?= date('h:i A', strtotime($order['created_at'])) ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-light text-dark border me-2 d-none d-sm-inline-flex">
                                                            <i class="bi bi-cart-fill text-primary me-1"></i>
                                                            <?= isset($order['items']) ? $order['items'] : '?' ?>
                                                        </span>
                                                        <span class="fw-bold">₱<?= number_format($order['total_price'] ?? 0, 2) ?></span>
                                                    </div>
                                                </td>
                                                <td class="d-none d-sm-table-cell">
                                                    <?php
                                                        $status = $order['status'] ?? 'pending';
                                                        $statusClass = '';
                                                        switch ($status) {
                                                            case 'completed': $statusClass = 'success'; break;
                                                            case 'processing': $statusClass = 'info'; break;
                                                            case 'cancelled': $statusClass = 'danger'; break;
                                                            default: $statusClass = 'warning';
                                                        }
                                                    ?>
                                                    <span class="cell-badge <?= $statusClass ?>">
                                                        <?php
                                                        $statusIcon = '';
                                                        switch ($status) {
                                                            case 'completed': $statusIcon = '<i class="bi bi-check-circle-fill me-1"></i>'; break;
                                                            case 'processing': $statusIcon = '<i class="bi bi-arrow-repeat me-1"></i>'; break;
                                                            case 'cancelled': $statusIcon = '<i class="bi bi-x-circle-fill me-1"></i>'; break;
                                                            default: $statusIcon = '<i class="bi bi-hourglass-split me-1"></i>';
                                                        }
                                                        echo $statusIcon . ucfirst($status);
                                                        ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group order-actions">
                                                        <button type="button" class="btn btn-sm btn-outline-primary view-order-btn" data-order-id="<?= $order['id'] ?>" title="View Order">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <a href="edit_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Order">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-order-btn" data-order-id="<?= $order['id'] ?>" title="Delete Order">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                                <h5 class="mt-3">No Orders Found</h5>
                                                <p class="text-muted">There are no orders matching your search criteria.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="mb-2 mb-md-0">
                                <p class="text-muted mb-0 small">Showing <?= count($orders) ?> of <?= $totalOrders ?> orders</p>
                            </div>
                            <nav aria-label="Page navigation" class="pagination-container">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                                            <i class="bi bi-chevron-left"></i>
                                            <span class="d-none d-sm-inline-block ms-1">Previous</span>
                                        </a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#">
                                            <i class="bi bi-chevron-right"></i>
                                            <span class="d-none d-sm-inline-block ms-1">Next</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg order-details-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading order details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex flex-wrap justify-content-end w-100 gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i> Close
                        </button>
                        <a href="#" class="btn btn-primary flex-fill" id="editOrderBtn">
                            <i class="bi bi-pencil me-1"></i> Edit Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Order Confirmation Modal -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-labelledby="deleteOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteOrderModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this order? This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Deleting an order will also remove all associated order items and transaction records.
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex flex-wrap justify-content-end w-100 gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </button>
                        <a href="#" class="btn btn-danger flex-fill" id="confirmDeleteBtn">
                            <i class="bi bi-trash me-1"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer Scripts -->
    <?php include 'includes/footer-scripts.php'; ?>

    <!-- Date Range Picker -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Date Range Picker
            $('#dateRangePicker').daterangepicker({
                opens: 'left',
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear',
                    format: 'MM/DD/YYYY'
                }
            });

            $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
            });

            $('#dateRangePicker').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
            });

            // View Order Details
            const viewOrderBtns = document.querySelectorAll('.view-order-btn');
            const orderDetailsModal = document.getElementById('orderDetailsModal');
            const editOrderBtn = document.getElementById('editOrderBtn');

            viewOrderBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    const modal = new bootstrap.Modal(orderDetailsModal);

                    // Update edit button URL
                    editOrderBtn.href = `edit_order.php?id=${orderId}`;

                    // Show modal
                    modal.show();

                    // Fetch order details
                    fetch(`order_details.php?id=${orderId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                orderDetailsModal.querySelector('.modal-body').innerHTML = data.html;
                            } else {
                                orderDetailsModal.querySelector('.modal-body').innerHTML = `
                                    <div class="alert alert-danger">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        ${data.error || 'Failed to load order details.'}
                                    </div>
                                `;
                            }
                        })
                        .catch(error => {
                            orderDetailsModal.querySelector('.modal-body').innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    An error occurred while fetching order details.
                                </div>
                            `;
                            console.error('Error fetching order details:', error);
                        });
                });
            });

            // Delete Order Confirmation
            const deleteOrderBtns = document.querySelectorAll('.delete-order-btn');
            const deleteOrderModal = document.getElementById('deleteOrderModal');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

            deleteOrderBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    const modal = new bootstrap.Modal(deleteOrderModal);

                    // Update confirm delete button URL
                    confirmDeleteBtn.href = `delete_order.php?id=${orderId}`;

                    // Show modal
                    modal.show();
                });
            });
        });
    </script>
</body>
</html>
