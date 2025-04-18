<?php
session_start();
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../views/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - Brew & Bake</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>☕ Brew & Bake - Admin Dashboard</h2>
    <p>Welcome, Admin!</p>
    <a href="../logout.php" class="btn btn-danger btn-sm mb-3">Logout</a>

    <h4>📦 Manage Products</h4>
    <a href="add_product.php" class="btn btn-success btn-sm mb-3">+ Add New Product</a>

    <?php
    require_once "../includes/db.php";
    $stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Price (₱)</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($products as $prod): ?>
            <tr>
                <td><?= $prod['name'] ?></td>
                <td><?= $prod['type'] ?></td>
                <td><?= number_format($prod['price'], 2) ?></td>
                <td><?= $prod['description'] ?></td>
                <td>
                    <a href="edit_product.php?id=<?= $prod['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="delete_product.php?id=<?= $prod['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
