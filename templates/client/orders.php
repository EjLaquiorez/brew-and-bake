<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'client') {
    $_SESSION['error'] = "Access denied. Client privileges required.";
    header("Location: ../../views/login.php");
    exit;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Handle messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get user information
$userId = $_SESSION['user_id'];

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$query = "
    SELECT o.*, COUNT(oi.id) as item_count, SUM(oi.quantity) as total_items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
";
$params = [$userId];

if (!empty($status)) {
    $query .= " AND o.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (o.id LIKE ? OR o.tracking_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " GROUP BY o.id";

// Add sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY o.created_at ASC";
        break;
    case 'highest':
        $query .= " ORDER BY o.total_amount DESC";
        break;
    case 'lowest':
        $query .= " ORDER BY o.total_amount ASC";
        break;
    default: // newest
        $query .= " ORDER BY o.created_at DESC";
}

// Get orders
try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching orders: " . $e->getMessage();
    $orders = [];
}

// Helper function to get status color
function getStatusColor($status) {
    switch($status) {
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>My Orders - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/client.css?v=<?= time() ?>">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="client.php">
                <i class="bi bi-cup-hot"></i> Brew & Bake
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="client.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="orders.php">My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../views/products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart"></i> Cart
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../includes/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-1">My Orders</h1>
                <p class="text-muted mb-0">View and track all your orders</p>
            </div>
            <a href="../../views/products.php" class="btn btn-primary">
                <i class="bi bi-bag-plus"></i> Shop More
            </a>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search by order #" value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-select" onchange="this.form.submit()">
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
                            <option value="highest" <?= $sort === 'highest' ? 'selected' : '' ?>>Highest Amount</option>
                            <option value="lowest" <?= $sort === 'lowest' ? 'selected' : '' ?>>Lowest Amount</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <?php if (!empty($status) || !empty($search) || $sort !== 'newest'): ?>
                        <div class="col-12">
                            <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Clear Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Orders List -->
        <div class="card">
            <div class="card-header">
                <h2 class="mb-0"><i class="bi bi-receipt"></i> Order History</h2>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-receipt-cutoff display-4 text-muted"></i>
                        <h3 class="mt-3">No Orders Found</h3>
                        <p class="text-muted mb-4">You haven't placed any orders yet or no orders match your filter criteria.</p>
                        <a href="../../views/products.php" class="btn btn-primary">
                            <i class="bi bi-bag-plus"></i> Start Shopping
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?= htmlspecialchars($order['id']) ?></td>
                                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($order['total_items']) ?> items</td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusColor($order['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <a href="cancel_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Are you sure you want to cancel this order?')">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-4">
                    <h3><i class="bi bi-cup-hot"></i> Brew & Bake</h3>
                    <p>Experience the perfect blend of coffee and bakery in a warm, welcoming atmosphere.</p>
                </div>
                <div class="col-lg-4">
                    <h4>Quick Links</h4>
                    <ul class="list-unstyled">
                        <li><a href="client.php">Dashboard</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                        <li><a href="../../views/products.php">Products</a></li>
                        <li><a href="profile.php">Profile</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h4>Contact Us</h4>
                    <p><i class="bi bi-geo-alt"></i> 123 Coffee Street, Manila, Philippines</p>
                    <p><i class="bi bi-telephone"></i> +63 912 345 6789</p>
                    <p><i class="bi bi-envelope"></i> info@brewandbake.com</p>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Brew & Bake. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
