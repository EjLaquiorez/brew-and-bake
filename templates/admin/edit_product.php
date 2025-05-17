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
$formData = [
    'id' => 0,
    'name' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'category_id' => '',
    'status' => 'active',
    'image' => ''
];
$errors = [];

// Handle messages
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Get product ID from URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$productId) {
    $_SESSION['error'] = "Invalid product ID.";
    header("Location: products.php");
    exit;
}

// Fetch all categories
try {
    $stmt = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Error fetching categories: " . $e->getMessage();
    $categories = [];
}

// Fetch product data
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: products.php");
        exit;
    }

    $formData = [
        'id' => $product['id'],
        'name' => $product['name'],
        'description' => $product['description'] ?? '',
        'price' => $product['price'],
        'stock' => $product['stock'],
        'category_id' => $product['category_id'],
        'status' => $product['status'],
        'image' => $product['image'] ?? ''
    ];
} catch (PDOException $e) {
    $errorMessage = "Error fetching product: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'id' => $productId,
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'stock' => intval($_POST['stock'] ?? 0),
        'category_id' => intval($_POST['category_id'] ?? 0),
        'status' => $_POST['status'] ?? 'active',
        'image' => $product['image'] ?? '' // Keep existing image by default
    ];

    // Validate form data
    if (empty($formData['name'])) {
        $errors['name'] = "Product name is required.";
    }

    if ($formData['price'] <= 0) {
        $errors['price'] = "Price must be greater than zero.";
    }

    if ($formData['stock'] < 0) {
        $errors['stock'] = "Stock cannot be negative.";
    }

    if (empty($formData['category_id'])) {
        $errors['category_id'] = "Please select a category.";
    }

    // If no errors, proceed with saving
    if (empty($errors)) {
        try {
            // Handle image upload
            $imageName = $formData['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = "../../assets/images/products/";

                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Get file info
                $fileName = $_FILES['image']['name'];
                $fileSize = $_FILES['image']['size'];
                $fileTmp = $_FILES['image']['tmp_name'];
                $fileType = $_FILES['image']['type'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Allowed extensions
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

                // Validate file
                if (in_array($fileExt, $allowedExts) && $fileSize < 2097152) { // 2MB max
                    // Generate unique filename
                    $imageName = uniqid() . '.' . $fileExt;
                    $uploadPath = $uploadDir . $imageName;

                    // Move uploaded file
                    if (move_uploaded_file($fileTmp, $uploadPath)) {
                        // Resize image if needed
                        if (function_exists('imagecreatefromjpeg')) {
                            $maxWidth = 800;
                            $maxHeight = 800;

                            list($width, $height) = getimagesize($uploadPath);

                            if ($width > $maxWidth || $height > $maxHeight) {
                                $ratio = min($maxWidth / $width, $maxHeight / $height);
                                $newWidth = $width * $ratio;
                                $newHeight = $height * $ratio;

                                $srcImage = null;
                                switch ($fileExt) {
                                    case 'jpg':
                                    case 'jpeg':
                                        $srcImage = imagecreatefromjpeg($uploadPath);
                                        break;
                                    case 'png':
                                        $srcImage = imagecreatefrompng($uploadPath);
                                        break;
                                    case 'gif':
                                        $srcImage = imagecreatefromgif($uploadPath);
                                        break;
                                }

                                if ($srcImage) {
                                    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
                                    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                                    switch ($fileExt) {
                                        case 'jpg':
                                        case 'jpeg':
                                            imagejpeg($dstImage, $uploadPath, 90);
                                            break;
                                        case 'png':
                                            imagepng($dstImage, $uploadPath, 9);
                                            break;
                                        case 'gif':
                                            imagegif($dstImage, $uploadPath);
                                            break;
                                    }

                                    imagedestroy($srcImage);
                                    imagedestroy($dstImage);
                                }
                            }
                        }

                        // Delete old image if it exists
                        if (!empty($product['image']) && $product['image'] !== $imageName) {
                            $oldImagePath = $uploadDir . $product['image'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                    } else {
                        $errors['image'] = "Failed to upload image.";
                    }
                } else {
                    $errors['image'] = "Invalid file. Please upload a JPG, JPEG, PNG, or GIF file under 2MB.";
                }
            }

            // If still no errors, update database
            if (empty($errors)) {
                $stmt = $conn->prepare("
                    UPDATE products
                    SET name = ?, description = ?, price = ?, stock = ?, category_id = ?, status = ?, image = ?, updated_at = NOW()
                    WHERE id = ?
                ");

                $stmt->execute([
                    $formData['name'],
                    $formData['description'],
                    $formData['price'],
                    $formData['stock'],
                    $formData['category_id'],
                    $formData['status'],
                    $imageName,
                    $productId
                ]);

                $_SESSION['success'] = "Product updated successfully.";
                header("Location: products.php");
                exit;
            }
        } catch (PDOException $e) {
            $errorMessage = "Error updating product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Edit Product - Brew & Bake</title>
    <?php include 'includes/css-includes.php'; ?>
    <link rel="stylesheet" href="../../assets/css/admin-products.css">
    <style>
        .image-preview-container {
            width: 100%;
            height: 200px;
            border: 2px dashed var(--color-gray-300);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            background-color: var(--color-gray-100);
            transition: all 0.3s ease;
        }
        .image-preview-container:hover {
            border-color: var(--color-primary);
        }
        .image-preview {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .image-placeholder {
            color: var(--color-gray-400);
            text-align: center;
        }
        .image-placeholder i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        .custom-file-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
    </style>
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

            <!-- Page Header -->
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title mb-1">Edit Product</h1>
                    <p class="text-muted mb-0">Update product details</p>
                </div>
                <div>
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i> Back to Products
                    </a>
                </div>
            </div>

            <!-- Edit Product Form -->
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data" class="row g-3">
                        <!-- Product Name -->
                        <div class="col-md-6">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   id="name" name="name" value="<?= htmlspecialchars($formData['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>"
                                    id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" <?= $formData['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category_id'])): ?>
                                <div class="invalid-feedback"><?= $errors['category_id'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Price -->
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price (₱) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0"
                                       class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                                       id="price" name="price" value="<?= htmlspecialchars($formData['price']) ?>" required>
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?= $errors['price'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Stock -->
                        <div class="col-md-6">
                            <label for="stock" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1"
                                   class="form-control <?= isset($errors['stock']) ? 'is-invalid' : '' ?>"
                                   id="stock" name="stock" value="<?= htmlspecialchars($formData['stock']) ?>" required>
                            <?php if (isset($errors['stock'])): ?>
                                <div class="invalid-feedback"><?= $errors['stock'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status" id="statusActive"
                                           value="active" <?= $formData['status'] === 'active' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="statusActive">Active</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="status" id="statusInactive"
                                           value="inactive" <?= $formData['status'] === 'inactive' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="statusInactive">Inactive</label>
                                </div>
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="col-md-6">
                            <label class="form-label">Product Image</label>
                            <div class="image-preview-container mb-2">
                                <?php if (!empty($formData['image']) && file_exists("../../assets/images/products/" . $formData['image'])): ?>
                                    <img src="../../assets/images/products/<?= htmlspecialchars($formData['image']) ?>"
                                         alt="Preview" class="image-preview" id="imagePreview">
                                    <div class="image-placeholder d-none" id="imagePlaceholder">
                                        <i class="bi bi-image"></i>
                                        <p>Click or drag to upload image</p>
                                    </div>
                                <?php else: ?>
                                    <div class="image-placeholder" id="imagePlaceholder">
                                        <i class="bi bi-image"></i>
                                        <p>Click or drag to upload image</p>
                                    </div>
                                    <img src="#" alt="Preview" class="image-preview d-none" id="imagePreview">
                                <?php endif; ?>
                                <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                            </div>
                            <div class="form-text">Recommended size: 800x800px. Max file size: 2MB.</div>
                            <?php if (isset($errors['image'])): ?>
                                <div class="text-danger mt-1"><?= $errors['image'] ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($formData['description']) ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i> Update Product
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer-scripts.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image preview functionality
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const imagePlaceholder = document.getElementById('imagePlaceholder');

        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                    imagePlaceholder.classList.add('d-none');
                }

                reader.readAsDataURL(this.files[0]);
            } else {
                <?php if (empty($formData['image'])): ?>
                    imagePreview.classList.add('d-none');
                    imagePlaceholder.classList.remove('d-none');
                <?php endif; ?>
            }
        });
    });
</script>
</body>
</html>
