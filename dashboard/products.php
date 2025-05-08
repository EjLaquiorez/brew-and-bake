<?php
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Check if user is admin
if (!isAdmin()) {
    header("Location: ../views/login.php");
    exit();
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $productId = $_POST['product_id'];
    try {
        $stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$productId]);
        $success = "Product deleted successfully!";
    } catch (PDOException $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$query = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY created_at DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get unique categories for filter
    $catStmt = $conn->query("SELECT DISTINCT category FROM products ORDER BY category");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $products = [];
    $categories = [];
    $error = "Error fetching products: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Brew & Bake Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <?php include 'includes/topnav.php'; ?>

            <!-- Content -->
            <div class="content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3">Products Management</h1>
                        <a href="add_product.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Add New Product
                        </a>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="" method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" 
                                               placeholder="Search products..." 
                                               value="<?= htmlspecialchars($search) ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <select name="category" class="form-select" onchange="this.form.submit()">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat) ?>" 
                                                    <?= $category === $cat ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(ucfirst($cat)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="status" class="form-select" onchange="this.form.submit()">
                                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="" <?= $status === '' ? 'selected' : '' ?>>All Status</option>
                                    </select>
                                </div>
                                <?php if (!empty($category) || !empty($search) || !empty($status)): ?>
                                    <div class="col-md-2">
                                        <a href="products.php" class="btn btn-outline-secondary w-100">Clear Filters</a>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($products)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="bi bi-emoji-frown display-4 text-muted"></i>
                                                    <p class="mt-2">No products found</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($products as $product): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($product['image'])): ?>
                                                            <img src="../assets/images/products/<?= htmlspecialchars($product['image']) ?>" 
                                                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                                                 class="product-thumbnail">
                                                        <?php else: ?>
                                                            <div class="no-image">No Image</div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($product['description']) ?></small>
                                                    </td>
                                                    <td><?= htmlspecialchars(ucfirst($product['category'])) ?></td>
                                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                                    <td>
                                                        <span class="badge <?= $product['stock'] <= 5 ? 'bg-danger' : 'bg-success' ?>">
                                                            <?= $product['stock'] ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $product['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                                            <?= ucfirst($product['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="edit_product.php?id=<?= $product['id'] ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteModal<?= $product['id'] ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>

                                                        <!-- Delete Modal -->
                                                        <div class="modal fade" id="deleteModal<?= $product['id'] ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Confirm Delete</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Are you sure you want to delete "<?= htmlspecialchars($product['name']) ?>"?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <form action="" method="POST" class="d-inline">
                                                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                                            <button type="submit" name="delete_product" class="btn btn-danger">Delete</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html> 