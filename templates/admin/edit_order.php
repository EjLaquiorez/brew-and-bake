<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: ../../views/login.php");
    exit;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';
$order = [];
$orderItems = [];
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update order status
        $status = $_POST['status'] ?? '';
        $paymentStatus = $_POST['payment_status'] ?? '';
        $notes = $_POST['notes'] ?? '';

        if ($orderId && $status) {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, payment_status = ?, notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $paymentStatus, $notes, $orderId]);

            $successMessage = "Order #$orderId has been updated successfully.";

            // Redirect to prevent form resubmission
            $_SESSION['success'] = $successMessage;
            header("Location: edit_order.php?id=$orderId");
            exit;
        }
    } catch (PDOException $e) {
        $errorMessage = "Error updating order: " . $e->getMessage();
    }
}

// Handle messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Fetch order details
try {
    if ($orderId) {
        // Check if orders table exists
        $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
        $stmt->execute();
        $tableExists = $stmt->rowCount() > 0;

        if ($tableExists) {
            // Check if users table exists
            $stmt = $conn->prepare("SHOW TABLES LIKE 'users'");
            $stmt->execute();
            $usersTableExists = $stmt->rowCount() > 0;

            if ($usersTableExists) {
                // Determine the join condition based on available columns
                $stmt = $conn->prepare("DESCRIBE orders");
                $stmt->execute();
                $orderColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

                $joinCondition = "";
                if (in_array('user_id', $orderColumns)) {
                    $joinCondition = "o.user_id = u.id";
                } elseif (in_array('client_id', $orderColumns)) {
                    $joinCondition = "o.client_id = u.id";
                } elseif (in_array('customer_id', $orderColumns)) {
                    $joinCondition = "o.customer_id = u.id";
                }

                if ($joinCondition) {
                    // Check which columns exist in the users table
                    $stmt = $conn->prepare("DESCRIBE users");
                    $stmt->execute();
                    $userColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

                    // Build the SELECT clause based on available columns
                    $userSelectFields = [];

                    // Always include these fields if they exist
                    if (in_array('name', $userColumns)) {
                        $userSelectFields[] = "u.name as customer_name";
                    }
                    if (in_array('email', $userColumns)) {
                        $userSelectFields[] = "u.email";
                    }
                    if (in_array('phone', $userColumns)) {
                        $userSelectFields[] = "u.phone as customer_phone";
                    } else if (in_array('contact_number', $userColumns)) {
                        $userSelectFields[] = "u.contact_number as customer_phone";
                    } else if (in_array('mobile', $userColumns)) {
                        $userSelectFields[] = "u.mobile as customer_phone";
                    }
                    if (in_array('first_name', $userColumns) && in_array('last_name', $userColumns)) {
                        $userSelectFields[] = "CONCAT(u.first_name, ' ', u.last_name) as full_name";
                        $userSelectFields[] = "u.first_name";
                        $userSelectFields[] = "u.last_name";
                    }
                    if (in_array('profile_image', $userColumns)) {
                        $userSelectFields[] = "u.profile_image";
                    }

                    // Build the SQL query with the available fields
                    $userSelectClause = !empty($userSelectFields) ? ", " . implode(", ", $userSelectFields) : "";

                    // Join with users table to get customer information
                    $stmt = $conn->prepare("
                        SELECT o.*$userSelectClause
                        FROM orders o
                        LEFT JOIN users u ON $joinCondition
                        WHERE o.id = ?
                    ");
                    $stmt->execute([$orderId]);
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    // If no suitable join condition, just get the order
                    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
                    $stmt->execute([$orderId]);
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);
                }

                // Fetch order items
                $stmt = $conn->prepare("SHOW TABLES LIKE 'order_items'");
                $stmt->execute();
                $orderItemsTableExists = $stmt->rowCount() > 0;

                if ($orderItemsTableExists) {
                    $stmt = $conn->prepare("
                        SELECT oi.*, p.name as product_name, p.image as product_image
                        FROM order_items oi
                        LEFT JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                    ");
                    $stmt->execute([$orderId]);
                    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } else {
                // If users table doesn't exist, just get the order
                $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }

        // If no order found, show error
        if (!$order) {
            $errorMessage = "Order #$orderId not found.";
        }
    } else {
        $errorMessage = "No order ID provided.";
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching order details: " . $e->getMessage();
}

// Get customer name
$customerName = '';
if (!empty($order['full_name']) && $order['full_name'] != ' ') {
    $customerName = $order['full_name'];
} elseif (!empty($order['first_name']) && !empty($order['last_name'])) {
    $customerName = $order['first_name'] . ' ' . $order['last_name'];
} elseif (!empty($order['customer_name'])) {
    $customerName = $order['customer_name'];
} elseif (!empty($order['name'])) {
    $customerName = $order['name'];
} else {
    $customerName = "Customer #" . ($order['user_id'] ?? $order['client_id'] ?? $order['customer_id'] ?? 'Unknown');
}

// Calculate order total
$orderTotal = 0;
foreach ($orderItems as $item) {
    $orderTotal += ($item['price'] * $item['quantity']);
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
    <title>Edit Order #<?= $orderId ?> - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/admin-theme.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/readability.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/table-readability.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../assets/css/form-readability.css?v=<?= time() ?>">
</head>
<body>
<!-- Admin Container -->
<div class="admin-container">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Include Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Content Area -->
        <div class="admin-content">
            <?php if ($successMessage): ?>
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

            <?php if ($errorMessage): ?>
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

            <!-- Page Header -->
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">Edit Order #<?= $orderId ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="orders.php">Orders</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Edit Order #<?= $orderId ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="orders.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-2"></i> Back to Orders
                    </a>
                </div>
            </div>

            <?php if ($order): ?>
                <!-- Order Edit Form -->
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Order Details -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-cart me-2"></i>Order Details</h5>
                            </div>
                            <div class="card-body">
                                <form action="edit_order.php?id=<?= $orderId ?>" method="post">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="order_id" class="form-label">Order ID</label>
                                                <input type="text" class="form-control" id="order_id" value="<?= htmlspecialchars($order['id']) ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label for="order_date" class="form-label">Order Date</label>
                                                <input type="text" class="form-control" id="order_date" value="<?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Order Status</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="pending" <?= ($order['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="processing" <?= ($order['status'] ?? '') === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                    <option value="completed" <?= ($order['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                    <option value="cancelled" <?= ($order['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="customer_name" class="form-label">Customer Name</label>
                                                <input type="text" class="form-control" id="customer_name" value="<?= htmlspecialchars($customerName) ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label for="customer_email" class="form-label">Customer Email</label>
                                                <input type="email" class="form-control" id="customer_email" value="<?= htmlspecialchars($order['email'] ?? 'No email provided') ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label for="payment_status" class="form-label">Payment Status</label>
                                                <select class="form-select" id="payment_status" name="payment_status">
                                                    <option value="pending" <?= ($order['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="paid" <?= ($order['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                                    <option value="failed" <?= ($order['payment_status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                                                    <option value="refunded" <?= ($order['payment_status'] ?? '') === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Order Notes</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-2"></i> Update Order
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-cart me-2"></i>Order Items</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Quantity</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($orderItems) > 0): ?>
                                                <?php foreach ($orderItems as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($item['product_image'])): ?>
                                                                    <img src="../../assets/images/products/<?= htmlspecialchars($item['product_image']) ?>"
                                                                         alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>"
                                                                         class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                                <?php else: ?>
                                                                    <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center"
                                                                         style="width: 40px; height: 40px;">
                                                                        <i class="bi bi-box"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <h6 class="mb-0"><?= htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']) ?></h6>
                                                                    <small class="text-muted">SKU: <?= htmlspecialchars($item['product_id']) ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>₱<?= number_format($item['price'], 2) ?></td>
                                                        <td><?= $item['quantity'] ?></td>
                                                        <td class="text-end">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4">
                                                        <i class="bi bi-cart-x text-muted" style="font-size: 2rem;"></i>
                                                        <p class="mt-2 mb-0">No items found for this order.</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                                <td class="text-end">₱<?= number_format($orderTotal, 2) ?></td>
                                            </tr>
                                            <?php if (isset($order['shipping_fee']) && $order['shipping_fee'] > 0): ?>
                                                <tr>
                                                    <td colspan="3" class="text-end"><strong>Shipping Fee:</strong></td>
                                                    <td class="text-end">₱<?= number_format($order['shipping_fee'], 2) ?></td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                                <td class="text-end fw-bold">₱<?= number_format(($order['total'] ?? $orderTotal), 2) ?></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Customer Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-person me-2"></i>Customer Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-light rounded-circle me-3 d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <i class="bi bi-person-circle" style="font-size: 1.5rem;"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?= htmlspecialchars($customerName) ?></h5>
                                        <p class="text-muted mb-0">
                                            <?= !empty($order['email']) ? htmlspecialchars($order['email']) : 'No email provided' ?>
                                        </p>
                                    </div>
                                </div>

                                <?php
                                // Check for phone number in different possible fields
                                $phoneNumber = '';
                                if (!empty($order['customer_phone'])) {
                                    $phoneNumber = $order['customer_phone'];
                                } elseif (!empty($order['phone'])) {
                                    $phoneNumber = $order['phone'];
                                } elseif (!empty($order['contact_number'])) {
                                    $phoneNumber = $order['contact_number'];
                                } elseif (!empty($order['mobile'])) {
                                    $phoneNumber = $order['mobile'];
                                }

                                if (!empty($phoneNumber)):
                                ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-2">Contact Information</h6>
                                        <p class="mb-1">
                                            <i class="bi bi-telephone me-2"></i>
                                            <?= htmlspecialchars($phoneNumber) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($order['shipping_address'])): ?>
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-2">Shipping Address</h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($order['billing_address']) && $order['billing_address'] !== $order['shipping_address']): ?>
                                    <div>
                                        <h6 class="text-muted mb-2">Billing Address</h6>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['billing_address'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-credit-card me-2"></i>Payment Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Payment Method</h6>
                                    <p class="mb-0">
                                        <?php
                                        $paymentMethod = $order['payment_method'] ?? 'Unknown';
                                        $paymentIcon = 'bi-credit-card';

                                        switch (strtolower($paymentMethod)) {
                                            case 'gcash':
                                                $paymentIcon = 'bi-wallet';
                                                break;
                                            case 'maya':
                                                $paymentIcon = 'bi-wallet';
                                                break;
                                            case 'cod':
                                            case 'cash on delivery':
                                                $paymentIcon = 'bi-cash';
                                                break;
                                            case 'credit card':
                                                $paymentIcon = 'bi-credit-card';
                                                break;
                                        }
                                        ?>
                                        <i class="bi <?= $paymentIcon ?> me-2"></i>
                                        <?= htmlspecialchars(ucfirst($paymentMethod)) ?>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Payment Status</h6>
                                    <?php
                                    $paymentStatus = $order['payment_status'] ?? 'pending';
                                    $statusClass = 'warning';

                                    switch ($paymentStatus) {
                                        case 'paid':
                                            $statusClass = 'success';
                                            break;
                                        case 'failed':
                                            $statusClass = 'danger';
                                            break;
                                        case 'refunded':
                                            $statusClass = 'info';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst($paymentStatus) ?>
                                    </span>
                                </div>

                                <?php if (!empty($order['transaction_id'])): ?>
                                    <div>
                                        <h6 class="text-muted mb-2">Transaction ID</h6>
                                        <p class="mb-0"><?= htmlspecialchars($order['transaction_id']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Order Timeline -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-clock-history me-2"></i>Order Timeline</h5>
                            </div>
                            <div class="card-body">
                                <ul class="timeline">
                                    <li class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <h6 class="mb-0">Order Placed</h6>
                                            <p class="text-muted mb-0 small">
                                                <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>
                                            </p>
                                        </div>
                                    </li>

                                    <?php if (($order['status'] ?? '') !== 'pending'): ?>
                                        <li class="timeline-item">
                                            <div class="timeline-marker bg-info"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-0">Order Processing</h6>
                                                <p class="text-muted mb-0 small">
                                                    <?= !empty($order['processing_date']) ? date('M d, Y h:i A', strtotime($order['processing_date'])) : 'Status updated' ?>
                                                </p>
                                            </div>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (($order['status'] ?? '') === 'completed'): ?>
                                        <li class="timeline-item">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-0">Order Completed</h6>
                                                <p class="text-muted mb-0 small">
                                                    <?= !empty($order['completed_date']) ? date('M d, Y h:i A', strtotime($order['completed_date'])) : 'Status updated' ?>
                                                </p>
                                            </div>
                                        </li>
                                    <?php endif; ?>

                                    <?php if (($order['status'] ?? '') === 'cancelled'): ?>
                                        <li class="timeline-item">
                                            <div class="timeline-marker bg-danger"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-0">Order Cancelled</h6>
                                                <p class="text-muted mb-0 small">
                                                    <?= !empty($order['cancelled_date']) ? date('M d, Y h:i A', strtotime($order['cancelled_date'])) : 'Status updated' ?>
                                                </p>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-exclamation-circle text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">Order Not Found</h4>
                    <p class="text-muted">The order you are looking for does not exist or has been deleted.</p>
                    <a href="orders.php" class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-left me-2"></i> Back to Orders
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include 'includes/footer-scripts.php'; ?>
<style>
    /* Timeline styles */
    .timeline {
        position: relative;
        padding-left: 30px;
        list-style: none;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 20px;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
    }
    .timeline-marker {
        position: absolute;
        left: -30px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
    }
    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: -23px;
        top: 15px;
        height: calc(100% - 15px);
        width: 2px;
        background-color: #e9ecef;
    }
</style>
</body>
</html>
