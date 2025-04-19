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

    // Handle image upload
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../uploads/';
        $fileName = basename($_FILES['image']['name']);
        $image = 'images/' . $fileName;
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName);
    }

    $stmt = $conn->prepare("INSERT INTO products (name, description, category, price, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $category, $price, $image]);

    session_start();
    $_SESSION['success'] = "✅ Product added successfully!";
    header("Location: admin.php");
    exit;

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Product - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>

    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin.php">☕ Brew & Bake Admin</a>
            <a href="../logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Product</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">📛 Product Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter product name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">📂 Category</label>
                        <select name="category" class="form-select" required>
                            <option value="coffee">Coffee</option>
                            <option value="pastry">Pastry</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">💸 Price (₱)</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="e.g. 120.00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">📝 Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Write a short description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">🖼️ Product Image</label>
                        <input type="file" name="image" class="form-control">
                    </div>
                    <button name="save" class="btn btn-success"><i class="bi bi-check-circle"></i> Save Product</button>
                    <a href="admin.php" class="btn btn-secondary ms-2"><i class="bi bi-arrow-left"></i> Cancel</a>
                </form>
            </div>
        </div>
    </div>

</body>

</html>