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
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Brew & Bake ☕</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="bi bi-receipt"></i> Orders
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="admin.php">
            <i class="bi bi-box-seam"></i> Products
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="bi bi-bar-chart-line"></i> Reports
          </a>
        </li>
        <!-- Settings Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="settingsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-gear"></i> Settings
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="settingsDropdown">
            <li><a class="dropdown-item" href="#">Profile Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>


<!-- Main Content -->
<div class="flex-grow-1 p-3">
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
