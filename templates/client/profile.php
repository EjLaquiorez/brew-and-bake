<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn()) {
    $_SESSION['error'] = "Please log in to access your account settings.";
    header("Location: ../../index.php");
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
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching user data: " . $e->getMessage();
    $user = [];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validate inputs
    if (empty($name) || empty($email)) {
        $errorMessage = "Name and email are required fields.";
    } else {
        try {
            // Check if email already exists for another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->rowCount() > 0) {
                $errorMessage = "Email already in use by another account.";
            } else {
                // Update user profile
                $stmt = $conn->prepare("
                    UPDATE users
                    SET name = ?, email = ?, phone = ?, address = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $email, $phone, $address, $userId]);

                $successMessage = "Profile updated successfully!";

                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $errorMessage = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = "All password fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "New passwords do not match.";
    } elseif (strlen($newPassword) < 8) {
        $errorMessage = "New password must be at least 8 characters long.";
    } else {
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $storedPassword = $stmt->fetchColumn();

            if (!password_verify($currentPassword, $storedPassword)) {
                $errorMessage = "Current password is incorrect.";
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $userId]);

                $successMessage = "Password changed successfully!";
            }
        } catch (PDOException $e) {
            $errorMessage = "Error changing password: " . $e->getMessage();
        }
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
    <link rel="stylesheet" href="../../assets/css/menu.css?v=<?= time() ?>">
    <style>
        /* Account settings specific styles */
        .account-section {
            padding: 2rem 0;
        }

        .account-header {
            margin-bottom: 2rem;
        }

        .account-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.5rem;
        }

        .account-subtitle {
            color: var(--color-gray-600);
            font-size: 1rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .card-header {
            background-color: var(--color-white);
            border-bottom: 1px solid var(--color-gray-200);
            padding: 1.25rem 1.5rem;
        }

        .card-header h2 {
            font-size: 1.25rem;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            color: var(--color-primary);
        }

        .card-header h2 i {
            margin-right: 0.75rem;
            color: var(--color-secondary);
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--color-gray-700);
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: var(--color-primary-dark);
            border-color: var(--color-primary-dark);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .badge {
            padding: 0.35em 0.65em;
            font-weight: 600;
            border-radius: 6px;
        }

        /* Header and menu navigation styling */
        .site-header {
            background-color: var(--color-primary);
            position: relative;
            z-index: 99; /* Lower than menu-nav */
        }

        .menu-nav {
            position: sticky;
            top: 0;
            z-index: 101; /* Higher than site-header */
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            background-color: var(--color-white);
            border-bottom: 1px solid var(--color-gray-200);
        }

        .menu-tabs {
            display: flex;
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .menu-tabs li {
            margin: 0;
        }

        .menu-tabs a {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--color-gray-700);
            padding: 1rem 1.5rem;
            display: block;
            position: relative;
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .menu-tabs a:hover {
            color: var(--color-primary);
        }

        .menu-tabs a.active {
            color: var(--color-primary);
            font-weight: 700;
        }

        .menu-tabs a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--color-secondary);
            border-radius: 4px 4px 0 0;
        }

        /* User dropdown styling */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: var(--color-primary-light);
            box-shadow: var(--shadow-md);
            border-radius: var(--radius-md);
            width: 200px;
            padding: 0.5rem 0;
            display: none;
            z-index: 10;
        }

        .user-dropdown.show {
            display: block;
        }

        .user-dropdown ul {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .user-dropdown li a {
            display: block;
            padding: 0.75rem 1rem;
            transition: background-color 0.3s ease;
            color: var(--color-gray-300);
            text-decoration: none;
        }

        .user-dropdown li a:hover,
        .user-dropdown li a.active {
            background-color: var(--color-primary-dark);
            color: var(--color-white);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="../../index.php">
                        <i class="bi bi-cup-hot"></i> Brew & Bake
                    </a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="client.php" class="active">MENU</a></li>
                        <li><a href="client.php#about">ABOUT</a></li>
                        <li><a href="client.php#contact">CONTACT</a></li>
                    </ul>
                </nav>
                <div class="header-actions">
                    <a href="cart.php" class="cart-icon">
                        <i class="bi bi-cart"></i>
                    </a>
                    <a href="#" class="user-icon">
                        <i class="bi bi-person-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Menu Navigation -->
    <div class="menu-nav">
        <div class="container">
            <ul class="menu-tabs">
                <li><a href="client.php">Menu</a></li>
                <li><a href="profile.php" class="active">My Orders</a></li>
            </ul>
        </div>
    </div>

    <!-- User Dropdown Menu -->
    <div class="user-dropdown">
        <ul>
            <li><a href="profile.php" class="active">My Orders</a></li>
            <li><a href="../includes/logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="container">
        <section class="account-section">
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

            <div class="account-header">
                <h1 class="account-title">My Orders</h1>
                <p class="account-subtitle">View and track your order history</p>
            </div>

            <div class="row">
                <!-- Order History -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="bi bi-bag"></i> Order History</h2>
                        </div>
                        <div class="card-body">
                            <?php
                            // Fetch user's orders
                            try {
                                // Check if orders table exists
                                $stmt = $conn->prepare("SHOW TABLES LIKE 'orders'");
                                $stmt->execute();
                                $tableExists = $stmt->rowCount() > 0;

                                if ($tableExists) {
                                    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
                                    $stmt->execute([$userId]);
                                    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } else {
                                    // Placeholder data for demonstration
                                    $orders = [
                                        [
                                            'id' => 1001,
                                            'total' => 850.00,
                                            'status' => 'completed',
                                            'created_at' => '2025-06-15 14:30:45',
                                            'items' => 3
                                        ],
                                        [
                                            'id' => 1002,
                                            'total' => 1250.75,
                                            'status' => 'processing',
                                            'created_at' => '2025-06-16 09:15:22',
                                            'items' => 5
                                        ],
                                        [
                                            'id' => 1003,
                                            'total' => 450.50,
                                            'status' => 'pending',
                                            'created_at' => '2025-06-16 16:45:10',
                                            'items' => 2
                                        ]
                                    ];
                                }
                            } catch (PDOException $e) {
                                $errorMessage = "Error fetching orders: " . $e->getMessage();
                                $orders = [];
                            }
                            ?>

                            <?php if (count($orders) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <span class="fw-bold">#<?= htmlspecialchars($order['id']) ?></span>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <div class="fw-medium"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
                                                            <div class="text-muted small"><?= date('h:i A', strtotime($order['created_at'])) ?></div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold">₱<?= number_format($order['total'], 2) ?></span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $statusClass = '';
                                                            switch ($order['status']) {
                                                                case 'completed': $statusClass = 'success'; break;
                                                                case 'processing': $statusClass = 'info'; break;
                                                                case 'cancelled': $statusClass = 'danger'; break;
                                                                default: $statusClass = 'warning';
                                                            }
                                                        ?>
                                                        <span class="badge bg-<?= $statusClass ?>">
                                                            <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="#" class="btn btn-sm btn-primary view-order" data-order-id="<?= $order['id'] ?>">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="mb-3">
                                        <i class="bi bi-bag-x" style="font-size: 3rem; color: var(--color-gray-400);"></i>
                                    </div>
                                    <h3 class="text-muted">No Orders Yet</h3>
                                    <p class="text-muted">You haven't placed any orders yet.</p>
                                    <a href="client.php" class="btn btn-primary mt-3">
                                        <i class="bi bi-cup-hot"></i> Browse Menu
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Details Modal -->
                <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="order-details-content">
                                    <!-- Order details will be loaded here -->
                                    <div class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <i class="bi bi-cup-hot"></i> Brew & Bake
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>About Us</h4>
                        <ul>
                            <li><a href="client.php#about">Our Story</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Social Impact</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Customer Service</h4>
                        <ul>
                            <li><a href="client.php#contact">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Store Locator</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="../../index.php">Home</a></li>
                            <li><a href="client.php">Menu</a></li>
                            <li><a href="orders.php">My Orders</a></li>
                            <li><a href="profile.php">Account Settings</a></li>
                            <li><a href="../includes/logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Brew & Bake. All rights reserved.</p>
                <div class="social-links">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle user dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const userIcon = document.querySelector('.user-icon');
            const userDropdown = document.querySelector('.user-dropdown');

            if (userIcon && userDropdown) {
                userIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    userDropdown.classList.toggle('show');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userIcon.contains(e.target) && !userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('show');
                    }
                });
            }

            // Make menu-nav sticky on scroll
            const menuNav = document.querySelector('.menu-nav');
            const siteHeader = document.querySelector('.site-header');

            if (menuNav && siteHeader) {
                // Get the height of the site header
                const headerHeight = siteHeader.offsetHeight;

                // Initial check on page load
                if (window.scrollY > headerHeight) {
                    menuNav.classList.add('scrolled');
                }

                // Check on scroll
                window.addEventListener('scroll', function() {
                    if (window.scrollY > headerHeight) {
                        menuNav.classList.add('scrolled');
                    } else {
                        menuNav.classList.remove('scrolled');
                    }
                });
            }

            // Order details modal
            const orderModal = document.getElementById('orderDetailsModal');
            if (orderModal) {
                const modal = new bootstrap.Modal(orderModal);

                // View order button click
                document.querySelectorAll('.view-order').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const orderId = this.getAttribute('data-order-id');
                        const modalContent = document.querySelector('.order-details-content');

                        // Show modal with loading spinner
                        modal.show();

                        // Simulate loading order details (in a real app, this would be an AJAX call)
                        setTimeout(() => {
                            // Sample order details HTML
                            const orderDetails = `
                                <div class="order-header mb-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h4 class="mb-0">Order #${orderId}</h4>
                                        <span class="badge bg-success">Completed</span>
                                    </div>
                                    <p class="text-muted mb-0">Placed on June 15, 2025 at 2:30 PM</p>
                                </div>

                                <div class="order-items mb-4">
                                    <h5 class="border-bottom pb-2 mb-3">Order Items</h5>
                                    <div class="order-item d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-cup-hot fs-3"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Kapeng Barako</h6>
                                                <p class="text-muted mb-0">Large, Hot</p>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <p class="mb-0">₱150.00 × 2</p>
                                            <p class="fw-bold mb-0">₱300.00</p>
                                        </div>
                                    </div>
                                    <div class="order-item d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-cake2 fs-3"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Ube Cheese Pandesal</h6>
                                                <p class="text-muted mb-0">Box of 6</p>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <p class="mb-0">₱250.00 × 1</p>
                                            <p class="fw-bold mb-0">₱250.00</p>
                                        </div>
                                    </div>
                                    <div class="order-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="bi bi-cup fs-3"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">Tablea Hot Chocolate</h6>
                                                <p class="text-muted mb-0">Medium, Extra Sweet</p>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <p class="mb-0">₱180.00 × 1</p>
                                            <p class="fw-bold mb-0">₱180.00</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="order-summary mb-4">
                                    <h5 class="border-bottom pb-2 mb-3">Order Summary</h5>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal</span>
                                        <span>₱730.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Delivery Fee</span>
                                        <span>₱120.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total</span>
                                        <span>₱850.00</span>
                                    </div>
                                </div>

                                <div class="delivery-info">
                                    <h5 class="border-bottom pb-2 mb-3">Delivery Information</h5>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <p class="text-muted mb-1">Delivery Address</p>
                                            <p class="mb-0">123 Coffee Street, Manila, Philippines</p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <p class="text-muted mb-1">Contact Number</p>
                                            <p class="mb-0">+63 912 345 6789</p>
                                        </div>
                                    </div>
                                </div>
                            `;

                            // Update modal content
                            modalContent.innerHTML = orderDetails;
                        }, 1000);
                    });
                });
            }
        });
    </script>
</body>
</html>
