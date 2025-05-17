<?php
session_start();
require_once "../includes/auth.php";
require_once "../includes/db.php";

// Security check
if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: ../../views/login.php");
    exit;
}

// Initialize variables
$successMessage = '';
$errorMessage = '';

// Handle messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errorMessage = "Category name is required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            $successMessage = "Category created successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error creating category: " . $e->getMessage();
        }
    }
}

// Handle category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'] ?? 0;

    if (empty($id)) {
        $errorMessage = "Invalid category ID.";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $successMessage = "Category deleted successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Fetch categories
try {
    // Check if categories table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'categories'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Placeholder data for demonstration
        $categories = [
            [
                'id' => 1,
                'name' => 'Coffee',
                'description' => 'Various coffee drinks and beans',
                'product_count' => 8
            ],
            [
                'id' => 2,
                'name' => 'Pastries',
                'description' => 'Freshly baked pastries and bread',
                'product_count' => 12
            ],
            [
                'id' => 3,
                'name' => 'Cakes',
                'description' => 'Delicious cakes for all occasions',
                'product_count' => 6
            ],
            [
                'id' => 4,
                'name' => 'Sandwiches',
                'description' => 'Savory sandwiches and wraps',
                'product_count' => 5
            ],
            [
                'id' => 5,
                'name' => 'Beverages',
                'description' => 'Cold and hot beverages',
                'product_count' => 10
            ]
        ];
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching categories: " . $e->getMessage();
    $categories = [];
}

// Count products per category (placeholder)
foreach ($categories as &$category) {
    if (!isset($category['product_count'])) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
            $stmt->execute([$category['name']]);
            $category['product_count'] = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $category['product_count'] = 0;
        }
    }
}
unset($category);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Categories - Brew & Bake</title>
    <?php include 'includes/css-includes.php'; ?>
    <link rel="stylesheet" href="../../assets/css/admin-products.css">
</head>
<body>
<!-- Admin Container -->
<div class="admin-container">
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Include Topbar -->
        <?php include 'includes/topbar.php'; ?>

        <!-- Content Area -->
        <div class="admin-content">
            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-check-lg"></i>
                        </div>
                        <div class="alert-content">
                            <h6 class="alert-title">Success</h6>
                            <p class="alert-text"><?= htmlspecialchars($successMessage) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div class="alert-content">
                            <h6 class="alert-title">Error</h6>
                            <p class="alert-text"><?= htmlspecialchars($errorMessage) ?></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Include Page Header -->
            <?php include 'includes/page-header.php'; ?>

            <!-- Categories Management -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                        <div>
                            <h3 class="mb-1">Categories Management</h3>
                            <p class="text-muted mb-0">Organize your products with categories</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-lg me-2"></i> Add New Category
                        </button>
                    </div>
                </div>

                <div class="col-12">
                    <div class="row">
                        <?php foreach ($categories as $category): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card category-card fade-in">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="card-title mb-0"><?= htmlspecialchars($category['name']) ?></h5>
                                            <div class="category-badge">
                                                <span class="badge-primary"><?= $category['product_count'] ?> Products</span>
                                            </div>
                                        </div>
                                        <p class="card-text text-muted mb-4"><?= htmlspecialchars($category['description']) ?></p>
                                        <div class="d-flex justify-content-between">
                                            <a href="admin.php?category=<?= urlencode($category['name']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye me-1"></i> View Products
                                            </a>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                    data-id="<?= $category['id'] ?>"
                                                    data-name="<?= htmlspecialchars($category['name']) ?>"
                                                    data-description="<?= htmlspecialchars($category['description']) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal"
                                                    data-id="<?= $category['id'] ?>"
                                                    data-name="<?= htmlspecialchars($category['name']) ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($categories) === 0): ?>
                            <div class="col-12">
                                <div class="p-5 text-center">
                                    <div class="mb-4">
                                        <i class="bi bi-tags" style="font-size: 3rem; color: var(--color-gray-400);"></i>
                                    </div>
                                    <h4>No Categories Found</h4>
                                    <p class="text-muted mb-4">You haven't added any categories yet. Start by adding your first category!</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <i class="bi bi-plus-lg me-2"></i> Add New Category
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Category Statistics -->
            <div class="row mb-5">
                <div class="col-12 mb-4">
                    <h3 class="mb-4">Category Statistics</h3>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-left">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-pie-chart"></i> Product Distribution</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th>Products</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalProducts = array_sum(array_column($categories, 'product_count'));
                                        foreach ($categories as $category):
                                            $percentage = $totalProducts > 0 ? round(($category['product_count'] / $totalProducts) * 100) : 0;
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($category['name']) ?></td>
                                                <td><?= $category['product_count'] ?></td>
                                                <td>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <span class="small"><?= $percentage ?>%</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card fade-in-right">
                        <div class="card-header">
                            <h5 class="card-title"><i class="bi bi-lightning"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="bi bi-plus-lg me-2"></i> Add New Category
                                </button>
                                <a href="update_categories.php" class="btn btn-success">
                                    <i class="bi bi-arrow-repeat me-2"></i> Update to Brew & Bake Menu
                                </a>
                                <a href="products.php" class="btn btn-secondary">
                                    <i class="bi bi-box me-2"></i> Manage Products
                                </a>
                                <button class="btn btn-light">
                                    <i class="bi bi-download me-2"></i> Export Categories
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle text-primary me-2"></i>
                    Add New Category
                </h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form action="categories.php" method="post">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="categoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="categoryDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square text-primary me-2"></i>
                    Edit Category
                </h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form action="categories.php" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editCategoryId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="editCategoryName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editCategoryDescription" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle text-danger me-2"></i>
                    Confirm Delete
                </h5>
                <button type="button" class="modal-close" data-bs-dismiss="modal">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the category "<span id="deleteCategoryName"></span>"? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <div class="alert-icon">
                        <div class="alert-icon-symbol">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div class="alert-content">
                            <p class="alert-text mb-0">Products in this category will not be deleted, but they will no longer be associated with this category.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form action="categories.php" method="post" class="d-inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i> Delete Category
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer-scripts.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit category modal
        const editCategoryModal = document.getElementById('editCategoryModal');
        if (editCategoryModal) {
            editCategoryModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const description = button.getAttribute('data-description');

                document.getElementById('editCategoryId').value = id;
                document.getElementById('editCategoryName').value = name;
                document.getElementById('editCategoryDescription').value = description;
            });
        }

        // Delete category modal
        const deleteCategoryModal = document.getElementById('deleteCategoryModal');
        if (deleteCategoryModal) {
            deleteCategoryModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');

                document.getElementById('deleteCategoryId').value = id;
                document.getElementById('deleteCategoryName').textContent = name;
            });
        }
    });
</script>
</body>
</html>