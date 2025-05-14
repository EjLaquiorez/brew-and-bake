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

if (isset($_POST['update_menu'])) {
    try {
        // Check if there are transaction_items referencing products
        $checkTransactionItems = $conn->query("SHOW TABLES LIKE 'transaction_items'")->rowCount() > 0;
        
        if ($checkTransactionItems) {
            $checkReferences = $conn->query("SELECT COUNT(*) FROM transaction_items")->fetchColumn();
        } else {
            $checkReferences = 0;
        }
        
        // Read the SQL file
        $sqlFile = file_get_contents("../includes/update_brew_bake_products.sql");
        
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
                    ['Espresso', 'A concentrated shot of coffee served in a small cup, offering a rich, intense flavor with a layer of crema on top.', 'coffee', 150.00, 100, 'espresso.jpg', 'active'],
                    ['Americano', 'Espresso diluted with hot water, creating a coffee similar in strength to regular drip coffee but with a different flavor profile.', 'coffee', 160.00, 100, 'americano.jpg', 'active'],
                    ['Caffè Latte', 'Espresso with steamed milk and a small layer of milk foam, creating a creamy, mild coffee experience.', 'coffee', 180.00, 100, 'latte.jpg', 'active'],
                    ['Cappuccino', 'Equal parts espresso, steamed milk, and milk foam, offering a perfect balance of strong coffee flavor and creamy texture.', 'coffee', 180.00, 100, 'cappuccino.jpg', 'active'],
                    ['Caramel Macchiato', 'Vanilla-flavored milk marked with espresso and topped with caramel drizzle, creating a sweet, layered coffee treat.', 'coffee', 200.00, 80, 'caramel_macchiato.jpg', 'active']
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
    <title>Update Menu - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
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
                            <h2 class="page-title">Update Brew & Bake Menu</h2>
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left"></i> Back to Products
                            </a>
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
                                <h5 class="card-title"><i class="bi bi-cup-hot"></i> Update Brew & Bake Menu</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h5><i class="bi bi-info-circle"></i> About This Update</h5>
                                    <p>This will update your product catalog with the official Brew & Bake menu items including:</p>
                                    
                                    <h6 class="mt-3"><i class="bi bi-cup"></i> Signature Coffee</h6>
                                    <ul class="mb-3">
                                        <li>Espresso (₱150), Americano (₱160), Caffè Latte (₱180)</li>
                                        <li>Cappuccino (₱180), Caramel Macchiato (₱200), Mocha Latte (₱200)</li>
                                        <li>White Chocolate Mocha (₱220), Flat White (₱210)</li>
                                        <li>Iced Shaken Espresso (₱190), Cold Brew (₱170)</li>
                                    </ul>
                                    
                                    <h6><i class="bi bi-cup-straw"></i> Iced and Blended</h6>
                                    <ul class="mb-3">
                                        <li>Iced Americano (₱160), Iced Latte (₱180), Iced Caramel Macchiato (₱200)</li>
                                        <li>Java Chip Frappe (₱250), Mocha Frappe (₱240), Caramel Frappe (₱240)</li>
                                        <li>Matcha Green Tea Frappe (₱250), Strawberries & Cream Frappe (₱260)</li>
                                        <li>Cookies & Cream Frappe (₱250), Ube Frappe (₱260)</li>
                                    </ul>
                                    
                                    <h6><i class="bi bi-egg"></i> Pastries</h6>
                                    <ul class="mb-0">
                                        <li>Classic Croissant (₱80), Chocolate Croissant (₱100), Cheese Danish (₱120)</li>
                                        <li>Banana Bread Slice (₱150), Blueberry Muffin (₱130)</li>
                                        <li>Chocolate Chip Cookie (₱90), Ube Cheese Pandesal (₱60)</li>
                                        <li>Ensaymada (₱70), Cheese Roll (₱60), Cinnamon Roll (₱140)</li>
                                    </ul>
                                    
                                    <p class="mt-3 mb-0"><strong>Note:</strong> This will replace any existing products in your database.</p>
                                </div>
                                
                                <form method="POST" class="mt-4">
                                    <div class="d-grid">
                                        <button type="submit" name="update_menu" class="btn btn-primary btn-lg">
                                            <i class="bi bi-arrow-repeat"></i> Update Brew & Bake Menu
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
</body>
</html>
