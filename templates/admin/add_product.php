<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../views/login.php");
    exit;
}
require_once "../includes/db.php";

if (isset($_POST['save'])) {
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;

    // Handle image upload
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $image = 'uploads/' . $fileName;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
    }

    $stmt = $conn->prepare("INSERT INTO products (name, description, category_id, price, image, stock, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $description, $category_id, $price, $image, $stock]);

    $_SESSION['success'] = "✅ Product added successfully!";
    header("Location: products.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Brew & Bake</title>
    <?php include 'includes/css-includes.php'; ?>
    <link rel="stylesheet" href="../../assets/css/admin-products.css">
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
                <!-- Include Page Header -->
                <?php include 'includes/page-header.php'; ?>

                <!-- Add Product Form -->
                <div class="row mb-5">
                    <div class="col-12">
                        <div class="card fade-in">
                            <div class="card-header">
                                <h5 class="card-title"><i class="bi bi-plus-circle"></i> Add New Product</h5>
                            </div>
                            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-tag"></i> Product Name
                        </label>
                        <input type="text" name="name" class="form-control" placeholder="Enter product name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-grid"></i> Category
                        </label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Select category</option>
                            <?php
                            // Fetch categories from database
                            try {
                                $categoryStmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
                                while ($category = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $category['id'] . '">' . ucfirst(htmlspecialchars($category['name'])) . '</option>';
                                }
                            } catch (PDOException $e) {
                                echo '<option value="">Error loading categories</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-currency-dollar"></i> Price (₱)
                        </label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="e.g. 120.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-box"></i> Stock
                        </label>
                        <input type="number" name="stock" class="form-control" placeholder="Enter stock quantity" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-card-text"></i> Description
                        </label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Write a short description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="bi bi-image"></i> Product Image
                        </label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="d-flex gap-2">
                        <button name="save" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Save Product
                        </button>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
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