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
    <label for="name" class="form-label">Product Name</label>
    <input type="text" id="name" name="name" value="<?= $product['name'] ?>" class="form-control mb-2" placeholder="Enter product name" required>

    <label for="category" class="form-label">Category</label>
    <select id="category" name="category" class="form-control mb-2" required>
        <option value="" disabled selected>Select category</option>
        <option value="coffee" <?= $product['category'] === 'coffee' ? 'selected' : '' ?>>Coffee</option>
        <option value="pastry" <?= $product['category'] === 'pastry' ? 'selected' : '' ?>>Pastry</option>
    </select>

    <label for="price" class="form-label">Price</label>
    <input type="number" id="price" step="0.01" name="price" value="<?= $product['price'] ?>" class="form-control mb-2" placeholder="Enter product price" required>

    <label for="description" class="form-label">Description</label>
    <textarea id="description" name="description" class="form-control mb-2" placeholder="Enter product description" required><?= $product['description'] ?></textarea>

    <label for="image" class="form-label">Upload Image</label>
    <input type="file" id="image" name="image" class="form-control mb-2" placeholder="Upload product image">

    <button name="update" class="btn btn-success">Update Product</button>
    <a href="admin.php" class="btn btn-secondary">Cancel</a>
</form>

<link rel="stylesheet" href="../assets/css/edit_product.css">