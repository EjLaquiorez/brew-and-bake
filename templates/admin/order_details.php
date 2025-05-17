<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    echo json_encode(['error' => 'Access denied. Admin privileges required.']);
    exit;
}

// Check if order ID is provided
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$orderId) {
    echo json_encode(['error' => 'Invalid order ID.']);
    exit;
}

try {
    // Check if orders table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo json_encode(['error' => 'Orders table does not exist.']);
        exit;
    }

    // Check if users table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $usersTableExists = $stmt->rowCount() > 0;

    // Determine the join condition based on available columns
    $joinCondition = "";
    if ($usersTableExists) {
        $stmt = $conn->prepare("DESCRIBE orders");
        $stmt->execute();
        $orderColumns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

        if (in_array('user_id', $orderColumns)) {
            $joinCondition = "o.user_id = u.id";
        } elseif (in_array('client_id', $orderColumns)) {
            $joinCondition = "o.client_id = u.id";
        } elseif (in_array('customer_id', $orderColumns)) {
            $joinCondition = "o.customer_id = u.id";
        }
    }

    // Fetch order details
    if ($usersTableExists && $joinCondition) {
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

        $stmt = $conn->prepare("
            SELECT o.*$userSelectClause
            FROM orders o
            LEFT JOIN users u ON $joinCondition
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$order) {
        echo json_encode(['error' => 'Order not found.']);
        exit;
    }

    // Fetch order items
    $orderItems = [];
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

    // Prepare response HTML
    ob_start();
?>
<div class="order-details">
    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="text-muted mb-2">Order Information</h6>
            <p class="mb-1"><strong>Order ID:</strong> #<?= htmlspecialchars($order['id']) ?></p>
            <p class="mb-1"><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></p>
            <p class="mb-1">
                <strong>Status:</strong>
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
                <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($status) ?></span>
            </p>
            <p class="mb-0">
                <strong>Payment Method:</strong>
                <?= htmlspecialchars(ucfirst($order['payment_method'] ?? 'Unknown')) ?>
            </p>
        </div>
        <div class="col-md-6">
            <h6 class="text-muted mb-2">Customer Information</h6>
            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($customerName) ?></p>
            <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($order['email'] ?? 'No email provided') ?></p>
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
                <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($phoneNumber) ?></p>
            <?php endif; ?>
            <?php if (!empty($order['shipping_address'])): ?>
                <p class="mb-0"><strong>Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <h6 class="text-muted mb-3">Order Items</h6>
    <div class="table-responsive mb-4">
        <table class="table table-sm">
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
                            <td><?= htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']) ?></td>
                            <td>₱<?= number_format($item['price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td class="text-end">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No items found for this order.</td>
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

    <?php if (!empty($order['notes'])): ?>
        <h6 class="text-muted mb-3">Order Notes</h6>
        <p class="mb-0"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
    <?php endif; ?>
</div>
<?php
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error fetching order details: ' . $e->getMessage()]);
}
?>
