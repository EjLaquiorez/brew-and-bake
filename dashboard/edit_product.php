<?php
session_start();
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../views/login.php");
    exit;
}
require_once "../includes/db.php";

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Handle image upload (update if new image is uploaded)
    $image = $product['image']; // Keep old image if none uploaded
    if ($_FILES['image']['name']) {
        $image = 'images/' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $_FILES['image']['name']);
    }

    $update = $conn->prepare("UPDATE products SET name=?, description=?, category=?, price=?, image=? WHERE id=?");
    $update->execute([$name, $description, $category, $price, $image, $id]);
    header("Location: admin.php");
}
?>

<!-- Edit Product Form -->
<form method="POST" enctype="multipart/form-data" class="edit-product-form">
    <input type="text" name="name" value="<?= $product['name'] ?>" class="form-control mb-2" required>
    <select name="category" class="form-control mb-2" required>
        <option value="coffee" <?= $product['category'] === 'coffee' ? 'selected' : '' ?>>Coffee</option>
        <option value="pastry" <?= $product['category'] === 'pastry' ? 'selected' : '' ?>>Pastry</option>
    </select>
    <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" class="form-control mb-2" required>
    <textarea name="description" class="form-control mb-2"><?= $product['description'] ?></textarea>
    <input type="file" name="image" class="form-control mb-2">
    <button name="update" class="btn btn-success">Update Product</button>
    <a href="admin.php" class="btn btn-secondary">Cancel</a>
</form>

<link rel="stylesheet" href="../assets/css/edit_product.css">