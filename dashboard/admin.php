<?php
session_start();
$successMessage = '';
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../views/login.php");
    exit;
}

require_once "../includes/db.php";
$stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Brew & Bake</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css"> <!-- Link to external CSS -->
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
            <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-bold" href="#">☕ Brew & Bake Admin</a>
    </div>
</nav>

<!-- Sidebar -->
<div class="offcanvas offcanvas-start bg-dark text-white offcanvas-lg" tabindex="-1" id="sidebar">
    <div class="offcanvas-header d-lg-none">
        <h5 class="offcanvas-title">Navigation</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
            <a href="admin.php" class="list-group-item list-group-item-action bg-dark text-white"><i class="bi bi-box-seam"></i> Manage Products</a>
            <a href="add_product.php" class="list-group-item list-group-item-action bg-dark text-white"><i class="bi bi-plus-circle"></i> Add Product</a>
            <a href="../logout.php" class="list-group-item list-group-item-action bg-dark text-white"><i class="bi bi-box-arrow-left"></i> Logout</a>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="content pt-5 mt-4 px-3">
    <?php if ($successMessage): ?>
        <div id="flash-alert" class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>📦 Manage Products</h3>
        <a href="add_product.php" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Add Product
        </a>
    </div>

    <?php if (count($products) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Price (₱)</th>
                        <th>Description</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                <?php foreach ($products as $prod): ?>
                    <tr>
                        <td><?= htmlspecialchars($prod['name']) ?></td>
                        <td><?= htmlspecialchars($prod['category']) ?></td>
                        <td><?= number_format($prod['price'], 2) ?></td>
                        <td><?= htmlspecialchars($prod['description']) ?></td>
                        <td>
                            <a href="edit_product.php?id=<?= $prod['id'] ?>" class="btn btn-warning btn-sm me-1">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="delete_product.php?id=<?= $prod['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No products found. Start by adding one!
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/scripts.js"></script> <!-- Link to external JS -->
</body>
</html>
