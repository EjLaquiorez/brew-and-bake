<?php
session_start();
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../views/login.php");
    exit;
}
require_once "../includes/db.php";

if (isset($_POST['save'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Handle image upload
    $image = null;
    if ($_FILES['image']['name']) {
        $image = 'images/' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $_FILES['image']['name']);
    }

    $stmt = $conn->prepare("INSERT INTO products (name, description, category, price, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $description, $category, $price, $image]);

    header("Location: admin.php");
}
?>

<!-- The HTML Form -->
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="name" class="form-control mb-2" placeholder="Product Name" required>
    <select name="category" class="form-control mb-2" required>
        <option value="coffee">Coffee</option>
        <option value="pastry">Pastry</option>
    </select>
    <input type="number" step="0.01" name="price" class="form-control mb-2" placeholder="Price (₱)" required>
    <textarea name="description" class="form-control mb-2" placeholder="Description" rows="3"></textarea>
    <input type="file" name="image" class="form-control mb-2">
    <button name="save" class="btn btn-primary">Save Product</button>
</form>
