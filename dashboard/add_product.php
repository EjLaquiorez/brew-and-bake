<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../views/login.php");
    exit;
}
require_once "../includes/db.php";

if (isset($_POST['save'])) {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
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

    $stmt = $conn->prepare("INSERT INTO products (name, description, category, price, image, stock, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $description, $category, $price, $image, $stock]);

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/add_product.css">
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="products.php">
                <i class="bi bi-cup-hot"></i> Brew & Bake Admin
            </a>
            <a href="../logout.php" class="btn btn-outline-light">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-plus-circle"></i> Add New Product</h5>
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
                        <select name="category" class="form-select" required>
                            <option value="">Select category</option>
                            <option value="coffee">Coffee</option>
                            <option value="pastry">Pastry</option>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>