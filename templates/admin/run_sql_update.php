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

$message = '';
$alertType = '';

if (isset($_POST['run_sql'])) {
    try {
        // Check if there are transaction_items referencing products
        $checkTransactionItems = $conn->query("SHOW TABLES LIKE 'transaction_items'")->rowCount() > 0;

        if ($checkTransactionItems) {
            $checkReferences = $conn->query("SELECT COUNT(*) FROM transaction_items")->fetchColumn();
        } else {
            $checkReferences = 0;
        }

        // Read the SQL file
        $sqlFile = file_get_contents("../includes/update_products.sql");

        // If there are transaction items, we need to be careful
        if ($checkReferences > 0) {
            $message = "Found transaction items referencing products. Using safer update method.<br>";

            // Begin transaction for safety
            $conn->beginTransaction();

            // Get existing product IDs
            $existingProducts = $conn->query("SELECT id FROM products ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
            $productCount = count($existingProducts);

            if ($productCount > 0) {
                $message .= "Found $productCount existing products to update.<br>";

                // First, update existing products
                $updateStmt = $conn->prepare("
                    UPDATE products
                    SET name = ?, description = ?, category = ?, price = ?, stock = ?, image = ?, status = 'active'
                    WHERE id = ?
                ");

                // Define our new products (first few as examples)
                $newProducts = [
                    ['Single Origin Ethiopian Yirgacheffe', 'Bright and fruity with notes of blueberry, citrus, and floral undertones. Sourced directly from small-scale farmers in Ethiopia.', 'coffee', 195.00, 50, 'ethiopian_coffee.jpg', 'active'],
                    ['Specialty Cold Brew', 'Smooth, low-acidity cold brew steeped for 18 hours with hints of chocolate and caramel. Served with your choice of milk or black.', 'coffee', 175.00, 35, 'cold_brew.jpg', 'active'],
                    ['Nitro Coffee', 'Our signature cold brew infused with nitrogen for a creamy, stout-like texture with a beautiful cascading effect.', 'coffee', 210.00, 25, 'nitro_coffee.jpg', 'active'],
                    ['Barako Espresso', 'Traditional Filipino Liberica coffee with a bold, earthy flavor profile. Perfect as a strong espresso shot.', 'coffee', 120.00, 60, 'barako_espresso.jpg', 'active'],
                    ['Oat Milk Latte', 'Creamy plant-based latte made with premium oat milk and a double shot of our house blend espresso.', 'coffee', 185.00, 40, 'oat_latte.jpg', 'active']
                ];

                // Update existing products
                $updateCount = 0;
                for ($i = 0; $i < min($productCount, count($newProducts)); $i++) {
                    $product = $newProducts[$i];
                    $updateStmt->execute([
                        $product[0], // name
                        $product[1], // description
                        $product[2], // category
                        $product[3], // price
                        $product[4], // stock
                        $product[5], // image
                        $existingProducts[$i] // id
                    ]);
                    $updateCount++;
                }

                $message .= "Updated $updateCount existing products.<br>";

                // For remaining products, we'll use the SQL INSERT statements
                // But we'll skip the first few that we've already updated
                $sqlStatements = explode(';', $sqlFile);

                // Find the INSERT statements
                $insertStatements = [];
                foreach ($sqlStatements as $statement) {
                    if (stripos($statement, 'INSERT INTO products') !== false) {
                        $insertStatements[] = $statement;
                    }
                }

                // Execute only the INSERT statements we need
                if (count($insertStatements) > 0) {
                    // Skip the first INSERT statement which contains the products we've already updated
                    $skipCount = ceil($updateCount / 5); // Assuming about 5 products per INSERT statement
                    $insertCount = 0;

                    for ($i = $skipCount; $i < count($insertStatements); $i++) {
                        $statement = trim($insertStatements[$i]);
                        if (!empty($statement)) {
                            $conn->exec($statement);
                            $insertCount++;
                        }
                    }

                    $message .= "Inserted additional products using $insertCount SQL statements.<br>";
                }

                // Commit the transaction
                $conn->commit();
                $message .= "All changes committed successfully!";
                $alertType = 'success';
            } else {
                $message .= "No existing products found. Proceeding with normal inserts.<br>";
                // Fall through to normal execution
            }
        }

        // If we didn't handle it above with the special case, do normal execution
        if ($alertType != 'success') {
            // Split SQL file into individual statements
            $sqlStatements = explode(';', $sqlFile);

            // Execute each statement
            $successCount = 0;
            foreach ($sqlStatements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && strpos($statement, '/*') === false && strpos($statement, '--') !== 0) {
                    $conn->exec($statement);
                    $successCount++;
                }
            }

            $message = "SQL script executed successfully! $successCount statements processed.";
            $alertType = 'success';
        }

    } catch (PDOException $e) {
        // If a transaction is active, roll it back
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $message = "Error executing SQL: " . $e->getMessage();
        $alertType = 'danger';
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
    <title>Run SQL Update - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/default.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/languages/sql.min.js"></script>
</head>
<body>
<!-- Admin Container -->
<div class="admin-container">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <div class="sidebar-logo">
                    <i class="bi bi-cup-hot"></i>
                </div>
                <div>
                    <h3 class="sidebar-title">Brew & Bake</h3>
                    <p class="sidebar-subtitle">Admin Dashboard</p>
                </div>
            </a>
            <button class="sidebar-close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sidebar-nav">
            <div class="nav-section">
                <h6 class="nav-section-title">Main</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="bi bi-speedometer2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link">
                            <i class="bi bi-receipt"></i>
                            Orders
                            <span class="nav-badge">5</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products.php" class="nav-link active">
                            <i class="bi bi-box-seam"></i>
                            Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link">
                            <i class="bi bi-tags"></i>
                            Categories
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h6 class="nav-section-title">Analytics</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="analytics.php" class="nav-link">
                            <i class="bi bi-bar-chart-line"></i>
                            Analytics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="sales.php" class="nav-link">
                            <i class="bi bi-graph-up"></i>
                            Sales
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h6 class="nav-section-title">Settings</h6>
                <ul class="nav-items">
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link">
                            <i class="bi bi-person"></i>
                            Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="bi bi-gear"></i>
                            System Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <?php include 'includes/sidebar-user-menu.php'; ?>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Include Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Content Area -->
        <div class="admin-content">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="page-title">Run SQL Update</h2>
                            <div>
                                <a href="update_products.php" class="btn btn-outline-primary me-2">
                                    <i class="bi bi-arrow-repeat"></i> PHP Update
                                </a>
                                <a href="products.php" class="btn btn-outline-primary">
                                    <i class="bi bi-arrow-left"></i> Back to Products
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show" role="alert">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-database"></i> SQL Product Update</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h5><i class="bi bi-info-circle"></i> About This SQL Update</h5>
                                    <p>This will execute an SQL script to update your product catalog with modern coffee shop and bakery items. The script will:</p>
                                    <ul>
                                        <li>Create the products table if it doesn't exist</li>
                                        <li>Clear any existing products</li>
                                        <li>Insert 25 new products across 5 categories</li>
                                    </ul>
                                    <p class="mb-0"><strong>Note:</strong> This will replace any existing products in your database.</p>
                                </div>

                                <div class="mt-4 mb-4">
                                    <h6>SQL Preview:</h6>
                                    <div class="code-preview p-3 bg-light rounded" style="max-height: 300px; overflow-y: auto;">
                                        <pre><code class="language-sql"><?= htmlspecialchars(file_get_contents("../includes/update_products.sql")) ?></code></pre>
                                    </div>
                                </div>

                                <form method="POST" class="mt-4">
                                    <div class="d-grid">
                                        <button type="submit" name="run_sql" class="btn btn-primary btn-lg">
                                            <i class="bi bi-database-fill-up"></i> Execute SQL Script
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer-scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize syntax highlighting
        document.querySelectorAll('pre code').forEach((block) => {
            hljs.highlightElement(block);
        });
    });
</script>
</body>
</html>
