<?php
/**
 * Import Products Script
 *
 * This script imports products from the add_products.sql file into the database
 * It also generates placeholder images for products if they don't exist
 */

// Include database connection
require_once "templates/includes/db.php";

// Check if the SQL file exists
$sqlFile = 'add_products.sql';
if (!file_exists($sqlFile)) {
    die("Error: SQL file not found: $sqlFile");
}

// Read SQL file
$sql = file_get_contents($sqlFile);

// Split SQL file into individual queries
$queries = explode(';', $sql);

// Execute each query
$successCount = 0;
$errorCount = 0;
$errors = [];

echo "<h1>Importing Products</h1>";
echo "<p>Starting import process...</p>";

foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) continue;

    try {
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $successCount++;
    } catch (PDOException $e) {
        $errorCount++;
        $errors[] = $e->getMessage();
    }
}

echo "<p>Import completed with $successCount successful queries and $errorCount errors.</p>";

if ($errorCount > 0) {
    echo "<h2>Errors:</h2>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
}

// Check if we need to generate placeholder images
echo "<h2>Checking Product Images</h2>";

// Get all products
try {
    $stmt = $conn->query("SELECT id, name, image, category_id FROM products");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get categories
    $stmt = $conn->query("SELECT id, name FROM categories");
    $categories = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[$row['id']] = $row['name'];
    }

    // Check if image directory exists
    $imageDir = 'assets/images/products/';
    if (!is_dir($imageDir)) {
        if (!mkdir($imageDir, 0755, true)) {
            echo "<p>Warning: Could not create image directory: $imageDir</p>";
        }
    }

    // Count missing images
    $missingImages = 0;
    foreach ($products as $product) {
        if (!empty($product['image'])) {
            $imagePath = $imageDir . $product['image'];
            if (!file_exists($imagePath)) {
                $missingImages++;
            }
        }
    }

    echo "<p>Found " . count($products) . " products in the database.</p>";
    echo "<p>Missing images: $missingImages</p>";

    if ($missingImages > 0) {
        echo "<h3>Generate Placeholder Images</h3>";
        echo "<div class='alert alert-info'>";
        echo "<p>You have two options for generating placeholder images:</p>";
        echo "<ol>";
        echo "<li><strong>Using GD Library (Recommended if Available):</strong> This will create actual image files.</li>";
        echo "<li><strong>Using CSS-Based Placeholders:</strong> This will create HTML/CSS placeholders if GD is not available.</li>";
        echo "</ol>";
        echo "</div>";

        echo "<div class='d-flex gap-3'>";
        echo "<a href='generate_product_images.php' class='btn btn-primary'>Option 1: Generate Image Files (GD)</a>";
        echo "<a href='generate_css_placeholders.php' class='btn btn-secondary'>Option 2: CSS-Based Placeholders</a>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<p>Error checking products: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Add link to view products
echo "<p><a href='templates/client/client.php' class='btn btn-success'>View Products</a></p>";
?>
