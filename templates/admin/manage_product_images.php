<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../../views/login.php");
    exit;
}

require_once "../includes/db.php";

// Initialize variables
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch product details
try {
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: products.php");
        exit;
    }
} catch (PDOException $e) {
    $errorMessage = "Error fetching product: " . $e->getMessage();
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../assets/images/products/";

        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Get file info
        $fileName = $_FILES['product_image']['name'];
        $fileSize = $_FILES['product_image']['size'];
        $fileTmp = $_FILES['product_image']['tmp_name'];
        $fileType = $_FILES['product_image']['type'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Allowed extensions
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file
        if (in_array($fileExt, $allowedExts) && $fileSize < 2097152) { // 2MB max
            // Generate filename based on product name or use unique ID
            if (isset($_POST['use_product_name']) && $_POST['use_product_name'] == 1) {
                // Convert product name to filename format (lowercase, hyphens)
                $baseFilename = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $product['name']), '-'));
                $imageName = $baseFilename . '.' . $fileExt;
            } else {
                // Generate unique filename
                $imageName = uniqid() . '.' . $fileExt;
            }

            $uploadPath = $uploadDir . $imageName;

            // Move uploaded file
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Resize image if needed
                if (function_exists('imagecreatefromjpeg')) {
                    $maxWidth = 800;
                    $maxHeight = 800;

                    list($width, $height) = getimagesize($uploadPath);

                    // Only resize if image is larger than max dimensions
                    if ($width > $maxWidth || $height > $maxHeight) {
                        // Calculate new dimensions while maintaining aspect ratio
                        if ($width > $height) {
                            $newWidth = $maxWidth;
                            $newHeight = intval($height * $maxWidth / $width);
                        } else {
                            $newHeight = $maxHeight;
                            $newWidth = intval($width * $maxHeight / $height);
                        }

                        // Create source image based on file type
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

                            // Preserve transparency for PNG images
                            if ($fileExt === 'png') {
                                imagealphablending($dstImage, false);
                                imagesavealpha($dstImage, true);
                                $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
                                imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
                            }

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

                // Update product image in database
                try {
                    $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
                    $stmt->execute([$imageName, $productId]);

                    // Update product variable with new image
                    $product['image'] = $imageName;

                    $successMessage = "Product image updated successfully.";
                } catch (PDOException $e) {
                    $errorMessage = "Error updating product image: " . $e->getMessage();
                }
            } else {
                $errorMessage = "Failed to upload image.";
            }
        } else {
            $errorMessage = "Invalid file. Please upload a JPG, JPEG, PNG, or GIF file under 2MB.";
        }
    } else {
        $errorMessage = "Please select an image to upload.";
    }
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    if (!empty($product['image'])) {
        $imagePath = "../../assets/images/products/" . $product['image'];
        if (file_exists($imagePath)) {
            if (unlink($imagePath)) {
                try {
                    $stmt = $conn->prepare("UPDATE products SET image = NULL WHERE id = ?");
                    $stmt->execute([$productId]);

                    // Update product variable
                    $product['image'] = null;

                    $successMessage = "Product image deleted successfully.";
                } catch (PDOException $e) {
                    $errorMessage = "Error updating product: " . $e->getMessage();
                }
            } else {
                $errorMessage = "Failed to delete image file.";
            }
        } else {
            // If file doesn't exist, just update the database
            try {
                $stmt = $conn->prepare("UPDATE products SET image = NULL WHERE id = ?");
                $stmt->execute([$productId]);

                // Update product variable
                $product['image'] = null;

                $successMessage = "Product image reference removed successfully.";
            } catch (PDOException $e) {
                $errorMessage = "Error updating product: " . $e->getMessage();
            }
        }
    } else {
        $errorMessage = "No image to delete.";
    }
}

// Function to get category image
function getCategoryImage($categoryName) {
    $defaultImage = "category-default.jpg";
    $categoryName = strtolower($categoryName);

    // Check for available PNG images in categories folder
    if ($categoryName == 'coffee') {
        return "coffee.png";
    } elseif ($categoryName == 'cake' || $categoryName == 'cakes') {
        return "cake.png";
    } elseif ($categoryName == 'pastry' || $categoryName == 'pastries') {
        return "pastries.png";
    } elseif ($categoryName == 'beverage' || $categoryName == 'beverages' || $categoryName == 'non-coffee drinks') {
        return "beverage.png";
    } elseif ($categoryName == 'sandwich' || $categoryName == 'sandwiches') {
        return "sandwich.png";
    } elseif ($categoryName == 'other baked goods') {
        return "baked-goods.png";
    }

    // Return default image if no match
    return $defaultImage;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Product Images - Brew & Bake Admin</title>
    <?php include 'includes/css-includes.php'; ?>
    <style>
        .image-preview-container {
            width: 100%;
            height: 300px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f8f9fa;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .image-preview-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .image-upload-zone {
            width: 100%;
            min-height: 200px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .image-upload-zone:hover, .image-upload-zone.dragover {
            border-color: #f59e0b;
            background-color: rgba(245, 158, 11, 0.1);
        }

        .image-upload-zone i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .image-upload-zone p {
            margin-bottom: 0.5rem;
            color: #6c757d;
        }

        .image-upload-zone .btn {
            margin-top: 1rem;
        }

        .image-requirements {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .image-requirements h5 {
            margin-bottom: 1rem;
            color: #111827;
        }

        .image-requirements ul {
            padding-left: 1.5rem;
            margin-bottom: 0;
        }

        .image-requirements li {
            margin-bottom: 0.5rem;
            color: #6c757d;
        }

        .product-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .product-info h5 {
            margin-bottom: 1rem;
            color: #111827;
        }

        .product-info .info-item {
            margin-bottom: 0.75rem;
        }

        .product-info .info-label {
            font-weight: 600;
            color: #6c757d;
        }

        .product-info .info-value {
            color: #111827;
        }

        @media (max-width: 768px) {
            .image-preview-container {
                height: 250px;
            }

            .image-upload-zone {
                min-height: 180px;
                padding: 1.5rem;
            }

            .image-upload-zone i {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 576px) {
            .image-preview-container {
                height: 200px;
            }

            .image-upload-zone {
                min-height: 150px;
                padding: 1rem;
            }

            .image-upload-zone i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Include Topbar -->
            <?php include 'includes/topbar.php'; ?>

            <!-- Content Area -->
            <div class="admin-content">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="page-title">Manage Product Images</h1>
                        <p class="text-muted">Update and optimize product images</p>
                    </div>
                    <div>
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Products
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (!empty($successMessage)): ?>
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

                <?php if (!empty($errorMessage)): ?>
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

                <!-- Main Content -->
                <div class="row">
                    <!-- Left Column - Product Info and Image Preview -->
                    <div class="col-lg-5 mb-4">
                        <!-- Product Information -->
                        <div class="product-info">
                            <h5><i class="bi bi-info-circle me-2"></i>Product Information</h5>
                            <div class="info-item">
                                <div class="info-label">Product Name</div>
                                <div class="info-value"><?= htmlspecialchars($product['name']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Category</div>
                                <div class="info-value"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Price</div>
                                <div class="info-value">₱<?= number_format($product['price'], 2) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Stock</div>
                                <div class="info-value"><?= $product['stock'] ?> units</div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <?php if ($product['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Current Image Preview -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-image me-2"></i>Current Image</h5>
                            </div>
                            <div class="card-body">
                                <div class="image-preview-container">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="../../assets/images/products/<?= htmlspecialchars($product['image']) ?>"
                                             alt="<?= htmlspecialchars($product['name']) ?>" class="img-fluid">
                                    <?php elseif (!empty($product['category_name'])): ?>
                                        <div class="text-center">
                                            <img src="../../assets/images/categories/<?= getCategoryImage($product['category_name']) ?>"
                                                 alt="<?= htmlspecialchars($product['category_name']) ?>"
                                                 class="img-fluid opacity-50" style="max-height: 200px;">
                                            <p class="mt-3 text-muted">No product image available.<br>Using category placeholder.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center">
                                            <i class="bi bi-image text-muted" style="font-size: 5rem;"></i>
                                            <p class="mt-3 text-muted">No image available</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($product['image'])): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <small class="text-muted">Filename: <?= htmlspecialchars($product['image']) ?></small>
                                        </div>
                                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                            <button type="submit" name="delete_image" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i>Delete Image
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Upload Form and Requirements -->
                    <div class="col-lg-7">
                        <!-- Image Requirements -->
                        <div class="image-requirements">
                            <h5><i class="bi bi-list-check me-2"></i>Image Requirements</h5>
                            <ul>
                                <li><strong>Format:</strong> JPG, JPEG, PNG, or GIF (PNG preferred for transparency)</li>
                                <li><strong>Size:</strong> 800x800 pixels (1:1 aspect ratio) - Images will be automatically resized</li>
                                <li><strong>Background:</strong> Transparent or white background preferred</li>
                                <li><strong>File Size:</strong> Maximum 2MB (smaller files load faster)</li>
                                <li><strong>Naming Convention:</strong> You can use product name or let the system generate a unique name</li>
                            </ul>
                        </div>

                        <!-- Upload Form -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="bi bi-upload me-2"></i>Upload New Image</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" enctype="multipart/form-data" id="imageUploadForm">
                                    <div class="image-upload-zone" id="dropZone">
                                        <i class="bi bi-cloud-arrow-up"></i>
                                        <p class="text-center">Drag & drop your image here or click to browse</p>
                                        <p class="text-center text-muted small">Supported formats: JPG, JPEG, PNG, GIF</p>
                                        <input type="file" name="product_image" id="productImage" class="d-none" accept=".jpg,.jpeg,.png,.gif">
                                        <button type="button" class="btn btn-outline-primary" id="browseButton">
                                            <i class="bi bi-folder me-1"></i>Browse Files
                                        </button>
                                    </div>

                                    <div id="imagePreviewContainer" class="mt-4 d-none">
                                        <h6>Image Preview</h6>
                                        <div class="image-preview-container">
                                            <img src="" id="imagePreview" alt="Preview" class="img-fluid">
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelUpload">
                                                <i class="bi bi-x-circle me-1"></i>Cancel
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" value="1" id="useProductName" name="use_product_name" checked>
                                            <label class="form-check-label" for="useProductName">
                                                Use product name as filename (recommended for SEO)
                                            </label>
                                            <div class="form-text">
                                                Example: "<?= strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $product['name']), '-')) ?>.png"
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" name="upload_image" class="btn btn-primary" id="uploadButton" disabled>
                                                <i class="bi bi-cloud-upload me-1"></i>Upload Image
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <?php include 'includes/footer-scripts.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropZone = document.getElementById('dropZone');
            const productImage = document.getElementById('productImage');
            const browseButton = document.getElementById('browseButton');
            const imagePreview = document.getElementById('imagePreview');
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');
            const uploadButton = document.getElementById('uploadButton');
            const cancelUpload = document.getElementById('cancelUpload');

            // Browse button click handler
            browseButton.addEventListener('click', function() {
                productImage.click();
            });

            // File input change handler
            productImage.addEventListener('change', function() {
                handleFileSelect(this.files);
            });

            // Drag and drop handlers
            dropZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', function() {
                dropZone.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', function(e) {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                handleFileSelect(e.dataTransfer.files);
            });

            // Cancel upload button handler
            cancelUpload.addEventListener('click', function() {
                resetUploadForm();
            });

            // Handle file selection
            function handleFileSelect(files) {
                if (files.length > 0) {
                    const file = files[0];

                    // Check file type
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        alert('Please select a valid image file (JPG, JPEG, PNG, or GIF).');
                        return;
                    }

                    // Check file size (max 2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        alert('File size exceeds 2MB. Please select a smaller file.');
                        return;
                    }

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreviewContainer.classList.remove('d-none');
                        dropZone.classList.add('d-none');
                        uploadButton.disabled = false;
                    };
                    reader.readAsDataURL(file);
                }
            }

            // Reset upload form
            function resetUploadForm() {
                productImage.value = '';
                imagePreview.src = '';
                imagePreviewContainer.classList.add('d-none');
                dropZone.classList.remove('d-none');
                uploadButton.disabled = true;
            }
        });
    </script>
</body>
</html>