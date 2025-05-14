<?php
session_start();
if ($_SESSION['user_role'] !== 'admin') {
    header("Location: ../../views/login.php");
    exit;
}
require_once "../includes/db.php";

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $price = $_POST['price'];
    $description = $_POST['description'];

    // Handle image upload (update if new image is uploaded)
    $image = $product['image']; // Keep old image if none uploaded
    if ($_FILES['image']['name']) {
        $image = 'images/' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $_FILES['image']['name']);
    }

    $update = $conn->prepare("UPDATE products SET name=?, description=?, category_id=?, price=?, image=? WHERE id=?");
    $update->execute([$name, $description, $category_id, $price, $image, $id]);
    header("Location: products.php");
}
?>

<!-- Edit Product Form -->
<form method="POST" enctype="multipart/form-data" class="edit-product-form">
    <label for="name" class="form-label">Product Name</label>
    <input type="text" id="name" name="name" value="<?= $product['name'] ?>" class="form-control mb-2" placeholder="Enter product name" required>

    <label for="category_id" class="form-label">Category</label>
    <select id="category_id" name="category_id" class="form-control mb-2" required>
        <option value="" disabled>Select category</option>
        <?php
        // Fetch categories from database
        try {
            $categoryStmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
            while ($category = $categoryStmt->fetch(PDO::FETCH_ASSOC)) {
                $selected = ($product['category_id'] == $category['id']) ? 'selected' : '';
                echo '<option value="' . $category['id'] . '" ' . $selected . '>' . ucfirst(htmlspecialchars($category['name'])) . '</option>';
            }
        } catch (PDOException $e) {
            echo '<option value="">Error loading categories</option>';
        }
        ?>
    </select>

    <label for="price" class="form-label">Price</label>
    <input type="number" id="price" step="0.01" name="price" value="<?= $product['price'] ?>" class="form-control mb-2" placeholder="Enter product price" required>

    <label for="description" class="form-label">Description</label>
    <textarea id="description" name="description" class="form-control mb-2" placeholder="Enter product description" required><?= $product['description'] ?></textarea>

    <label for="image" class="form-label">Upload Image</label>
    <input type="file" id="image" name="image" class="form-control mb-2" placeholder="Upload product image">

    <button name="update" class="btn btn-success">Update Product</button>
    <a href="products.php" class="btn btn-secondary">Cancel</a>
</form>

<link rel="stylesheet" href="../../assets/css/edit_product.css">